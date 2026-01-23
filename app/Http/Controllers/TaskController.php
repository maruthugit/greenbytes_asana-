<?php
namespace App\Http\Controllers;

use App\Models\Task;
use App\Models\Project;
use App\Models\Team;
use App\Models\User;
use App\Models\TaskActivity;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;
use Mews\Purifier\Facades\Purifier;

class TaskController extends Controller
{
    private function logActivity(Task $task, string $type, array $meta = []): void
    {
        TaskActivity::create([
            'task_id' => $task->id,
            'user_id' => auth()->id(),
            'type' => $type,
            'meta' => empty($meta) ? null : $meta,
        ]);
    }

    private function accessibleTeamIds()
    {
        $user = auth()->user();

		if ($user && $user->hasRole('admin')) {
			return Team::query()->pluck('id');
		}

        $owned = $user->ownedTeams()->pluck('teams.id');
        $memberOf = $user->teams()->pluck('teams.id');

        return $owned->merge($memberOf)->unique()->values();
    }

    private function accessibleUserIds()
    {
        $teamIds = $this->accessibleTeamIds();

        $ownerIds = Team::query()->whereIn('id', $teamIds)->pluck('user_id');
        $memberIds = DB::table('team_user')->whereIn('team_id', $teamIds)->pluck('user_id');

        return $ownerIds->merge($memberIds)->unique()->values();
    }

    private function allowedAssigneeIds()
    {
        $currentUser = auth()->user();
        $accessibleUserIds = $this->accessibleUserIds();

        if ($currentUser->hasRole('admin')) {
            return $accessibleUserIds;
        }

        if ($currentUser->hasRole('manager')) {
            $memberIds = User::query()
                ->whereIn('id', $accessibleUserIds)
                ->role('member')
                ->whereDoesntHave('roles', function ($q) {
                    $q->whereIn('name', ['admin', 'manager']);
                })
                ->pluck('id');

            return $memberIds
                ->merge([(int) $currentUser->id])
                ->unique()
                ->values();
        }

        return collect([(int) $currentUser->id]);
    }

    private function canAssignTo(?int $assigneeId): bool
    {
        if ($assigneeId === null) {
            return true;
        }

        return $this->allowedAssigneeIds()->contains((int) $assigneeId);
    }

    private function assigneeNotAllowedMessage(?int $assigneeId): string
    {
        $currentUser = auth()->user();

        if ($assigneeId === null) {
            return 'Invalid assignee.';
        }

        if ($currentUser->hasRole('manager')) {
            return 'Managers can assign tasks to members or themselves only.';
        }

        if ($currentUser->hasRole('member')) {
            return 'Members can only assign tasks to themselves.';
        }

        return 'You are not allowed to assign tasks to this user.';
    }

    private function findAccessibleTaskOrFail(Task $task): Task
    {
        $teamIds = $this->accessibleTeamIds();

        return Task::query()
            ->where('id', $task->id)
            ->whereIn('project_id', Project::query()->whereIn('team_id', $teamIds)->select('id'))
            ->firstOrFail();
    }

    private function findAccessibleTaskIdOrFail(int $taskId): Task
    {
        $teamIds = $this->accessibleTeamIds();

        return Task::query()
            ->where('id', $taskId)
            ->whereIn('project_id', Project::query()->whereIn('team_id', $teamIds)->select('id'))
            ->firstOrFail();
    }

    public function index()
    {
        $view = Str::lower((string) request()->query('view', 'list'));
        if (!in_array($view, ['list', 'board', 'calendar', 'files'], true)) {
            $view = 'list';
        }

        $teamIds = $this->accessibleTeamIds();

        $projects = Project::query()
            ->whereIn('team_id', $teamIds)
            ->orderBy('name')
            ->get();

        $tasksQuery = Task::query()
            ->whereIn('project_id', $projects->pluck('id'))
            ->with(['project', 'assignee', 'creator'])
            ->withCount('comments');

        $tasks = match ($view) {
            'board' => $tasksQuery->orderBy('position')->orderByDesc('id')->get(),
            default => $tasksQuery->latest()->get(),
        };

        $selectedTask = null;
        $selectedMode = 'none';
        $selectedTaskRaw = request()->query('task');

        if ($selectedTaskRaw === 'new') {
            $selectedMode = 'new';
        } elseif ($selectedTaskRaw !== null && $selectedTaskRaw !== '') {
            $selectedTaskId = (int) $selectedTaskRaw;
            if ($selectedTaskId > 0) {
                $selectedMode = 'view';
                $selectedTask = $this->findAccessibleTaskIdOrFail($selectedTaskId);
                $selectedTask->load([
                    'project.team',
                    'assignee',
                    'creator',
                    'comments.user',
                    'activities.user',
                ]);
            }
        }

        $users = User::query()
            ->whereIn('id', $this->allowedAssigneeIds())
            ->orderBy('name')
            ->get();

        $tasksByStatus = null;
        $calendarGroups = null;
        $tasksWithImages = null;
        $fileItems = null;

        $filesQuery = (string) request()->query('files_q', '');
        $filesType = Str::lower((string) request()->query('files_type', 'all'));
        $filesProject = (string) request()->query('files_project', '');
        $filesSort = Str::lower((string) request()->query('files_sort', 'recent'));

        if (!in_array($filesType, ['all', 'images', 'files'], true)) {
            $filesType = 'all';
        }
        if (!in_array($filesSort, ['recent', 'task'], true)) {
            $filesSort = 'recent';
        }

        if ($view === 'board') {
            $tasksByStatus = $tasks->groupBy('status');
        }
        if ($view === 'calendar') {
            $calendarGroups = $tasks
                ->filter(fn ($t) => !empty($t->due_date))
                ->sortBy(fn ($t) => $t->due_date)
                ->groupBy(fn ($t) => $t->due_date->format('Y-m-d'));
        }
        if ($view === 'files') {
            $tasksWithImages = $tasks->filter(fn ($t) => !empty($t->image_path))->values();

            $fileItems = collect();

            foreach ($tasks as $task) {
                if (!empty($task->image_path)) {
                    $fileItems->push([
                        'task' => $task,
                        'kind' => 'image',
                        'url' => Storage::url($task->image_path),
                        'name' => 'Task image',
                    ]);
                }

                $html = (string) ($task->description ?? '');
                if ($html === '') {
                    continue;
                }

                // Extract embedded images
                if (preg_match_all('/<img[^>]+src=["\']([^"\']+)["\']/i', $html, $matches)) {
                    foreach ($matches[1] as $src) {
                        $url = $this->normalizeStorageUrl($src);
                        if ($url === null) {
                            continue;
                        }
                        $fileItems->push([
                            'task' => $task,
                            'kind' => 'image',
                            'url' => $url,
                            'name' => 'Embedded image',
                        ]);
                    }
                }

                // Extract embedded file links
                if (preg_match_all('/<a[^>]+href=["\']([^"\']+)["\'][^>]*>(.*?)<\/a>/is', $html, $matches)) {
                    $hrefs = $matches[1] ?? [];
                    $texts = $matches[2] ?? [];
                    foreach ($hrefs as $i => $href) {
                        $url = $this->normalizeStorageUrl($href);
                        if ($url === null) {
                            continue;
                        }

                        // Skip links that are actually images already captured.
                        if (str_contains($url, '/storage/task-description/')) {
                            continue;
                        }

                        $label = trim(strip_tags((string) ($texts[$i] ?? '')));
                        $fileItems->push([
                            'task' => $task,
                            'kind' => 'file',
                            'url' => $url,
                            'name' => $label !== '' ? $label : 'Attachment',
                        ]);
                    }
                }
            }

            $fileItems = $fileItems
                ->unique(fn ($item) => ($item['kind'] ?? '') . '|' . ($item['url'] ?? '') . '|' . (($item['task']->id ?? 0)))
                ->values();

            $fileItems = $fileItems->map(function (array $item) {
                $path = $this->publicDiskPathFromStorageUrl((string) ($item['url'] ?? ''));
                if ($path === null) {
                    return $item;
                }

                $item['path'] = $path;
                $item['ext'] = strtolower(pathinfo($path, PATHINFO_EXTENSION));

                try {
                    if (Storage::disk('public')->exists($path)) {
                        $size = Storage::disk('public')->size($path);
                        $item['size'] = $size;
                        $item['size_human'] = $this->humanFileSize((int) $size);

                        $modified = Storage::disk('public')->lastModified($path);
                        $item['modified_at'] = $modified;
                    }
                } catch (\Throwable $e) {
                    // Ignore file metadata errors; still show the link.
                }

                return $item;
            });

            // Apply Files tab filters
            if ($filesType === 'images') {
                $fileItems = $fileItems->filter(fn ($i) => ($i['kind'] ?? '') === 'image')->values();
            } elseif ($filesType === 'files') {
                $fileItems = $fileItems->filter(fn ($i) => ($i['kind'] ?? '') === 'file')->values();
            }

            if ($filesProject !== '') {
                $projectId = (int) $filesProject;
                if ($projectId > 0) {
                    $fileItems = $fileItems->filter(function ($i) use ($projectId) {
                        $task = $i['task'] ?? null;
                        return $task && (int) $task->project_id === $projectId;
                    })->values();
                }
            }

            $q = trim($filesQuery);
            if ($q !== '') {
                $qLower = Str::lower($q);
                $fileItems = $fileItems->filter(function ($i) use ($qLower) {
                    $task = $i['task'] ?? null;
                    $haystacks = [
                        (string) ($i['name'] ?? ''),
                        (string) ($i['url'] ?? ''),
                        $task ? (string) ($task->title ?? '') : '',
                        $task && $task->project ? (string) ($task->project->name ?? '') : '',
                    ];
                    foreach ($haystacks as $h) {
                        if (Str::contains(Str::lower($h), $qLower)) {
                            return true;
                        }
                    }
                    return false;
                })->values();
            }

            // Sorting
            if ($filesSort === 'recent') {
                $fileItems = $fileItems
                    ->sortByDesc(function ($i) {
                        return (int) ($i['modified_at'] ?? 0);
                    })
                    ->values();
            } elseif ($filesSort === 'task') {
                $fileItems = $fileItems
                    ->sortBy(function ($i) {
                        $task = $i['task'] ?? null;
                        return $task ? (string) ($task->title ?? '') : '';
                    })
                    ->values();
            }
        }

        return view('tasks.index', [
            'projects' => $projects,
            'tasks' => $tasks,
            'users' => $users,
            'selectedTask' => $selectedTask,
            'selectedMode' => $selectedMode,
            'view' => $view,
            'tasksByStatus' => $tasksByStatus,
            'calendarGroups' => $calendarGroups,
            'tasksWithImages' => $tasksWithImages,
            'fileItems' => $fileItems,

            'files_q' => $filesQuery,
            'files_type' => $filesType,
            'files_project' => $filesProject,
            'files_sort' => $filesSort,
        ]);
    }

    private function normalizeStorageUrl(string $url): ?string
    {
        $url = trim($url);
        if ($url === '') {
            return null;
        }

        // Accept either relative /storage/... or absolute http(s)://.../storage/...
        if (str_starts_with($url, '/storage/')) {
            return $url;
        }

        if (preg_match('/^https?:\/\//i', $url) === 1) {
            $path = parse_url($url, PHP_URL_PATH);
            if (is_string($path) && str_starts_with($path, '/storage/')) {
                return $path;
            }
        }

        return null;
    }

    private function publicDiskPathFromStorageUrl(string $url): ?string
    {
        $path = $this->normalizeStorageUrl($url);
        if ($path === null) {
            return null;
        }

        if (!str_starts_with($path, '/storage/')) {
            return null;
        }

        return ltrim(substr($path, strlen('/storage/')), '/');
    }

    private function humanFileSize(int $bytes): string
    {
        if ($bytes < 1024) {
            return $bytes . ' B';
        }
        $units = ['KB', 'MB', 'GB', 'TB'];
        $size = $bytes / 1024;
        foreach ($units as $unit) {
            if ($size < 1024) {
                return round($size, 1) . ' ' . $unit;
            }
            $size /= 1024;
        }
        return round($size, 1) . ' PB';
    }

    private function isAllowedAttachmentPath(string $path): bool
    {
        return str_starts_with($path, 'task-images/')
            || str_starts_with($path, 'task-description/')
            || str_starts_with($path, 'task-attachments/');
    }

    private function normalizeEmptyRichText(?string $html): ?string
    {
        $html = (string) ($html ?? '');
        $html = trim($html);
        if ($html === '') {
            return null;
        }

        $text = strip_tags(html_entity_decode($html));
        $text = trim(str_replace(["\xC2\xA0", '&nbsp;'], ' ', $text));
        if ($text === '') {
            return null;
        }

        return $html;
    }

    public function store(Request $request)
    {
        $data = $request->validate([
            'project_id' => ['required', 'integer', 'exists:projects,id'],
            'title' => ['required', 'string', 'max:255'],
            'description' => ['nullable', 'string', 'max:50000'],
            'status' => ['required', 'in:Todo,Doing,Done'],
            'due_date' => ['nullable', 'date'],
            'assigned_to' => ['nullable', 'integer', 'exists:users,id'],
            'image' => ['nullable', 'image', 'max:4096'],
        ]);

        if (array_key_exists('description', $data) && $data['description'] !== null) {
            $data['description'] = Purifier::clean($data['description']);
        }

        $teamIds = $this->accessibleTeamIds();

        $project = Project::query()
            ->where('id', $data['project_id'])
            ->whereIn('team_id', $teamIds)
            ->firstOrFail();

        $assignedTo = $data['assigned_to'] ?? null;
        if ($assignedTo !== null) {
            if (!$this->canAssignTo((int) $assignedTo)) {
                return back()->withErrors(['assigned_to' => $this->assigneeNotAllowedMessage((int) $assignedTo)]);
            }

            $accessibleUserIds = $this->accessibleUserIds();
            if (!$accessibleUserIds->contains((int) $assignedTo)) {
                return back()->withErrors(['assigned_to' => 'Assignee must be a member of this team.']);
            }
        }

        $nextPosition = (int) Task::query()
            ->where('project_id', $project->id)
            ->where('status', $data['status'])
            ->max('position');

        $imagePath = null;
        if ($request->hasFile('image')) {
            $imagePath = $request->file('image')->store('task-images', 'public');
        }

        $task = Task::create([
            'project_id' => $project->id,
            'title' => $data['title'],
            'description' => $data['description'] ?? null,
            'status' => $data['status'],
            'due_date' => $data['due_date'] ?? null,
            'assigned_to' => $data['assigned_to'] ?? null,
            'created_by' => auth()->id(),
            'position' => $nextPosition + 1,
            'image_path' => $imagePath,
        ]);

        return redirect('/tasks?task=' . $task->id)
            ->with('toast', ['type' => 'success', 'message' => 'Task created.']);
    }

    public function show(Task $task)
    {
        $task = $this->findAccessibleTaskOrFail($task);

        $task->load([
            'project.team',
            'assignee',
            'creator',
            'comments.user',
            'activities.user',
        ]);

        return view('tasks.show', [
            'task' => $task,
        ]);
    }

    public function update(Request $request, Task $task)
    {
        $task = $this->findAccessibleTaskOrFail($task);

        $before = [
            'status' => (string) $task->status,
            'assigned_to' => $task->assigned_to,
            'due_date' => optional($task->due_date)->format('Y-m-d'),
            'description' => (string) ($task->description ?? ''),
        ];

        $data = $request->validate([
            'project_id' => ['required', 'integer', 'exists:projects,id'],
            'title' => ['required', 'string', 'max:255'],
            'description' => ['nullable', 'string', 'max:50000'],
            'status' => ['required', 'in:Todo,Doing,Done'],
            'due_date' => ['nullable', 'date'],
            'assigned_to' => ['nullable', 'integer', 'exists:users,id'],
        ]);

        if (array_key_exists('description', $data) && $data['description'] !== null) {
            $data['description'] = Purifier::clean($data['description']);
        }

        $teamIds = $this->accessibleTeamIds();

        $project = Project::query()
            ->where('id', $data['project_id'])
            ->whereIn('team_id', $teamIds)
            ->firstOrFail();

        $assignedTo = $data['assigned_to'] ?? null;
        $assigneeChanged = (string) ($assignedTo ?? '') !== (string) ($task->assigned_to ?? '');

        // Only enforce assignee restrictions when changing the assignee.
        // This prevents existing tasks (assigned by an admin, or before role changes)
        // from becoming impossible to edit.
        if ($assigneeChanged && $assignedTo !== null) {
            if (!$this->canAssignTo((int) $assignedTo)) {
                return back()->withErrors(['assigned_to' => $this->assigneeNotAllowedMessage((int) $assignedTo)]);
            }

            $accessibleUserIds = $this->accessibleUserIds();
            if (!$accessibleUserIds->contains((int) $assignedTo)) {
                return back()->withErrors(['assigned_to' => 'Assignee must be a member of this team.']);
            }
        }

        $task->forceFill([
            'project_id' => $project->id,
            'title' => $data['title'],
            'description' => $data['description'] ?? null,
            'status' => $data['status'],
            'due_date' => $data['due_date'] ?? null,
            'assigned_to' => $data['assigned_to'] ?? null,
        ]);

        $task->save();

        $after = [
            'status' => (string) $task->status,
            'assigned_to' => $task->assigned_to,
            'due_date' => optional($task->due_date)->format('Y-m-d'),
            'description' => (string) ($task->description ?? ''),
        ];

        if ($before['status'] !== $after['status']) {
            $this->logActivity($task, 'status.changed', [
                'from' => $before['status'],
                'to' => $after['status'],
            ]);
        }

        if ((string) ($before['assigned_to'] ?? '') !== (string) ($after['assigned_to'] ?? '')) {
            $assigneeIds = array_values(array_filter([
                $before['assigned_to'] ?? null,
                $after['assigned_to'] ?? null,
            ], fn ($v) => $v !== null && $v !== ''));

            $assigneeNames = empty($assigneeIds)
                ? collect()
                : User::query()->whereIn('id', $assigneeIds)->pluck('name', 'id');

            $this->logActivity($task, 'assignee.changed', [
                'from' => $before['assigned_to'],
                'to' => $after['assigned_to'],
                'from_name' => $before['assigned_to'] ? ($assigneeNames[(int) $before['assigned_to']] ?? null) : null,
                'to_name' => $after['assigned_to'] ? ($assigneeNames[(int) $after['assigned_to']] ?? null) : null,
            ]);
        }

        if ((string) ($before['due_date'] ?? '') !== (string) ($after['due_date'] ?? '')) {
            $this->logActivity($task, 'due_date.changed', [
                'from' => $before['due_date'],
                'to' => $after['due_date'],
            ]);
        }

        if ($before['description'] !== $after['description']) {
            $this->logActivity($task, 'description.updated');
        }

        return redirect('/tasks?task=' . $task->id)
            ->with('toast', ['type' => 'success', 'message' => 'Task updated.']);
    }

    public function complete(Task $task)
    {
        $task = $this->findAccessibleTaskOrFail($task);

        $before = (string) $task->status;

        $task->update(['status' => 'Done']);

        if ($before !== 'Done') {
            $this->logActivity($task, 'status.changed', [
                'from' => $before,
                'to' => 'Done',
            ]);
        }

        return back()->with('toast', ['type' => 'success', 'message' => 'Task marked complete.']);
    }

    public function destroy(Request $request, Task $task)
    {
        if (!auth()->user()?->hasRole('admin')) {
            abort(403);
        }

        $task = $this->findAccessibleTaskOrFail($task);

        $data = $request->validate([
            'redirect' => ['nullable', 'string', 'max:2048'],
        ]);

        $redirect = (string) ($data['redirect'] ?? '');

        // Best-effort cleanup of stored files.
        $pathsToDelete = collect();

        if (!empty($task->image_path)) {
            $pathsToDelete->push((string) $task->image_path);
        }

        $html = (string) ($task->description ?? '');
        if ($html !== '') {
            try {
                if (preg_match_all('/<img[^>]+src=["\']([^"\']+)["\']/i', $html, $matches)) {
                    foreach (($matches[1] ?? []) as $src) {
                        $url = $this->normalizeStorageUrl((string) $src);
                        if ($url === null) {
                            continue;
                        }
                        $path = $this->publicDiskPathFromStorageUrl($url);
                        if ($path !== null && $this->isAllowedAttachmentPath($path)) {
                            $pathsToDelete->push($path);
                        }
                    }
                }

                if (preg_match_all('/<a[^>]+href=["\']([^"\']+)["\']/i', $html, $matches)) {
                    foreach (($matches[1] ?? []) as $href) {
                        $url = $this->normalizeStorageUrl((string) $href);
                        if ($url === null) {
                            continue;
                        }
                        $path = $this->publicDiskPathFromStorageUrl($url);
                        if ($path !== null && $this->isAllowedAttachmentPath($path)) {
                            $pathsToDelete->push($path);
                        }
                    }
                }
            } catch (\Throwable $e) {
                // Ignore extraction errors; delete proceeds.
            }
        }

        $pathsToDelete
            ->filter(fn ($p) => is_string($p) && $p !== '')
            ->unique()
            ->each(function (string $path) {
                try {
                    Storage::disk('public')->delete($path);
                } catch (\Throwable $e) {
                    // Ignore deletion errors; delete proceeds.
                }
            });

        $task->delete();

        // Prevent open redirects; only allow local paths.
        if ($redirect !== '' && str_starts_with($redirect, '/')) {
            return redirect($redirect)->with('toast', ['type' => 'success', 'message' => 'Task deleted.']);
        }

        return redirect('/tasks')->with('toast', ['type' => 'success', 'message' => 'Task deleted.']);
    }

    public function destroyAttachment(Request $request, Task $task)
    {
        $task = $this->findAccessibleTaskOrFail($task);

        $beforeDescription = (string) ($task->description ?? '');
        $beforeImage = $task->image_path;

        $data = $request->validate([
            'url' => ['required', 'string', 'max:2048'],
        ]);

        $url = (string) $data['url'];
        $normalizedUrl = $this->normalizeStorageUrl($url);
        if ($normalizedUrl === null) {
            return back()->withErrors(['url' => 'Invalid attachment URL.']);
        }

        $path = $this->publicDiskPathFromStorageUrl($normalizedUrl);
        if ($path === null || !$this->isAllowedAttachmentPath($path)) {
            return back()->withErrors(['url' => 'Attachment is not deletable.']);
        }

        $taskImageUrl = $task->image_path ? Storage::url($task->image_path) : null;

        $changed = false;

        if ($taskImageUrl !== null && $normalizedUrl === $taskImageUrl) {
            $task->image_path = null;
            $changed = true;
        } else {
            $html = (string) ($task->description ?? '');
            if ($html !== '') {
                $quoted = preg_quote($normalizedUrl, '/');

                // Remove matching embedded images
                $html = preg_replace('/<img\b[^>]*\bsrc=["\']' . $quoted . '["\'][^>]*>/i', '', $html) ?? $html;

                // Remove matching links (attachments)
                $html = preg_replace('/<a\b[^>]*\bhref=["\']' . $quoted . '["\'][^>]*>.*?<\/a>/is', '', $html) ?? $html;

                $html = Purifier::clean($html);
                $task->description = $this->normalizeEmptyRichText($html);
                $changed = true;
            }
        }

        if ($changed) {
            $task->save();

            $this->logActivity($task, 'attachment.removed', [
                'url' => $normalizedUrl,
            ]);

            $afterDescription = (string) ($task->description ?? '');
            if ($beforeDescription !== $afterDescription) {
                $this->logActivity($task, 'description.updated');
            }
            if ($beforeImage !== $task->image_path) {
                $this->logActivity($task, 'image.removed');
            }
        }

        // Delete underlying file only if no other tasks reference it.
        $isReferencedElsewhere = Task::query()
            ->where('id', '!=', $task->id)
            ->where(function ($q) use ($normalizedUrl, $path) {
                $q->where('description', 'like', '%' . $normalizedUrl . '%')
                    ->orWhere('image_path', $path);
            })
            ->exists();

        if (!$isReferencedElsewhere && Storage::disk('public')->exists($path)) {
            Storage::disk('public')->delete($path);
        }

        return back()->with('toast', ['type' => 'success', 'message' => 'Attachment deleted.']);
    }
}
