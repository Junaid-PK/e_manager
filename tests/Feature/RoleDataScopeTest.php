<?php

namespace Tests\Feature;

use App\Livewire\Invoices\InvoicePage;
use App\Livewire\Roles\RolesPage;
use App\Models\Invoice;
use App\Models\Permission;
use App\Models\Role;
use App\Models\User;
use Database\Seeders\RolesAndPermissionsSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Livewire\Livewire;
use Tests\TestCase;

class RoleDataScopeTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        $this->seed(RolesAndPermissionsSeeder::class);
    }

    private function roleWithPermissions(string $name, array $permissionNames): Role
    {
        $role = Role::create(['name' => $name]);
        $role->permissions()->sync(
            Permission::query()->whereIn('name', $permissionNames)->pluck('id')
        );

        return $role;
    }

    private function invoiceFor(User $owner, string $number): Invoice
    {
        return Invoice::factory()->create([
            'user_id' => $owner->id,
            'invoice_number' => $number,
        ]);
    }

    public function test_role_access_all_can_be_limited_to_selected_users_data(): void
    {
        $role = $this->roleWithPermissions('limited-invoice-viewer', [
            'invoices.view',
            'invoices.access_all',
        ]);
        $viewer = User::factory()->create(['name' => 'Scoped Viewer']);
        $allowedA = User::factory()->create(['name' => 'Allowed Owner A']);
        $allowedB = User::factory()->create(['name' => 'Allowed Owner B']);
        $blocked = User::factory()->create(['name' => 'Blocked Owner']);
        $viewer->roles()->attach($role);
        $role->syncAccessibleUsersForModule('invoices', [$allowedA->id, $allowedB->id]);

        $this->invoiceFor($viewer, 'OWN-SCOPE-INVOICE');
        $this->invoiceFor($allowedA, 'ALLOWED-A-INVOICE');
        $this->invoiceFor($allowedB, 'ALLOWED-B-INVOICE');
        $this->invoiceFor($blocked, 'BLOCKED-INVOICE');

        $this->actingAs($viewer);

        Livewire::test(InvoicePage::class)
            ->assertSee('OWN-SCOPE-INVOICE')
            ->assertSee('ALLOWED-A-INVOICE')
            ->assertSee('ALLOWED-B-INVOICE')
            ->assertDontSee('BLOCKED-INVOICE')
            ->assertViewHas('invoiceUsers', fn ($users) => $users->pluck('id')->sort()->values()->all() === collect([
                $viewer->id,
                $allowedA->id,
                $allowedB->id,
            ])->sort()->values()->all());
    }

    public function test_access_all_with_no_selected_users_remains_unrestricted(): void
    {
        $role = $this->roleWithPermissions('unrestricted-invoice-viewer', [
            'invoices.view',
            'invoices.access_all',
        ]);
        $viewer = User::factory()->create();
        $other = User::factory()->create();
        $viewer->roles()->attach($role);

        $this->invoiceFor($other, 'LEGACY-UNRESTRICTED-INVOICE');

        $this->actingAs($viewer);

        Livewire::test(InvoicePage::class)
            ->assertSee('LEGACY-UNRESTRICTED-INVOICE');
    }

    public function test_roles_page_saves_and_loads_selected_data_users(): void
    {
        $admin = User::factory()->create();
        $admin->roles()->attach(Role::where('name', 'admin')->firstOrFail());
        $role = Role::create(['name' => 'scoped-role-editor-test']);
        $allowedA = User::factory()->create();
        $allowedB = User::factory()->create();

        $this->actingAs($admin);

        Livewire::test(RolesPage::class)
            ->call('editRole', $role->id)
            ->set('rolePermissions', [
                'invoices.view',
                'invoices.access_all',
                'movements.view',
                'movements.access_all',
            ])
            ->assertSee('Invoices data access')
            ->assertSee('Movements data access')
            ->set('roleAccessibleUserSelections.invoices', json_encode([(string) $allowedA->id]))
            ->set('roleAccessibleUserSelections.movements', json_encode([(string) $allowedB->id]))
            ->call('saveRole')
            ->assertHasNoErrors();

        $this->assertDatabaseHas('role_module_accessible_user', [
            'role_id' => $role->id,
            'module' => 'invoices',
            'user_id' => $allowedA->id,
        ]);
        $this->assertDatabaseHas('role_module_accessible_user', [
            'role_id' => $role->id,
            'module' => 'movements',
            'user_id' => $allowedB->id,
        ]);
        $this->assertDatabaseMissing('role_module_accessible_user', [
            'role_id' => $role->id,
            'module' => 'invoices',
            'user_id' => $allowedB->id,
        ]);

        Livewire::test(RolesPage::class)
            ->call('editRole', $role->id)
            ->assertSet('roleAccessibleUserSelections.invoices', json_encode([(string) $allowedA->id]))
            ->assertSet('roleAccessibleUserSelections.movements', json_encode([(string) $allowedB->id]));
    }

    public function test_unrestricted_access_all_role_wins_when_user_has_multiple_roles(): void
    {
        $limitedRole = $this->roleWithPermissions('limited-role', ['invoices.view', 'invoices.access_all']);
        $unrestrictedRole = $this->roleWithPermissions('unrestricted-role', ['invoices.access_all']);
        $viewer = User::factory()->create();
        $allowed = User::factory()->create();
        $other = User::factory()->create();
        $limitedRole->syncAccessibleUsersForModule('invoices', [$allowed->id]);
        $viewer->roles()->attach([$limitedRole->id, $unrestrictedRole->id]);

        $this->actingAs($viewer);

        $this->assertNull($viewer->accessibleOwnerIds('invoices'));
        $this->assertTrue(Invoice::query()->where('user_id', $other->id)->exists() === false);

        $this->invoiceFor($other, 'MULTI-ROLE-UNRESTRICTED');

        $this->assertTrue(Invoice::query()->where('invoice_number', 'MULTI-ROLE-UNRESTRICTED')->exists());
    }

    public function test_selected_data_users_still_limit_an_admin_like_permission_set(): void
    {
        $role = Role::create(['name' => 'broad-but-data-scoped']);
        $role->permissions()->sync(Permission::query()->pluck('id'));
        $viewer = User::factory()->create();
        $allowed = User::factory()->create();
        $blocked = User::factory()->create();
        $viewer->roles()->attach($role);
        $role->syncAccessibleUsersForModule('invoices', [$allowed->id]);

        $this->actingAs($viewer);

        $this->assertTrue($viewer->isAdmin());
        $this->assertEqualsCanonicalizing(
            [$viewer->id, $allowed->id],
            $viewer->accessibleOwnerIds('invoices')
        );

        $this->invoiceFor($allowed, 'ADMIN-LIKE-ALLOWED');
        $this->invoiceFor($blocked, 'ADMIN-LIKE-BLOCKED');

        Livewire::test(InvoicePage::class)
            ->assertSee('ADMIN-LIKE-ALLOWED')
            ->assertDontSee('ADMIN-LIKE-BLOCKED');
    }

    public function test_each_access_all_module_has_an_independent_user_scope(): void
    {
        $role = $this->roleWithPermissions('per-page-data-scope', [
            'invoices.view',
            'invoices.access_all',
            'movements.view',
            'movements.access_all',
        ]);
        $viewer = User::factory()->create();
        $invoiceOwner = User::factory()->create();
        $movementOwner = User::factory()->create();
        $role->syncAccessibleUsersForModule('invoices', [$invoiceOwner->id]);
        $viewer->roles()->attach($role);

        $this->actingAs($viewer);

        $this->assertEqualsCanonicalizing(
            [$viewer->id, $invoiceOwner->id],
            $viewer->accessibleOwnerIds('invoices')
        );
        $this->assertNull($viewer->accessibleOwnerIds('movements'));
        $this->assertContains($movementOwner->id, $viewer->accessibleUserQuery('movements')->pluck('id')->all());
        $this->assertNotContains($movementOwner->id, $viewer->accessibleUserQuery('invoices')->pluck('id')->all());
    }
}
