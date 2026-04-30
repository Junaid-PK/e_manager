<?php

namespace Tests\Feature;

use App\Livewire\BankAccounts\BankAccountPage;
use App\Models\BankAccount;
use App\Models\Permission;
use App\Models\Role;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Livewire\Livewire;
use Tests\TestCase;

class AdminVisibilityScopeTest extends TestCase
{
    use RefreshDatabase;

    public function test_user_with_admin_permission_set_can_see_bank_accounts_from_other_users(): void
    {
        $permissionNames = [
            'dashboard.view',
            'invoices.view', 'invoices.create', 'invoices.edit', 'invoices.delete', 'invoices.export',
            'movements.view', 'movements.create', 'movements.edit', 'movements.delete', 'movements.export',
            'bank_accounts.view', 'bank_accounts.create', 'bank_accounts.edit', 'bank_accounts.delete',
            'expenses.view', 'expenses.create', 'expenses.edit', 'expenses.delete', 'expenses.export', 'expenses.access_all',
            'credit_lines.view', 'credit_lines.create', 'credit_lines.edit', 'credit_lines.delete',
            'companies_clients.view', 'companies_clients.create', 'companies_clients.edit', 'companies_clients.delete',
            'reports.view',
            'reminders.view', 'reminders.create', 'reminders.edit', 'reminders.delete',
            'settings.view', 'settings.edit',
            'users.view', 'users.create', 'users.edit', 'users.delete',
            'roles.view',
        ];

        $permissionIds = collect($permissionNames)
            ->map(fn (string $name) => Permission::firstOrCreate(['name' => $name])->id)
            ->all();

        $adminRole = Role::create(['name' => 'admin']);
        $adminRole->permissions()->sync($permissionIds);

        $opsRole = Role::create(['name' => 'ops-admin']);
        $opsRole->permissions()->sync($permissionIds);

        $viewerPermission = Permission::firstOrCreate(['name' => 'bank_accounts.view']);
        $viewerRole = Role::create(['name' => 'viewer']);
        $viewerRole->permissions()->sync([$viewerPermission->id]);

        $adminLikeUser = User::factory()->create();
        $regularUser = User::factory()->create();

        $adminLikeUser->roles()->attach($opsRole->id);
        $regularUser->roles()->attach($viewerRole->id);

        BankAccount::create([
            'user_id' => $adminLikeUser->id,
            'bank_name' => 'Own Bank',
            'account_number' => '1111222233334444',
            'holder_name' => 'Admin Like',
            'currency' => 'EUR',
            'initial_balance' => 100,
            'current_balance' => 100,
        ]);

        BankAccount::create([
            'user_id' => $regularUser->id,
            'bank_name' => 'Other User Bank',
            'account_number' => '5555666677778888',
            'holder_name' => 'Regular User',
            'currency' => 'EUR',
            'initial_balance' => 200,
            'current_balance' => 200,
        ]);

        $this->actingAs($adminLikeUser);

        Livewire::test(BankAccountPage::class)
            ->assertSee('Own Bank')
            ->assertSee('Other User Bank');
    }
}
