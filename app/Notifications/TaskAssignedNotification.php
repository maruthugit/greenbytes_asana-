<?php

namespace App\Notifications;

use App\Models\Task;
use Illuminate\Bus\Queueable;
use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Notifications\Notification;

class TaskAssignedNotification extends Notification
{
    use Queueable;

    public function __construct(
        public readonly Task $task,
        public readonly string $assignerName,
    ) {
    }

    public function via(object $notifiable): array
    {
        return ['database', 'mail'];
    }

    public function toMail(object $notifiable): MailMessage
    {
        return (new MailMessage)
            ->subject('New task assigned: ' . (string) $this->task->title)
            ->greeting('Hi ' . (string) ($notifiable->name ?? ''))
            ->line($this->assignerName . ' assigned you a task.')
            ->line('Task: ' . (string) $this->task->title)
            ->action('View task', url('/tasks/' . $this->task->id))
            ->line('You’re receiving this because you are a member of the team.');
    }

    public function toArray(object $notifiable): array
    {
        return [
            'task_id' => (int) $this->task->id,
            'task_title' => (string) $this->task->title,
            'assigner' => (string) $this->assignerName,
        ];
    }
}
