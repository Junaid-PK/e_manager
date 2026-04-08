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
        'dashboard'         => ['view'],
        'invoices'          => ['view', 'create', 'edit', 'delete', 'export'],
        'movements'         => ['view', 'create', 'edit', 'delete', 'export'],
        'bank_accounts'     => ['view', 'create', 'edit', 'delete'],
        'expenses'          => ['view', 'create', 'edit', 'delete'],
        'credit_lines'      => ['view', 'create', 'edit', 'delete'],
        'companies_clients' => ['view', 'create', 'edit', 'delete'],
        'reports'           => ['view'],
        'reminders'         => ['view', 'create', 'edit', 'delete'],
        'settings'          => ['view', 'edit'],
        'users'             => ['view', 'create', 'edit', 'delete'],
        'roles'             => ['view'],
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
