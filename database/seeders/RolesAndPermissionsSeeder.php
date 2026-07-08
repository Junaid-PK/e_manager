<?php

namespace Database\Seeders;

use App\Models\Permission;
use App\Models\Role;
use App\Models\User;
use Illuminate\Database\Seeder;

class RolesAndPermissionsSeeder extends Seeder
{
    /**
     * Permission matrix: module => actions
     */
    private array $matrix = [
        'dashboard' => ['view', 'access_all'],
        'invoices' => ['view', 'create', 'edit', 'delete', 'export', 'access_all', 'payment_summary', 'retention'],
        'movements' => ['view', 'create', 'edit', 'delete', 'export', 'access_all'],
        'bank_accounts' => ['view', 'create', 'edit', 'delete', 'access_all'],
        'expenses' => ['view', 'create', 'edit', 'delete', 'export', 'access_all'],
        'credit_lines' => ['view', 'create', 'edit', 'delete', 'access_all'],
        'companies_clients' => ['view', 'create', 'edit', 'delete', 'access_all'],
        'reports' => ['view', 'access_all'],
        'reminders' => ['view', 'create', 'edit', 'delete', 'access_all'],
        'settings' => ['view', 'edit', 'access_all'],
        'users' => ['view', 'create', 'edit', 'delete', 'access_all'],
        'roles' => ['view', 'access_all'],
        'workers' => ['view', 'create', 'edit', 'delete', 'export', 'access_all'],
        'monthly_periods' => ['view', 'create', 'edit', 'delete', 'export', 'access_all'],
        'project_months' => ['view', 'create', 'edit', 'delete', 'export', 'access_all'],
        'project_invoices' => ['view', 'create', 'edit', 'delete', 'export', 'access_all'],
        'project_expenses' => ['view', 'create', 'edit', 'delete', 'export', 'access_all'],
        'worker_monthly_summaries' => ['view', 'create', 'edit', 'delete', 'export', 'access_all'],
        'worker_project_entries' => ['view', 'create', 'edit', 'delete', 'export', 'access_all'],
        'period_dashboard' => ['view', 'access_all'],
    ];

    public function run(): void
    {
        // 1. Seed all permissions
        $allPermissionIds = [];
        $viewPermissionIds = [];

        foreach ($this->matrix as $module => $actions) {
            foreach ($actions as $action) {
                $permission = Permission::firstOrCreate(['name' => "{$module}.{$action}"]);
                $allPermissionIds[] = $permission->id;

                if ($action === 'view') {
                    $viewPermissionIds[] = $permission->id;
                }
            }
        }

        // 2. Seed admin role with all permissions
        $adminRole = Role::firstOrCreate(['name' => 'admin']);
        $adminRole->permissions()->sync($allPermissionIds);

        // 3. Seed viewer role with only view permissions
        $viewerRole = Role::firstOrCreate(['name' => 'viewer']);
        $viewerRole->permissions()->sync($viewPermissionIds);

        // 4. Assign admin role to first user if no admin exists
        $adminRoleHasUsers = $adminRole->users()->exists();

        if (! $adminRoleHasUsers) {
            $firstUser = User::first();
            if ($firstUser) {
                $firstUser->roles()->syncWithoutDetaching([$adminRole->id]);
            }
        }
    }
}
