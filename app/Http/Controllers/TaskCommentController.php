<?php

namespace App\Http\Controllers;

use App\Models\Project;
use App\Models\Task;
use App\Models\TaskComment;
use App\Models\Team;
use Illuminate\Http\Request;

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
            'body' => ['required', 'string', 'max:2000'],
        ]);

        TaskComment::create([
            'task_id' => $task->id,
            'user_id' => auth()->id(),
            'body' => $data['body'],
        ]);

        return back();
    }
}
