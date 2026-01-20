<?php
namespace App\Http\Controllers;

use App\Models\Project;
use App\Models\Task;
use App\Models\TaskActivity;
use App\Models\Team;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;

class ProjectController extends Controller
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

    private function findAccessibleProjectOrFail(Project $project): Project
    {
        $teamIds = $this->accessibleTeamIds();

        return Project::query()
            ->where('id', $project->id)
            ->whereIn('team_id', $teamIds)
            ->firstOrFail();
    }

    public function dashboard(Request $request)
    {
        $user = auth()->user();

        $tab = Str::lower((string) $request->query('tab', 'upcoming'));
        if (!in_array($tab, ['upcoming', 'overdue', 'completed'], true)) {
            $tab = 'upcoming';
        }

        $teamIds = $this->accessibleTeamIds();

        $projectIds = Project::query()
            ->whereIn('team_id', $teamIds)
            ->pluck('id');

        $today = now()->startOfDay();

        $myTasksBase = Task::query()
            ->whereIn('project_id', $projectIds)
            ->where('assigned_to', $user->id)
            ->with(['project.team']);

        $counts = [
            'upcoming' => (clone $myTasksBase)
                ->where('status', '!=', 'Done')
                ->where(function ($q) use ($today) {
                    $q->whereNull('due_date')
                        ->orWhereDate('due_date', '>=', $today->toDateString());
                })
                ->count(),
            'overdue' => (clone $myTasksBase)
                ->where('status', '!=', 'Done')
                ->whereDate('due_date', '<', $today->toDateString())
                ->count(),
            'completed' => (clone $myTasksBase)
                ->where('status', 'Done')
                ->count(),
        ];

        $tasks = match ($tab) {
            'overdue' => (clone $myTasksBase)
                ->where('status', '!=', 'Done')
                ->whereDate('due_date', '<', $today->toDateString())
                ->orderBy('due_date')
                ->limit(12)
                ->get(),
            'completed' => (clone $myTasksBase)
                ->where('status', 'Done')
                ->latest()
                ->limit(12)
                ->get(),
            default => (clone $myTasksBase)
                ->where('status', '!=', 'Done')
                ->orderByRaw('due_date is null')
                ->orderBy('due_date')
                ->latest('id')
                ->limit(12)
                ->get(),
        };

        $projects = Project::query()
            ->whereIn('team_id', $teamIds)
            ->with('team')
            ->latest()
            ->limit(12)
            ->get();

        return view('dashboard.index', [
            'tab' => $tab,
            'counts' => $counts,
            'tasks' => $tasks,
            'projects' => $projects,
        ]);
    }

    public function index()
    {
        $teamIds = $this->accessibleTeamIds();

        $teams = Team::query()->whereIn('id', $teamIds)->orderBy('name')->get();

        $projects = Project::query()
            ->whereIn('team_id', $teamIds)
            ->with('team')
            ->latest()
            ->get();

        return view('projects.index', [
            'teams' => $teams,
            'projects' => $projects,
        ]);
    }

    public function store(Request $request)
    {
        if (!auth()->user()?->can('projects.manage') && !auth()->user()?->can('projects.create')) {
            abort(403);
        }

        $data = $request->validate([
            'team_id' => ['required', 'integer', 'exists:teams,id'],
            'name' => ['required', 'string', 'max:255'],
            'description' => ['nullable', 'string'],
        ]);

        $teamIds = $this->accessibleTeamIds();

        $team = Team::query()->where('id', $data['team_id'])->whereIn('id', $teamIds)->firstOrFail();

        Project::create([
            'team_id' => $team->id,
            'name' => $data['name'],
            'description' => $data['description'] ?? null,
        ]);

        return back()->with('toast', ['type' => 'success', 'message' => 'Project created.']);
    }

    public function edit(Project $project)
    {
        if (!auth()->user()?->can('projects.manage') && !auth()->user()?->can('projects.update')) {
            abort(403);
        }

        $project = $this->findAccessibleProjectOrFail($project);

        return view('projects.edit', ['project' => $project]);
    }

    public function update(Request $request, Project $project)
    {
        if (!auth()->user()?->can('projects.manage') && !auth()->user()?->can('projects.update')) {
            abort(403);
        }

        $project = $this->findAccessibleProjectOrFail($project);

        $data = $request->validate([
            'name' => ['required', 'string', 'max:255'],
            'description' => ['nullable', 'string'],
        ]);

        $project->update([
            'name' => $data['name'],
            'description' => $data['description'] ?? null,
        ]);

        return redirect()->route('projects.index')->with('toast', ['type' => 'success', 'message' => 'Project updated.']);
    }

    public function destroy(Request $request, Project $project)
    {
        if (!auth()->user()?->hasRole('admin')) {
            abort(403);
        }

        $project = $this->findAccessibleProjectOrFail($project);

        if ($project->tasks()->exists()) {
            return redirect()->route('projects.index')->with('toast', [
                'type' => 'error',
                'message' => 'Cannot delete a project that still has tasks. Delete/move the tasks first.',
            ]);
        }

        $project->delete();

        return redirect()->route('projects.index')->with('toast', ['type' => 'success', 'message' => 'Project deleted.']);
    }

    public function board(Project $project)
    {
        $project = $this->findAccessibleProjectOrFail($project);

        $tasks = Task::query()
            ->where('project_id', $project->id)
            ->with('assignee')
            ->ordered()
            ->get()
            ->groupBy('status');

        return view('projects.board', [
            'project' => $project,
            'columns' => [
                'Todo' => $tasks->get('Todo', collect()),
                'Doing' => $tasks->get('Doing', collect()),
                'Done' => $tasks->get('Done', collect()),
            ],
        ]);
    }

    public function updateBoard(Request $request, Project $project)
    {
        $project = $this->findAccessibleProjectOrFail($project);

        $data = $request->validate([
            'columns' => ['required', 'array'],
            'columns.Todo' => ['nullable', 'array'],
            'columns.Doing' => ['nullable', 'array'],
            'columns.Done' => ['nullable', 'array'],
            'columns.Todo.*' => ['integer'],
            'columns.Doing.*' => ['integer'],
            'columns.Done.*' => ['integer'],
        ]);

        $columns = $data['columns'];

        $allIds = collect($columns)->flatten()->filter()->unique()->values();

        $projectTaskIds = Task::query()
            ->where('project_id', $project->id)
            ->whereIn('id', $allIds)
            ->pluck('id');

        if ($projectTaskIds->count() !== $allIds->count()) {
            return response()->json(['message' => 'Invalid tasks provided.'], 422);
        }

        DB::transaction(function () use ($columns, $project) {
            $beforeStatuses = Task::query()
                ->where('project_id', $project->id)
                ->pluck('status', 'id');

            foreach (['Todo', 'Doing', 'Done'] as $status) {
                $ids = collect($columns[$status] ?? [])->values();
                foreach ($ids as $index => $taskId) {
                    Task::query()
                        ->where('project_id', $project->id)
                        ->where('id', $taskId)
                        ->update([
                            'status' => $status,
                            'position' => $index,
                        ]);

                    $before = (string) ($beforeStatuses[$taskId] ?? '');
                    if ($before !== '' && $before !== $status) {
                        $task = Task::query()->where('project_id', $project->id)->where('id', $taskId)->first();
                        if ($task) {
                            $this->logActivity($task, 'status.changed', [
                                'from' => $before,
                                'to' => $status,
                            ]);
                        }
                    }
                }
            }
        });

        return response()->json(['ok' => true]);
    }
}
