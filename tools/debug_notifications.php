<?php

declare(strict_types=1);

// Usage:
//   php -c .\php.local.ini .\tools\debug_notifications.php --name="Maruthu Green"
//   .\tools\php-local.ps1 .\tools\debug_notifications.php --email="user@example.com"

require __DIR__ . '/../vendor/autoload.php';

$app = require __DIR__ . '/../bootstrap/app.php';
$kernel = $app->make(Illuminate\Contracts\Console\Kernel::class);
$kernel->bootstrap();

function argValue(string $key): ?string
{
    global $argv;
    foreach ($argv as $arg) {
        if (str_starts_with($arg, $key . '=')) {
            return (string) substr($arg, strlen($key) + 1);
        }
    }
    return null;
}

$email = argValue('--email');
$name = argValue('--name');

$userQuery = \App\Models\User::query();
if ($email) {
    $userQuery->where('email', $email);
} elseif ($name) {
    $userQuery->where('name', $name);
} else {
    fwrite(STDERR, "Provide --email=... or --name=...\n");
    exit(2);
}

$user = $userQuery->first();
if (!$user) {
    fwrite(STDERR, "User not found.\n");
    exit(1);
}

echo "User: #{$user->id} {$user->name} <{$user->email}>\n";
echo "Roles: " . implode(', ', $user->getRoleNames()->all()) . "\n";

$commentType = \App\Notifications\TaskCommentAddedNotification::class;

$unreadTotal = $user->unreadNotifications()->count();
$unreadComment = $user->unreadNotifications()->where('type', $commentType)->count();

echo "Unread total notifications: {$unreadTotal}\n";
echo "Unread comment notifications: {$unreadComment}\n";

$latest = $user->notifications()->latest()->limit(5)->get();
echo "Latest 5 notifications:\n";
foreach ($latest as $n) {
    $data = is_array($n->data) ? $n->data : (array) $n->data;
    $taskId = $data['task_id'] ?? null;
    $title = $data['task_title'] ?? null;
    echo "- {$n->id} type={$n->type} read_at=" . ($n->read_at ? $n->read_at->toDateTimeString() : 'null') . " created_at=" . ($n->created_at ? $n->created_at->toDateTimeString() : 'null');
    if ($taskId !== null) {
        echo " task_id={$taskId}";
    }
    if ($title !== null) {
        echo " task_title=\"" . str_replace('"', '\\"', (string) $title) . "\"";
    }
    echo "\n";
}

// If we have any notification with task_id, check whether user is in that team.
$anyTaskId = null;
foreach ($latest as $n) {
    $data = is_array($n->data) ? $n->data : (array) $n->data;
    if (!empty($data['task_id'])) {
        $anyTaskId = (int) $data['task_id'];
        break;
    }
}

if ($anyTaskId) {
    $task = \App\Models\Task::query()->find($anyTaskId);
    if ($task) {
        $teamId = (int) (\App\Models\Project::query()->where('id', $task->project_id)->value('team_id') ?? 0);
        $ownerId = (int) (\App\Models\Team::query()->where('id', $teamId)->value('user_id') ?? 0);
        $memberIds = \Illuminate\Support\Facades\DB::table('team_user')->where('team_id', $teamId)->pluck('user_id')->map(fn ($v) => (int) $v)->all();
        $inTeam = ((int) $user->id === $ownerId) || in_array((int) $user->id, $memberIds, true);

        echo "\nTeam membership check for task {$anyTaskId}:\n";
        echo "- team_id={$teamId} owner_id={$ownerId}\n";
        echo "- team_user_ids=" . json_encode($memberIds) . "\n";
        echo "- user_in_team=" . ($inTeam ? 'yes' : 'no') . "\n";
    }
}
