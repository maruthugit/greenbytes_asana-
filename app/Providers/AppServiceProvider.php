<?php

namespace App\Providers;

use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\ServiceProvider;
use Spatie\Permission\PermissionRegistrar;

class AppServiceProvider extends ServiceProvider
{
    /**
     * Register any application services.
     */
    public function register(): void
    {
        //
    }

    /**
     * Bootstrap any application services.
     */
    public function boot(): void
    {
        // Ensure base permissions exist so Admin can assign per-module access
        // (useful on hosts where running artisan seed is not convenient).
        try {
            if (!Schema::hasTable('permissions') || !Schema::hasTable('roles') || !Schema::hasTable('role_has_permissions')) {
                return;
            }

            $permissions = [
                'users.view',
                'users.manage',

                'teams.view',
                'teams.manage',
                'teams.create',
                'teams.update',
                'teams.delete',

                'projects.view',
                'projects.manage',
                'projects.create',
                'projects.update',
                'projects.delete',

                'tasks.view',
                'tasks.manage',
                'tasks.create',
                'tasks.update',
                'tasks.delete',
                'tasks.complete',
                'tasks.attachments.delete',

                'comments.view',
                'comments.manage',
                'comments.create',
                'comments.delete',

                'performance.view',
            ];

            $now = now();
            $rows = array_map(fn ($name) => [
                'name' => $name,
                'guard_name' => 'web',
                'created_at' => $now,
                'updated_at' => $now,
            ], $permissions);

            DB::table('permissions')->upsert(
                $rows,
                ['name', 'guard_name'],
                ['updated_at']
            );

            // Create a view-only role for convenience.
            DB::table('roles')->upsert([
                ['name' => 'viewer', 'guard_name' => 'web', 'created_at' => $now, 'updated_at' => $now],
            ], ['name', 'guard_name'], ['updated_at']);

            $viewerRoleId = DB::table('roles')->where('name', 'viewer')->where('guard_name', 'web')->value('id');
            if ($viewerRoleId) {
                $viewerPermNames = [
                    'teams.view',
                    'projects.view',
                    'tasks.view',
                    'comments.view',
                ];

                $viewerPermIds = DB::table('permissions')
                    ->where('guard_name', 'web')
                    ->whereIn('name', $viewerPermNames)
                    ->pluck('id');

                if ($viewerPermIds->isNotEmpty()) {
                    $rolePermRows = $viewerPermIds
                        ->map(fn ($permissionId) => ['permission_id' => $permissionId, 'role_id' => $viewerRoleId])
                        ->all();

                    DB::table('role_has_permissions')->upsert(
                        $rolePermRows,
                        ['permission_id', 'role_id'],
                        []
                    );
                }
            }

            app(PermissionRegistrar::class)->forgetCachedPermissions();
        } catch (\Throwable $e) {
            // If permissions tables aren't available yet, or DB isn't ready,
            // do not block the application boot.
        }
    }
}
