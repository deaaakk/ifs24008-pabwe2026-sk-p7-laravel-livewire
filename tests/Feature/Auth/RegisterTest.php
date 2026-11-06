<?php

namespace Tests\Feature\Auth;

use App\Features\Auth\Livewire\Register;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Livewire\Livewire;
use Tests\TestCase;

class RegisterTest extends TestCase
{
    use RefreshDatabase;

    public function test_register_page_renders(): void
    {
        $this->get('/register')->assertOk()->assertSee('Daftar');
    }

    public function test_user_can_register_as_member(): void
    {
        Livewire::test(Register::class)
            ->set('name', 'Siti Aminah')
            ->set('username', 'siti_aminah')
            ->set('email', 'siti@example.com')
            ->set('password', 'rahasia123')
            ->set('password_confirmation', 'rahasia123')
            ->call('submit')
            ->assertHasNoErrors()
            ->assertRedirect('/stocks');

        $this->assertDatabaseHas('users', ['username' => 'siti_aminah', 'role' => 'member']);
        $this->assertAuthenticated();
    }

    public function test_duplicate_username_and_email_are_rejected(): void
    {
        User::factory()->create(['username' => 'siti', 'email' => 'siti@example.com']);

        Livewire::test(Register::class)
            ->set('name', 'Siti')
            ->set('username', 'siti')
            ->set('email', 'siti@example.com')
            ->set('password', 'rahasia123')
            ->set('password_confirmation', 'rahasia123')
            ->call('submit')
            ->assertHasErrors(['username' => 'unique', 'email' => 'unique']);
    }

    public function test_password_confirmation_must_match(): void
    {
        Livewire::test(Register::class)
            ->set('name', 'Siti')
            ->set('username', 'siti')
            ->set('email', 'siti@example.com')
            ->set('password', 'rahasia123')
            ->set('password_confirmation', 'beda')
            ->call('submit')
            ->assertHasErrors(['password' => 'confirmed']);
    }
}
