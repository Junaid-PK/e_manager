<?php

namespace Tests\Feature;

use App\Livewire\Settings\UsersManager;
use App\Models\Role;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Hash;
use Livewire\Livewire;
use Tests\TestCase;

class UsersManagerTest extends TestCase
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

    public function test_renders_paginated_user_list(): void
    {
        $this->actingAs($this->admin);

        Livewire::test(UsersManager::class)
            ->assertSee($this->admin->name)
            ->assertSee($this->admin->email);
    }

    // ── createUser ─────────────────────────────────────────────────────────

    public function test_createUser_creates_user_with_valid_data(): void
    {
        $this->actingAs($this->admin);
        $viewerRole = Role::create(['name' => 'viewer']);

        Livewire::test(UsersManager::class)
            ->set('name', 'Jane Doe')
            ->set('email', 'jane@example.com')
            ->set('password', 'secret123')
            ->set('selectedRoles', [(string) $viewerRole->id])
            ->call('createUser')
            ->assertHasNoErrors();

        $user = User::where('email', 'jane@example.com')->first();
        $this->assertNotNull($user);
        $this->assertEquals('Jane Doe', $user->name);
        $this->assertTrue($user->roles->contains('id', $viewerRole->id));
    }

    public function test_createUser_requires_name(): void
    {
        $this->actingAs($this->admin);

        Livewire::test(UsersManager::class)
            ->set('name', '')
            ->set('email', 'test@example.com')
            ->set('password', 'secret123')
            ->call('createUser')
            ->assertHasErrors(['name']);
    }

    public function test_createUser_requires_valid_email(): void
    {
        $this->actingAs($this->admin);

        Livewire::test(UsersManager::class)
            ->set('name', 'Test')
            ->set('email', 'not-an-email')
            ->set('password', 'secret123')
            ->call('createUser')
            ->assertHasErrors(['email']);
    }

    public function test_createUser_requires_password_min_8(): void
    {
        $this->actingAs($this->admin);

        Livewire::test(UsersManager::class)
            ->set('name', 'Test')
            ->set('email', 'test@example.com')
            ->set('password', 'short')
            ->call('createUser')
            ->assertHasErrors(['password']);
    }

    public function test_createUser_rejects_duplicate_email(): void
    {
        $this->actingAs($this->admin);
        $existing = User::factory()->create(['email' => 'dup@example.com']);

        $countBefore = User::count();

        Livewire::test(UsersManager::class)
            ->set('name', 'Another')
            ->set('email', 'dup@example.com')
            ->set('password', 'secret123')
            ->call('createUser')
            ->assertHasErrors(['email']);

        $this->assertEquals($countBefore, User::count());
    }

    // ── editUser ───────────────────────────────────────────────────────────

    public function test_editUser_loads_user_into_form(): void
    {
        $this->actingAs($this->admin);
        $user = User::factory()->create(['name' => 'Edit Me', 'email' => 'edit@example.com']);

        Livewire::test(UsersManager::class)
            ->call('editUser', $user->id)
            ->assertSet('name', 'Edit Me')
            ->assertSet('email', 'edit@example.com')
            ->assertSet('editingUserId', $user->id)
            ->assertSet('showFormModal', true);
    }

    // ── updateUser ─────────────────────────────────────────────────────────

    public function test_updateUser_updates_name_and_email(): void
    {
        $this->actingAs($this->admin);
        $user = User::factory()->create(['name' => 'Old Name', 'email' => 'old@example.com']);

        Livewire::test(UsersManager::class)
            ->call('editUser', $user->id)
            ->set('name', 'New Name')
            ->set('email', 'new@example.com')
            ->set('password', '')
            ->call('updateUser')
            ->assertHasNoErrors();

        $user->refresh();
        $this->assertEquals('New Name', $user->name);
        $this->assertEquals('new@example.com', $user->email);
    }

    public function test_updateUser_blank_password_preserves_hash(): void
    {
        $this->actingAs($this->admin);
        $user = User::factory()->create(['password' => Hash::make('original_password')]);
        $originalHash = $user->password;

        Livewire::test(UsersManager::class)
            ->call('editUser', $user->id)
            ->set('password', '')
            ->call('updateUser')
            ->assertHasNoErrors();

        $user->refresh();
        $this->assertEquals($originalHash, $user->password);
    }

    public function test_updateUser_with_new_password_updates_hash(): void
    {
        $this->actingAs($this->admin);
        $user = User::factory()->create(['password' => Hash::make('original_password')]);
        $originalHash = $user->password;

        Livewire::test(UsersManager::class)
            ->call('editUser', $user->id)
            ->set('password', 'newpassword123')
            ->call('updateUser')
            ->assertHasNoErrors();

        $user->refresh();
        $this->assertNotEquals($originalHash, $user->password);
        $this->assertTrue(Hash::check('newpassword123', $user->password));
    }

    public function test_updateUser_rejects_duplicate_email_from_another_user(): void
    {
        $this->actingAs($this->admin);
        User::factory()->create(['email' => 'taken@example.com']);
        $user = User::factory()->create(['email' => 'mine@example.com']);

        Livewire::test(UsersManager::class)
            ->call('editUser', $user->id)
            ->set('email', 'taken@example.com')
            ->call('updateUser')
            ->assertHasErrors(['email']);
    }

    public function test_updateUser_allows_keeping_own_email(): void
    {
        $this->actingAs($this->admin);
        $user = User::factory()->create(['email' => 'mine@example.com']);

        Livewire::test(UsersManager::class)
            ->call('editUser', $user->id)
            ->set('email', 'mine@example.com')
            ->call('updateUser')
            ->assertHasNoErrors();
    }

    // ── deleteUser ─────────────────────────────────────────────────────────

    public function test_deleteUser_removes_user_and_detaches_roles(): void
    {
        $this->actingAs($this->admin);
        $viewerRole = Role::create(['name' => 'viewer']);
        $user = User::factory()->create();
        $user->roles()->attach($viewerRole->id);

        Livewire::test(UsersManager::class)
            ->call('deleteUser', $user->id);

        $this->assertNull(User::find($user->id));
    }

    public function test_deleteUser_prevents_deleting_sole_admin(): void
    {
        $this->actingAs($this->admin);

        // admin is the only admin user
        $component = Livewire::test(UsersManager::class)
            ->call('deleteUser', $this->admin->id);

        $component->assertHasErrors(['deleteUser']);
        $this->assertNotNull(User::find($this->admin->id));
    }

    public function test_deleteUser_allows_deleting_admin_when_another_admin_exists(): void
    {
        $this->actingAs($this->admin);

        $secondAdmin = User::factory()->create();
        $secondAdmin->roles()->attach($this->adminRole->id);

        Livewire::test(UsersManager::class)
            ->call('deleteUser', $this->admin->id);

        $this->assertNull(User::find($this->admin->id));
    }

    // ── assignRoles ────────────────────────────────────────────────────────

    public function test_assignRoles_syncs_pivot(): void
    {
        $this->actingAs($this->admin);
        $viewerRole = Role::create(['name' => 'viewer']);
        $user = User::factory()->create();

        Livewire::test(UsersManager::class)
            ->call('assignRoles', $user->id, [$viewerRole->id]);

        $user->refresh();
        $this->assertTrue($user->roles->contains('id', $viewerRole->id));
    }
}
