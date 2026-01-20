<?php

namespace Database\Seeders;

use App\Models\User;
use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;
use Spatie\Permission\Models\Permission;
use Spatie\Permission\Models\Role;
use Spatie\Permission\PermissionRegistrar;

class DatabaseSeeder extends Seeder
{
    use WithoutModelEvents;

    /**
     * Seed the application's database.
     */
    public function run(): void
    {
        // User::factory(10)->create();

        app(PermissionRegistrar::class)->forgetCachedPermissions();

        $permissions = [
            'users.view',
            'users.manage',
            'teams.view',
            'teams.manage',
            'projects.view',
            'projects.manage',
            'tasks.view',
            'tasks.manage',
            'comments.view',
            'comments.manage',
            'performance.view',
        ];

        foreach ($permissions as $permissionName) {
            Permission::findOrCreate($permissionName, 'web');
        }

        $permissionModels = Permission::query()->whereIn('name', $permissions)->get();

        $adminRole = Role::findOrCreate('admin', 'web');
        $managerRole = Role::findOrCreate('manager', 'web');
        $memberRole = Role::findOrCreate('member', 'web');
		$viewerRole = Role::findOrCreate('viewer', 'web');

        $adminRole->syncPermissions($permissionModels);

        $managerRole->syncPermissions(Permission::query()->whereIn('name', [
            'teams.view',
            'teams.manage',
            'projects.view',
            'projects.manage',
            'tasks.view',
            'tasks.manage',
            'comments.view',
            'comments.manage',
            'performance.view',
        ])->get());

        $memberRole->syncPermissions(Permission::query()->whereIn('name', [
            'teams.view',
            'projects.view',
            'tasks.view',
            'tasks.manage',
            'comments.view',
            'comments.manage',
            'performance.view',
        ])->get());

		$viewerRole->syncPermissions(Permission::query()->whereIn('name', [
			'teams.view',
			'projects.view',
			'tasks.view',
			'comments.view',
			'performance.view',
		])->get());

        $admin = User::updateOrCreate(
            ['email' => 'maruthu@tmgrocer.com'],
            ['name' => 'Maruthu', 'password' => '123456']
        );

        $admin->syncRoles(['admin']);

        app(PermissionRegistrar::class)->forgetCachedPermissions();
    }
}
