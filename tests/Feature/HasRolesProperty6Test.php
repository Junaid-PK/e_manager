<?php

// Feature: roles-and-permissions, Property 6: Union of role permissions

namespace Tests\Feature;

use App\Models\Permission;
use App\Models\Role;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

/**
 * Property 6: Union of role permissions
 *
 * For any user assigned multiple roles, the effective permission set SHALL equal
 * the union of all permissions across all assigned roles.
 *
 * Validates: Requirements 4.4
 */
class HasRolesProperty6Test extends TestCase
{
    use RefreshDatabase;

    /**
     * Property 6: hasPermission returns true iff the permission appears in at least one assigned role.
     *
     * Runs 100 iterations with randomized roles and permissions.
     */
    public function test_hasPermission_equals_union_of_all_assigned_role_permissions(): void
    {
        $modules = ['dashboard', 'invoices', 'movements', 'bank_accounts', 'expenses', 'credit_lines', 'companies_clients', 'reports', 'reminders', 'settings', 'users'];
        $actions = ['view', 'create', 'edit', 'delete', 'export'];

        // Build the full permission name pool
        $allPermissionNames = [];
        foreach ($modules as $module) {
            foreach ($actions as $action) {
                $allPermissionNames[] = "{$module}.{$action}";
            }
        }

        for ($iteration = 0; $iteration < 100; $iteration++) {
            // Create a fresh user with no roles
            $user = User::factory()->create();

            // Pick a random number of roles (1–5)
            $roleCount = rand(1, 5);

            // Track which permissions are assigned across all roles (the expected union)
            $unionPermissions = [];

            for ($r = 0; $r < $roleCount; $r++) {
                $role = Role::create(['name' => "role_{$iteration}_{$r}_" . uniqid()]);

                // Assign a random subset of permissions to this role
                $permCount = rand(0, count($allPermissionNames));
                $chosenNames = array_slice(
                    $allPermissionNames,
                    rand(0, count($allPermissionNames) - 1),
                    $permCount
                );
                // Ensure uniqueness within this role
                $chosenNames = array_unique($chosenNames);

                $permIds = [];
                foreach ($chosenNames as $permName) {
                    $perm = Permission::firstOrCreate(['name' => $permName]);
                    $permIds[] = $perm->id;
                    $unionPermissions[$permName] = true;
                }

                $role->permissions()->sync($permIds);
                $user->roles()->attach($role->id);
            }

            // Reload the user with fresh relationships
            $user->load('roles.permissions');

            // Assert: every permission in the union returns true
            foreach (array_keys($unionPermissions) as $permName) {
                $this->assertTrue(
                    $user->hasPermission($permName),
                    "Iteration {$iteration}: expected hasPermission('{$permName}') to be true (it is in at least one assigned role)"
                );
            }

            // Assert: permissions NOT in any assigned role return false
            $nonAssignedPermissions = array_diff($allPermissionNames, array_keys($unionPermissions));
            foreach ($nonAssignedPermissions as $permName) {
                $this->assertFalse(
                    $user->hasPermission($permName),
                    "Iteration {$iteration}: expected hasPermission('{$permName}') to be false (it is in no assigned role)"
                );
            }

            // Clean up for next iteration to avoid unique constraint issues with users
            $user->roles()->detach();
            $user->delete();
        }
    }
}
