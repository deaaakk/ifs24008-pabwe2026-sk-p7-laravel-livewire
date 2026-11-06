<?php

namespace Tests\Feature\Auth;

use App\Features\Auth\Livewire\Login;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Livewire\Livewire;
use Tests\TestCase;

class LoginTest extends TestCase
{
    use RefreshDatabase;

    public function test_login_page_renders_with_required_ids(): void
    {
        $this->get('/login')
            ->assertOk()
            ->assertSee('id="login-username"', false)
            ->assertSee('id="login-password"', false)
            ->assertSee('id="login-submit"', false);
    }

    public function test_member_can_login_with_username(): void
    {
        User::factory()->create(['username' => 'budi', 'password' => 'rahasia123']);

        Livewire::test(Login::class)
            ->set('login', 'budi')
            ->set('password', 'rahasia123')
            ->call('submit')
            ->assertHasNoErrors()
            ->assertRedirect('/stocks');

        $this->assertAuthenticated();
    }

    public function test_user_can_login_with_email(): void
    {
        User::factory()->create(['email' => 'budi@example.com', 'password' => 'rahasia123']);

        Livewire::test(Login::class)
            ->set('login', 'budi@example.com')
            ->set('password', 'rahasia123')
            ->call('submit')
            ->assertRedirect('/stocks');

        $this->assertAuthenticated();
    }

    public function test_admin_is_redirected_to_admin_users(): void
    {
        User::factory()->admin()->create(['username' => 'admin', 'password' => 'admin']);

        Livewire::test(Login::class)
            ->set('login', 'admin')
            ->set('password', 'admin')
            ->call('submit')
            ->assertRedirect('/admin/users');
    }

    public function test_wrong_password_shows_error(): void
    {
        User::factory()->create(['username' => 'budi', 'password' => 'rahasia123']);

        Livewire::test(Login::class)
            ->set('login', 'budi')
            ->set('password', 'salah')
            ->call('submit')
            ->assertHasErrors(['login']);

        $this->assertGuest();
    }

    public function test_empty_fields_are_rejected(): void
    {
        Livewire::test(Login::class)
            ->call('submit')
            ->assertHasErrors(['login' => 'required', 'password' => 'required']);
    }

    public function test_logout_clears_session(): void
    {
        $user = User::factory()->create();

        $this->actingAs($user)->post('/logout')->assertRedirect('/login');

        $this->assertGuest();
    }
}
