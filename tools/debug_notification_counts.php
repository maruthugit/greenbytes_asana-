<?php

use App\Models\User;
use App\Notifications\TaskAssignedNotification;
use App\Notifications\TaskCommentAddedNotification;
use Illuminate\Support\Facades\DB;

require __DIR__ . '/../vendor/autoload.php';

$app = require __DIR__ . '/../bootstrap/app.php';
$kernel = $app->make(Illuminate\Contracts\Console\Kernel::class);
$kernel->bootstrap();

function countByType(string $type): int
{
    return (int) DB::table('notifications')->where('type', $type)->count();
}

$assignedType = TaskAssignedNotification::class;
$commentType = TaskCommentAddedNotification::class;

echo "Total notifications\n";
echo "- TaskAssignedNotification: " . countByType($assignedType) . PHP_EOL;
echo "- TaskCommentAddedNotification: " . countByType($commentType) . PHP_EOL;

echo PHP_EOL;

$users = User::query()->whereIn('name', ['Maruthu Green', 'Maruthu Admin'])->get()->keyBy('name');
foreach (['Maruthu Admin', 'Maruthu Green'] as $name) {
    $u = $users->get($name);
    if (!$u) {
        echo "User not found: {$name}" . PHP_EOL;
        continue;
    }

    $assignedUnread = $u->unreadNotifications()->where('type', $assignedType)->count();
    $commentUnread = $u->unreadNotifications()->where('type', $commentType)->count();

    echo "{$name} (#{$u->id}) unread\n";
    echo "- assigned: {$assignedUnread}\n";
    echo "- comments: {$commentUnread}\n";
    echo PHP_EOL;
}
