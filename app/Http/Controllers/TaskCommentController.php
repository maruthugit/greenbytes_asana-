<?php

namespace App\Http\Controllers;

use App\Models\Project;
use App\Models\Task;
use App\Models\TaskAttachment;
use App\Models\TaskComment;
use App\Models\Team;
use App\Rules\AllowedTaskAttachment;
use Illuminate\Http\Request;
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
