<?php

namespace Tests\Feature\Auth;

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Livewire\Volt\Volt;
use Tests\TestCase;

class AuthenticationTest extends TestCase
{
    use RefreshDatabase;

    public function test_login_screen_can_be_rendered(): void
    {
        $response = $this->get('/login');

        $response
            ->assertOk()
            ->assertSeeVolt('pages.auth.login');
    }

    public function test_users_can_authenticate_using_the_login_screen(): void
    {
        $user = User::factory()->create();

        $component = Volt::test('pages.auth.login')
            ->set('form.email', $user->email)
            ->set('form.password', 'password');

        $component->call('login');

        $component
            ->assertHasNoErrors()
            ->assertRedirect(route('dashboard', absolute: false));

        $this->assertAuthenticated();
    }

    public function test_users_can_not_authenticate_with_invalid_password(): void
    {
        $user = User::factory()->create();

        $component = Volt::test('pages.auth.login')
            ->set('form.email', $user->email)
            ->set('form.password', 'wrong-password');

        $component->call('login');

        $component
            ->assertHasErrors()
            ->assertNoRedirect();

        $this->assertGuest();
    }

    public function test_developer_login_option_is_hidden_outside_local_environment(): void
    {
        $this->get('/login')
            ->assertOk()
            ->assertDontSee('Developer login');
    }

    public function test_developer_login_creates_admin_user_in_local_environment(): void
    {
        $this->app->detectEnvironment(fn () => 'local');

        $this->withSession(['_token' => 'test-token'])
            ->post(route('developer-login'), ['_token' => 'test-token'])
            ->assertRedirect(route('dashboard', absolute: false));

        $user = User::where('email', 'developer@example.com')->first();

        $this->assertAuthenticatedAs($user);
        $this->assertTrue($user->isAdmin());
    }

    public function test_developer_login_action_is_blocked_outside_local_environment(): void
    {
        $this->withSession(['_token' => 'test-token'])
            ->post(route('developer-login'), ['_token' => 'test-token'])
            ->assertNotFound();

        $this->assertGuest();
        $this->assertDatabaseMissing('users', ['email' => 'developer@example.com']);
    }

    public function test_navigation_menu_can_be_rendered(): void
    {
        $user = User::factory()->create();
        $role = \App\Models\Role::firstOrCreate(['name' => 'admin']);
        $user->roles()->attach($role);

        $response = $this->actingAs($user)->get('/dashboard');

        $response->assertOk();
    }

    public function test_users_can_logout(): void
    {
        $user = User::factory()->create();

        $this->actingAs($user);

        $component = Volt::test('layout.navigation');

        $component->call('logout');

        $component
            ->assertHasNoErrors()
            ->assertRedirect('/');

        $this->assertGuest();
    }
}
