<?php
namespace App\Http\Controllers;

use App\Models\Team;
use Illuminate\Http\Request;

class TeamController extends Controller
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

    private function findAccessibleTeamOrFail(Team $team): Team
    {
        $teamIds = $this->accessibleTeamIds();

        return Team::query()
            ->where('id', $team->id)
            ->whereIn('id', $teamIds)
            ->firstOrFail();
    }

    public function index()
    {
        $teamIds = $this->accessibleTeamIds();

        $teams = Team::query()
            ->whereIn('id', $teamIds)
            ->latest()
            ->get();

        return view('teams.index', ['teams' => $teams]);
    }

    public function store(Request $request)
    {
        if (!auth()->user()?->can('teams.manage') && !auth()->user()?->can('teams.create')) {
            abort(403);
        }

        $data = $request->validate([
            'name' => ['required', 'string', 'max:255'],
        ]);

        Team::create([
            'name' => $data['name'],
            'user_id' => auth()->id(),
        ]);

        return back()->with('toast', ['type' => 'success', 'message' => 'Team created.']);
    }

    public function edit(Team $team)
    {
        if (!auth()->user()?->can('teams.manage') && !auth()->user()?->can('teams.update')) {
            abort(403);
        }

        $team = $this->findAccessibleTeamOrFail($team);

        return view('teams.edit', ['team' => $team]);
    }

    public function update(Request $request, Team $team)
    {
        if (!auth()->user()?->can('teams.manage') && !auth()->user()?->can('teams.update')) {
            abort(403);
        }

        $team = $this->findAccessibleTeamOrFail($team);

        $data = $request->validate([
            'name' => ['required', 'string', 'max:255'],
        ]);

        $team->update([
            'name' => $data['name'],
        ]);

        return redirect()->route('teams.index')->with('toast', ['type' => 'success', 'message' => 'Team updated.']);
    }

    public function destroy(Request $request, Team $team)
    {
        if (!auth()->user()?->hasRole('admin')) {
            abort(403);
        }

        $team = $this->findAccessibleTeamOrFail($team);

        if ($team->projects()->exists()) {
            return redirect()->route('teams.index')->with('toast', [
                'type' => 'error',
                'message' => 'Cannot delete a team that still has projects. Delete/move the projects first.',
            ]);
        }

        $team->invitations()->delete();
        $team->members()->detach();
        $team->delete();

        return redirect()->route('teams.index')->with('toast', ['type' => 'success', 'message' => 'Team deleted.']);
    }
}
