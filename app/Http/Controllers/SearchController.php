<?php

namespace App\Http\Controllers;

use App\Models\Project;
use App\Models\Task;
use App\Models\Team;
use Illuminate\Http\Request;

class SearchController extends Controller
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

    public function index(Request $request)
    {
        $q = trim((string) $request->query('q', ''));

        $teamIds = $this->accessibleTeamIds();

        $projectsQuery = Project::query()
            ->whereIn('team_id', $teamIds)
            ->with('team');

        $tasksQuery = Task::query()
            ->whereIn('project_id', Project::query()->whereIn('team_id', $teamIds)->select('id'))
            ->with(['project.team', 'assignee']);

        if ($q !== '') {
            $projectsQuery->where('name', 'like', "%{$q}%");
            $tasksQuery->where('title', 'like', "%{$q}%");
        } else {
            $projectsQuery->whereRaw('1=0');
            $tasksQuery->whereRaw('1=0');
        }

        $projects = $projectsQuery->limit(20)->get();
        $tasks = $tasksQuery->latest()->limit(20)->get();

        return view('search.index', [
            'q' => $q,
            'projects' => $projects,
            'tasks' => $tasks,
        ]);
    }
}
