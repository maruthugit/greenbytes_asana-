<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Team;
use App\Models\User;
use Illuminate\Http\Request;
use Spatie\Permission\PermissionRegistrar;
use Spatie\Permission\Models\Permission;
use Spatie\Permission\Models\Role;

class UserManagementController extends Controller
{
    public function index()
    {
        $users = User::query()
            ->with(['roles', 'permissions'])
            ->orderBy('name')
            ->get();

        $roles = Role::query()->orderBy('name')->get();

        $teams = Team::query()->orderBy('name')->get();

        return view('admin.users.index', [
            'users' => $users,
            'roles' => $roles,
            'teams' => $teams,
        ]);
    }

    public function store(Request $request)
    {
        $data = $request->validate([
            'name' => ['required', 'string', 'max:255'],
            'email' => ['required', 'email', 'max:255', 'unique:users,email'],
            'password' => ['required', 'string', 'min:6'],
            'role' => ['nullable', 'string'],
            'teams' => ['nullable', 'array'],
            'teams.*' => ['integer', 'exists:teams,id'],
        ]);

        $user = User::create([
            'name' => $data['name'],
            'email' => $data['email'],
            'password' => $data['password'],
        ]);

        if (!empty($data['role'])) {
            $user->syncRoles([$data['role']]);
        }

        $teamIds = $data['teams'] ?? [];
        if (!empty($teamIds)) {
            // Default the pivot role to 'member' (team_user.role is separate from Spatie roles).
            $user->teams()->syncWithPivotValues($teamIds, ['role' => 'member']);
        }

        app(PermissionRegistrar::class)->forgetCachedPermissions();

        return redirect()->route('admin.users.index');
    }

    public function edit(User $user)
    {
        $roles = Role::query()->orderBy('name')->get();
        $permissions = Permission::query()
            ->whereNotIn('name', ['performance.view'])
            ->orderBy('name')
            ->get();
        $teams = Team::query()->orderBy('name')->get();

        return view('admin.users.edit', [
            'user' => $user->load(['roles', 'permissions', 'teams']),
            'roles' => $roles,
            'permissions' => $permissions,
            'teams' => $teams,
        ]);
    }

    public function update(Request $request, User $user)
    {
        $data = $request->validate([
            'name' => ['required', 'string', 'max:255'],
            'email' => ['required', 'email', 'max:255', 'unique:users,email,' . $user->id],
            'password' => ['nullable', 'string', 'min:6'],
            'roles' => ['nullable', 'array'],
            'roles.*' => ['string'],
            'permissions' => ['nullable', 'array'],
            'permissions.*' => ['string'],
            'teams' => ['nullable', 'array'],
            'teams.*' => ['integer', 'exists:teams,id'],
        ]);

        $user->forceFill([
            'name' => $data['name'],
            'email' => $data['email'],
        ]);

        if (!empty($data['password'])) {
            $user->password = $data['password'];
        }

        $user->save();

        $user->syncRoles($data['roles'] ?? []);

        $requestedPermissions = collect($data['permissions'] ?? [])->values();

        // Performance is admin-only; never grant it to non-admins.
        if (!$user->hasRole('admin')) {
            $requestedPermissions = $requestedPermissions->reject(fn ($p) => $p === 'performance.view');
        }

        $user->syncPermissions($requestedPermissions->all());

        $teamIds = $data['teams'] ?? [];
        $user->teams()->syncWithPivotValues($teamIds, ['role' => 'member']);

        app(PermissionRegistrar::class)->forgetCachedPermissions();

        return redirect()->route('admin.users.edit', $user);
    }
}
