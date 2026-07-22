<?php

namespace Tests\Feature;

use App\Livewire\BankAccounts\BankAccountPage;
use App\Livewire\Movements\MovementPage;
use App\Models\BankAccount;
use App\Models\BankMovement;
use App\Models\Permission;
use App\Models\Role;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Livewire\Livewire;
use Tests\TestCase;

class BankingUserFilterTest extends TestCase
{
    use RefreshDatabase;

    private function userWithPermissions(array $permissionNames): User
    {
        $role = Role::create(['name' => 'banking-filter-'.uniqid()]);

        foreach ($permissionNames as $permissionName) {
            $permission = Permission::firstOrCreate(['name' => $permissionName]);
            $role->permissions()->syncWithoutDetaching([$permission->id]);
        }

        $user = User::factory()->create();
        $user->roles()->attach($role);

        return $user;
    }

    private function accountFor(User $user, string $name): BankAccount
    {
        return BankAccount::create([
            'user_id' => $user->id,
            'bank_name' => $name,
            'account_number' => fake()->unique()->numerify('################'),
            'holder_name' => $user->name,
            'currency' => 'EUR',
            'initial_balance' => 0,
            'current_balance' => 0,
        ]);
    }

    private function movementFor(User $user, BankAccount $account, string $concept): BankMovement
    {
        return BankMovement::create([
            'user_id' => $user->id,
            'bank_account_id' => $account->id,
            'date' => '2026-07-22',
            'type' => 'transfer',
            'concept' => $concept,
            'deposit' => 100,
        ]);
    }

    public function test_access_all_user_can_filter_bank_accounts_by_owner(): void
    {
        $viewer = $this->userWithPermissions(['bank_accounts.view', 'bank_accounts.access_all']);
        $otherUser = User::factory()->create();
        $this->accountFor($viewer, 'Own account filter test');
        $this->accountFor($otherUser, 'Other account filter test');

        $this->actingAs($viewer);

        Livewire::test(BankAccountPage::class)
            ->assertSet('filterUserId', '')
            ->assertSeeHtml('wire:model.live="filterUserId"')
            ->assertSee('Own account filter test')
            ->assertSee('Other account filter test')
            ->set('filterUserId', (string) $otherUser->id)
            ->assertDontSee('Own account filter test')
            ->assertSee('Other account filter test');
    }

    public function test_access_all_user_can_filter_movements_and_accounts_by_owner(): void
    {
        $viewer = $this->userWithPermissions(['movements.view', 'movements.access_all']);
        $otherUser = User::factory()->create();
        $ownAccount = $this->accountFor($viewer, 'Own movement account');
        $otherAccount = $this->accountFor($otherUser, 'Other movement account');
        $this->movementFor($viewer, $ownAccount, 'Own movement filter test');
        $this->movementFor($otherUser, $otherAccount, 'Other movement filter test');

        $this->actingAs($viewer);

        Livewire::test(MovementPage::class)
            ->assertSet('filterUserId', '')
            ->assertSeeHtml('wire:model.live="filterUserId"')
            ->assertSee('Own movement filter test')
            ->assertSee('Other movement filter test')
            ->set('filterUserId', (string) $otherUser->id)
            ->assertSet('filterBankAccountId', '')
            ->assertDontSee('Own movement filter test')
            ->assertSee('Other movement filter test')
            ->assertDontSee('Own movement account')
            ->assertSee('Other movement account');
    }

    public function test_user_without_access_all_remains_limited_to_owned_banking_data(): void
    {
        $viewer = $this->userWithPermissions(['bank_accounts.view', 'movements.view']);
        $otherUser = User::factory()->create();
        $ownAccount = $this->accountFor($viewer, 'Visible owned bank account');
        $otherAccount = $this->accountFor($otherUser, 'Hidden other bank account');
        $this->movementFor($viewer, $ownAccount, 'Visible owned movement');
        $this->movementFor($otherUser, $otherAccount, 'Hidden other movement');

        $this->actingAs($viewer);

        Livewire::test(BankAccountPage::class)
            ->assertSet('filterUserId', (string) $viewer->id)
            ->assertDontSeeHtml('wire:model.live="filterUserId"')
            ->assertSee('Visible owned bank account')
            ->assertDontSee('Hidden other bank account');

        Livewire::test(MovementPage::class)
            ->assertSet('filterUserId', (string) $viewer->id)
            ->assertDontSeeHtml('wire:model.live="filterUserId"')
            ->assertSee('Visible owned movement')
            ->assertDontSee('Hidden other movement');
    }

    public function test_movements_can_be_filtered_to_rows_missing_a_type_or_category(): void
    {
        $viewer = $this->userWithPermissions(['movements.view']);
        $account = $this->accountFor($viewer, 'Classification filter bank');

        BankMovement::create([
            'user_id' => $viewer->id,
            'bank_account_id' => $account->id,
            'date' => '2026-07-22',
            'type' => 'transfer',
            'category' => 'Operations',
            'concept' => 'Fully assigned movement',
            'deposit' => 10,
        ]);
        BankMovement::create([
            'user_id' => $viewer->id,
            'bank_account_id' => $account->id,
            'date' => '2026-07-22',
            'type' => 'transfer',
            'category' => null,
            'concept' => 'Missing category movement',
            'deposit' => 20,
        ]);
        BankMovement::create([
            'user_id' => $viewer->id,
            'bank_account_id' => $account->id,
            'date' => '2026-07-22',
            'type' => 'other',
            'category' => 'Miscellaneous',
            'concept' => 'Missing type movement',
            'deposit' => 30,
        ]);

        $this->actingAs($viewer);

        Livewire::test(MovementPage::class)
            ->set('filterUnassigned', true)
            ->assertDontSee('Fully assigned movement')
            ->assertSee('Missing category movement')
            ->assertSee('Missing type movement')
            ->call('clearFilters')
            ->assertSet('filterUnassigned', false)
            ->assertSee('Fully assigned movement');
    }
}
