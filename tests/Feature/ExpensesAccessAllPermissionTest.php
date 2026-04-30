<?php

namespace Tests\Feature;

use App\Livewire\Expenses\ExpensePage;
use App\Models\Expense;
use App\Models\Permission;
use App\Models\Role;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Livewire\Livewire;
use Tests\TestCase;

class ExpensesAccessAllPermissionTest extends TestCase
{
    use RefreshDatabase;

    private function makeUserWithPermissions(array $permissions): User
    {
        $role = Role::create(['name' => 'role-'.uniqid()]);
        foreach ($permissions as $permissionName) {
            $permission = Permission::firstOrCreate(['name' => $permissionName]);
            $role->permissions()->syncWithoutDetaching([$permission->id]);
        }

        $user = User::factory()->create();
        $user->roles()->attach($role->id);

        return $user;
    }

    public function test_expenses_page_without_access_all_only_shows_owned_expenses(): void
    {
        $user = $this->makeUserWithPermissions(['expenses.view']);
        $otherUser = User::factory()->create();

        Expense::create([
            'user_id' => $user->id,
            'category' => 'Restaurante',
            'description' => 'Visible own expense',
            'amount' => 10,
            'date' => '2026-04-01',
            'payment_method' => 'cash',
        ]);

        Expense::create([
            'user_id' => $otherUser->id,
            'category' => 'Dieta',
            'description' => 'Hidden other expense',
            'amount' => 20,
            'date' => '2026-04-02',
            'payment_method' => 'cash',
        ]);

        $this->actingAs($user);

        Livewire::test(ExpensePage::class)
            ->assertSee('Visible own expense')
            ->assertDontSee('Hidden other expense');
    }

    public function test_expenses_page_with_access_all_shows_expenses_from_other_users(): void
    {
        $user = $this->makeUserWithPermissions(['expenses.view', 'expenses.access_all']);
        $otherUser = User::factory()->create();

        Expense::create([
            'user_id' => $user->id,
            'category' => 'Restaurante',
            'description' => 'Visible own expense',
            'amount' => 10,
            'date' => '2026-04-01',
            'payment_method' => 'cash',
        ]);

        Expense::create([
            'user_id' => $otherUser->id,
            'category' => 'Dieta',
            'description' => 'Visible other expense',
            'amount' => 20,
            'date' => '2026-04-02',
            'payment_method' => 'cash',
        ]);

        $this->actingAs($user);

        Livewire::test(ExpensePage::class)
            ->set('filterUserId', '')
            ->assertSee('Visible own expense')
            ->assertSee('Visible other expense');
    }

    public function test_expenses_page_defaults_user_filter_to_logged_in_user(): void
    {
        $user = $this->makeUserWithPermissions(['expenses.view', 'expenses.access_all']);
        $otherUser = User::factory()->create();

        Expense::create([
            'user_id' => $user->id,
            'category' => 'Restaurante',
            'description' => 'Visible own expense',
            'amount' => 10,
            'date' => '2026-04-01',
            'payment_method' => 'cash',
        ]);

        Expense::create([
            'user_id' => $otherUser->id,
            'category' => 'Dieta',
            'description' => 'Hidden by default other expense',
            'amount' => 20,
            'date' => '2026-04-02',
            'payment_method' => 'cash',
        ]);

        $this->actingAs($user);

        Livewire::test(ExpensePage::class)
            ->assertSet('filterUserId', (string) $user->id)
            ->assertSee('Visible own expense')
            ->assertDontSee('Hidden by default other expense');
    }
}
