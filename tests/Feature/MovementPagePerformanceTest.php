<?php

namespace Tests\Feature;

use App\Livewire\Movements\MovementPage;
use App\Models\BankAccount;
use App\Models\BankMovement;
use App\Models\MovementCategory;
use App\Models\Permission;
use App\Models\Role;
use App\Models\User;
use Database\Seeders\RolesAndPermissionsSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Livewire\Livewire;
use Tests\TestCase;

class MovementPagePerformanceTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        $this->seed(RolesAndPermissionsSeeder::class);
    }

    protected function createUserWithMovementAccess(): User
    {
        $user = User::factory()->create();
        $role = Role::firstOrCreate(['name' => 'movement-performance-test-role']);
        $permissions = Permission::query()
            ->whereIn('name', ['movements.view', 'movements.edit', 'movements.access_all'])
            ->pluck('id');
        $role->permissions()->syncWithoutDetaching($permissions);
        $user->roles()->syncWithoutDetaching([$role->id]);

        return $user;
    }

    public function test_movement_rows_do_not_render_inline_dropdown_options_until_editing(): void
    {
        $user = $this->createUserWithMovementAccess();
        $account = BankAccount::create([
            'bank_name' => 'Performance Bank',
            'account_number' => 'PK00PERF0000000001',
            'currency' => 'EUR',
            'initial_balance' => 0,
            'current_balance' => 0,
        ]);

        for ($i = 1; $i <= 30; $i++) {
            MovementCategory::create(['name' => "Category {$i}", 'sort_order' => $i]);
        }

        for ($i = 1; $i <= 100; $i++) {
            BankMovement::create([
                'bank_account_id' => $account->id,
                'date' => now()->subDays($i)->toDateString(),
                'type' => 'transfer',
                'concept' => "Movement {$i}",
                'deposit' => 10,
                'category' => 'Category 1',
                'import_source' => 'manual',
            ]);
        }

        $html = Livewire::actingAs($user)
            ->test(MovementPage::class)
            ->set('perPage', 100)
            ->html();

        $this->assertLessThan(120, substr_count($html, 'data-option-value='));
        $this->assertStringContainsString('Edit type', $html);
        $this->assertStringContainsString('Edit category', $html);
    }

    public function test_per_page_is_capped_at_100(): void
    {
        $user = $this->createUserWithMovementAccess();

        Livewire::actingAs($user)
            ->test(MovementPage::class)
            ->set('perPage', 1000)
            ->assertSet('perPage', 100);
    }

    public function test_inline_dropdown_auto_opens_after_first_click(): void
    {
        $user = $this->createUserWithMovementAccess();
        $account = BankAccount::create([
            'bank_name' => 'Performance Bank',
            'account_number' => 'PK00PERF0000000002',
            'currency' => 'EUR',
            'initial_balance' => 0,
            'current_balance' => 0,
        ]);
        $movement = BankMovement::create([
            'bank_account_id' => $account->id,
            'date' => now()->toDateString(),
            'type' => 'transfer',
            'concept' => 'Movement',
            'deposit' => 10,
            'category' => 'Office',
            'import_source' => 'manual',
        ]);

        Livewire::actingAs($user)
            ->test(MovementPage::class)
            ->call('editInlineType', $movement->id)
            ->assertSee('autoOpen: true', false);
    }
}
