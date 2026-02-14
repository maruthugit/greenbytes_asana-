<?php

namespace App\Notifications;

use App\Models\Task;
use App\Models\TaskComment;
use Illuminate\Bus\Queueable;
use Illuminate\Notifications\Notification;

class TaskCommentAddedNotification extends Notification
{
    use Queueable;

    public function __construct(
        public readonly Task $task,
        public readonly TaskComment $comment,
        public readonly string $commenterName,
    ) {
    }

    public function via(object $notifiable): array
    {
        return ['database'];
    }

    public function toArray(object $notifiable): array
    {
        $body = (string) ($this->comment->body ?? '');
        $body = trim(preg_replace('/\s+/u', ' ', strip_tags($body)) ?? '');

        return [
            'task_id' => (int) $this->task->id,
            'task_title' => (string) $this->task->title,
            'comment_id' => (int) $this->comment->id,
            'commenter' => (string) $this->commenterName,
            'excerpt' => mb_substr($body, 0, 140),
        ];
    }
}
