<?php

use App\Models\Task;
use App\Models\User;
use App\Notifications\TaskAssignedNotification;
use Illuminate\Support\Facades\DB;

require __DIR__ . '/../vendor/autoload.php';

$app = require __DIR__ . '/../bootstrap/app.php';
$kernel = $app->make(Illuminate\Contracts\Console\Kernel::class);
$kernel->bootstrap();

$assignee = User::query()->where('name', 'Maruthu Green')->first();
if (!$assignee) {
    echo "Maruthu Green not found\n";
    exit(1);
}

$type = TaskAssignedNotification::class;

$tasks = Task::query()
    ->where('assigned_to', $assignee->id)
    ->orderByDesc('updated_at')
    ->limit(10)
    ->get(['id', 'title', 'assigned_to', 'created_by', 'created_at', 'updated_at']);

echo "Latest tasks assigned to Maruthu Green (#{$assignee->id})\n";
foreach ($tasks as $t) {
    $notifCount = (int) DB::table('notifications')
        ->where('notifiable_type', User::class)
        ->where('notifiable_id', $assignee->id)
        ->where('type', $type)
        ->where('data->task_id', (int) $t->id)
        ->count();

    echo "- task_id={$t->id} updated_at={$t->updated_at} title=\"{$t->title}\" assigned_notifs={$notifCount}\n";
}
