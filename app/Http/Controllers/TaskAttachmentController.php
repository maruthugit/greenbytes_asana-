<?php

namespace App\Http\Controllers;

use App\Models\Task;
use App\Models\TaskAttachment;
use App\Rules\AllowedTaskAttachment;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;

class TaskAttachmentController extends Controller
{
    public function store(Request $request, Task $task)
    {
        $task = app(TaskController::class)->findAccessibleTaskOrFail($task);

        $data = $request->validate([
            'attachments' => ['required', 'array'],
            'attachments.*' => ['file', 'max:8192', new AllowedTaskAttachment()],
        ]);

        $uploaded = $request->file('attachments', []);

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

            if (!empty($createdIds)) {
                app(TaskController::class)->logAttachmentAddedActivity($task, $createdIds);
            }
        }

        return back()->with('toast', ['type' => 'success', 'message' => 'Attachment added.']);
    }

    public function show(Task $task, TaskAttachment $attachment)
    {
        $task = app(TaskController::class)->findAccessibleTaskOrFail($task);

        abort_unless((int) $attachment->task_id === (int) $task->id, 404);

        $path = (string) $attachment->path;
        if ($path === '' || !Storage::disk('public')->exists($path)) {
            abort(404);
        }

        return Storage::disk('public')->response($path);
    }

    public function download(Task $task, TaskAttachment $attachment)
    {
        $task = app(TaskController::class)->findAccessibleTaskOrFail($task);

        abort_unless((int) $attachment->task_id === (int) $task->id, 404);

        $path = (string) $attachment->path;
        if ($path === '' || !Storage::disk('public')->exists($path)) {
            abort(404);
        }

        $name = $attachment->original_name ?: basename($path);

        return Storage::disk('public')->download($path, $name);
    }

    public function destroy(Request $request, Task $task, TaskAttachment $attachment)
    {
        $task = app(TaskController::class)->findAccessibleTaskOrFail($task);

        abort_unless((int) $attachment->task_id === (int) $task->id, 404);

        $path = (string) $attachment->path;
        try {
            if ($path !== '' && Storage::disk('public')->exists($path)) {
                Storage::disk('public')->delete($path);
            }
        } catch (\Throwable $e) {
            // Best-effort delete
        }

        $attachment->delete();

        try {
            app(TaskController::class)->logAttachmentRemovedActivity($task, [
                'attachment_id' => (int) $attachment->id,
                'name' => (string) ($attachment->original_name ?: ''),
            ]);
        } catch (\Throwable $e) {
            // ignore
        }

        return back()->with('toast', ['type' => 'success', 'message' => 'Attachment deleted.']);
    }
}
