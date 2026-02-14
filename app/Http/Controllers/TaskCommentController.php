<?php

namespace App\Http\Controllers;

use App\Models\Project;
use App\Models\Task;
use App\Models\TaskAttachment;
use App\Models\TaskComment;
use App\Models\Team;
use App\Notifications\TaskCommentAddedNotification;
use App\Rules\AllowedTaskAttachment;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Mews\Purifier\Facades\Purifier;

class TaskCommentController extends Controller
{
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

    private function findAccessibleTaskOrFail(Task $task): Task
    {
        $teamIds = $this->accessibleTeamIds();

        return Task::query()
            ->where('id', $task->id)
            ->whereIn('project_id', Project::query()->whereIn('team_id', $teamIds)->select('id'))
            ->firstOrFail();
    }

    public function store(Request $request, Task $task)
    {
        $task = $this->findAccessibleTaskOrFail($task);

        $data = $request->validate([
            'body' => ['nullable', 'string', 'max:50000'],
            'attachments' => ['nullable', 'array'],
            'attachments.*' => ['file', 'max:25600', new AllowedTaskAttachment()],
        ]);

        $uploaded = $request->file('attachments', []);
        $hasUploads = is_array($uploaded) && !empty(array_filter($uploaded));

        $body = (string) ($data['body'] ?? '');
        $body = Purifier::clean($body);

        $plain = html_entity_decode(strip_tags($body), ENT_QUOTES | ENT_HTML5, 'UTF-8');
        $plain = preg_replace('/\x{00A0}/u', ' ', (string) $plain);
        $plain = trim((string) $plain);

        if ($plain === '' && !$hasUploads) {
            return back()
                ->withErrors(['body' => 'Please type a comment or attach a file.'])
                ->withInput();
        }

        $comment = TaskComment::create([
            'task_id' => $task->id,
            'user_id' => auth()->id(),
            'body' => $plain === '' ? '' : $body,
        ]);

        $teamId = (int) (Project::query()->where('id', $task->project_id)->value('team_id') ?? 0);
        if ($teamId > 0) {
            $ownerId = (int) (Team::query()->where('id', $teamId)->value('user_id') ?? 0);
            $memberIds = DB::table('team_user')->where('team_id', $teamId)->pluck('user_id')->map(fn ($v) => (int) $v);

            $recipientIds = collect([$ownerId])
                ->merge($memberIds)
                ->filter(fn ($id) => (int) $id > 0)
                ->unique()
                ->reject(fn ($id) => (int) $id === (int) auth()->id())
                ->values();

            if ($recipientIds->isNotEmpty()) {
                $users = \App\Models\User::query()->whereIn('id', $recipientIds)->get();
                foreach ($users as $user) {
                    $user->notify(new TaskCommentAddedNotification(
                        task: $task,
                        comment: $comment,
                        commenterName: (string) (auth()->user()?->name ?? 'Someone'),
                    ));
                }
            }
        }

        $createdIds = [];
        if (is_array($uploaded) && !empty($uploaded)) {
            foreach ($uploaded as $file) {
                if (!$file || !$file->isValid()) continue;

                $path = $file->store('task-attachments', 'public');
                $att = TaskAttachment::create([
                    'task_id' => $task->id,
                    'path' => $path,
                    'original_name' => (string) $file->getClientOriginalName(),
                    'mime_type' => (string) ($file->getMimeType() ?? ''),
                    'size' => $file->getSize(),
                    'created_by' => auth()->id(),
                ]);

                $createdIds[] = (int) $att->id;
            }
        }

        if (!empty($createdIds)) {
            $comment->meta = array_merge((array) ($comment->meta ?? []), [
                'attachment_ids' => array_values(array_unique($createdIds)),
            ]);
            $comment->save();

            try {
                app(TaskController::class)->logAttachmentAddedActivity($task, $createdIds);
            } catch (\Throwable $e) {
                // ignore
            }
        }

        return back();
    }
}
