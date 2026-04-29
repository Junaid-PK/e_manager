<?php

namespace Tests\Feature;

use App\Livewire\Roles\RolesPage;
use App\Models\Permission;
use App\Models\Role;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Livewire\Livewire;
use Tests\TestCase;

class RolesManagerTest extends TestCase
{
    use RefreshDatabase;

    private User $admin;
    private Role $adminRole;

    protected function setUp(): void
    {
        parent::setUp();

        $this->adminRole = Role::create(['name' => 'admin']);
        $this->admin = User::factory()->create();
        $this->admin->roles()->attach($this->adminRole->id);
    }

    // ── Rendering ──────────────────────────────────────────────────────────

    public function test_renders_roles_list_with_user_count(): void
    {
        $this->actingAs($this->admin);

        Livewire::test(RolesPage::class)
            ->assertSee('admin');
    }

    // ── createRole ─────────────────────────────────────────────────────────

    public function test_createRole_creates_role_with_unique_name(): void
    {
        $this->actingAs($this->admin);

        Livewire::test(RolesPage::class)
            ->set('roleName', 'editor')
            ->call('createRole')
            ->assertHasNoErrors();

        $this->assertDatabaseHas('roles', ['name' => 'editor']);
    }

    public function test_createRole_rejects_duplicate_name(): void
    {
        $this->actingAs($this->admin);
        Role::create(['name' => 'editor']);

        $countBefore = Role::count();

        Livewire::test(RolesPage::class)
            ->set('roleName', 'editor')
            ->call('createRole')
            ->assertHasErrors(['roleName']);

        $this->assertEquals($countBefore, Role::count());
    }

    public function test_createRole_requires_name(): void
    {
        $this->actingAs($this->admin);

        Livewire::test(RolesPage::class)
            ->set('roleName', '')
            ->call('createRole')
            ->assertHasErrors(['roleName']);
    }

    // ── editRole ───────────────────────────────────────────────────────────

    public function test_editRole_loads_role_and_permissions_into_form(): void
    {
        $this->actingAs($this->admin);
        $role = Role::create(['name' => 'editor']);
        $perm = Permission::create(['name' => 'invoices.view']);
        $role->permissions()->attach($perm->id);

        Livewire::test(RolesPage::class)
            ->call('editRole', $role->id)
            ->assertSet('roleName', 'editor')
            ->assertSet('editingRoleId', $role->id)
            ->assertSet('showFormModal', true);
    }

    // ── saveRole ───────────────────────────────────────────────────────────

    public function test_saveRole_updates_name_and_syncs_permissions(): void
    {
        $this->actingAs($this->admin);
        $role = Role::create(['name' => 'editor']);
        $perm1 = Permission::create(['name' => 'invoices.view']);
        $perm2 = Permission::create(['name' => 'invoices.edit']);

        Livewire::test(RolesPage::class)
            ->call('editRole', $role->id)
            ->set('roleName', 'senior-editor')
            ->set('rolePermissions', ['invoices.view', 'invoices.edit'])
            ->call('saveRole')
            ->assertHasNoErrors();

        $role->refresh();
        $this->assertEquals('senior-editor', $role->name);
        $this->assertTrue($role->permissions->contains('name', 'invoices.view'));
        $this->assertTrue($role->permissions->contains('name', 'invoices.edit'));
    }

    public function test_saveRole_creates_missing_selected_permissions_before_syncing(): void
    {
        $this->actingAs($this->admin);
        $role = Role::create(['name' => 'editor']);

        $this->assertDatabaseMissing('permissions', ['name' => 'expenses.export']);

        Livewire::test(RolesPage::class)
            ->call('editRole', $role->id)
            ->set('roleName', 'editor')
            ->set('rolePermissions', ['expenses.export'])
            ->call('saveRole')
            ->assertHasNoErrors();

        $this->assertDatabaseHas('permissions', ['name' => 'expenses.export']);
        $role->refresh();
        $this->assertTrue($role->permissions->contains('name', 'expenses.export'));
    }

    public function test_saveRole_prevents_renaming_admin_role(): void
    {
        $this->actingAs($this->admin);

        Livewire::test(RolesPage::class)
            ->call('editRole', $this->adminRole->id)
            ->set('roleName', 'superadmin')
            ->call('saveRole')
            ->assertHasErrors(['roleName']);

        $this->adminRole->refresh();
        $this->assertEquals('admin', $this->adminRole->name);
    }

    public function test_saveRole_allows_saving_admin_role_without_rename(): void
    {
        $this->actingAs($this->admin);
        $perm = Permission::create(['name' => 'invoices.view']);

        Livewire::test(RolesPage::class)
            ->call('editRole', $this->adminRole->id)
            ->set('roleName', 'admin')
            ->set('rolePermissions', ['invoices.view'])
            ->call('saveRole')
            ->assertHasNoErrors();
    }

    public function test_saveRole_rejects_duplicate_name_for_other_role(): void
    {
        $this->actingAs($this->admin);
        Role::create(['name' => 'viewer']);
        $role = Role::create(['name' => 'editor']);

        Livewire::test(RolesPage::class)
            ->call('editRole', $role->id)
            ->set('roleName', 'viewer')
            ->call('saveRole')
            ->assertHasErrors(['roleName']);
    }

    // ── deleteRole ─────────────────────────────────────────────────────────

    public function test_deleteRole_removes_role_with_no_users(): void
    {
        $this->actingAs($this->admin);
        $role = Role::create(['name' => 'temp-role']);

        Livewire::test(RolesPage::class)
            ->call('deleteRole', $role->id);

        $this->assertNull(Role::find($role->id));
    }

    public function test_deleteRole_prevents_deleting_admin_role(): void
    {
        $this->actingAs($this->admin);

        Livewire::test(RolesPage::class)
            ->call('deleteRole', $this->adminRole->id)
            ->assertHasErrors(['deleteRole']);

        $this->assertNotNull(Role::find($this->adminRole->id));
    }

    public function test_deleteRole_prevents_deleting_role_with_assigned_users(): void
    {
        $this->actingAs($this->admin);
        $role = Role::create(['name' => 'editor']);
        $user = User::factory()->create();
        $user->roles()->attach($role->id);

        Livewire::test(RolesPage::class)
            ->call('deleteRole', $role->id)
            ->assertHasErrors(['deleteRole']);

        $this->assertNotNull(Role::find($role->id));
    }

    public function test_deleteRole_detaches_permissions_before_deleting(): void
    {
        $this->actingAs($this->admin);
        $role = Role::create(['name' => 'temp-role']);
        $perm = Permission::create(['name' => 'invoices.view']);
        $role->permissions()->attach($perm->id);

        Livewire::test(RolesPage::class)
            ->call('deleteRole', $role->id);

        $this->assertNull(Role::find($role->id));
        // Permission itself should still exist
        $this->assertNotNull(Permission::find($perm->id));
    }

    // ── getPermissionMatrix ────────────────────────────────────────────────

    public function test_getPermissionMatrix_returns_modules_and_actions(): void
    {
        $this->actingAs($this->admin);

        $component = Livewire::test(RolesPage::class);
        $matrix = (new RolesPage())->getPermissionMatrix();

        $this->assertArrayHasKey('invoices', $matrix);
        $this->assertContains('view', $matrix['invoices']);
        $this->assertContains('create', $matrix['invoices']);
        $this->assertContains('edit', $matrix['invoices']);
        $this->assertContains('delete', $matrix['invoices']);
        $this->assertContains('export', $matrix['invoices']);
    }
}
