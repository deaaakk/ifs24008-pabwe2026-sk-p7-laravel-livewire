<?php

namespace Tests\Feature;

use App\Features\Stock\Models\Stock;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class RoutingTest extends TestCase
{
    use RefreshDatabase;

    public function test_root_shows_login_page_without_redirect_for_guest(): void
    {
        $this->get('/')
            ->assertOk()
            ->assertSee('id="login-username"', false);
    }

    public function test_root_redirects_authenticated_user_by_role(): void
    {
        $this->actingAs(User::factory()->admin()->create())->get('/')->assertRedirect('/admin/users');

        auth()->logout();

        $this->actingAs(User::factory()->create())->get('/')->assertRedirect('/stocks');
    }

    public function test_guest_is_redirected_from_protected_pages(): void
    {
        $this->get('/stocks')->assertRedirect('/login');
        $this->get('/profile')->assertRedirect('/login');
        $this->get('/admin/users')->assertRedirect('/login');
    }

    public function test_authenticated_user_is_redirected_away_from_login(): void
    {
        $this->actingAs(User::factory()->create())->get('/login')->assertRedirect('/stocks');
    }

    public function test_member_pages_render_for_member(): void
    {
        $member = User::factory()->create();
        $stock = Stock::factory()->for($member)->create(['name' => 'Kabel Data']);

        $this->actingAs($member)->get('/stocks')->assertOk()->assertSee('Stok Barang');
        $this->actingAs($member)->get("/stocks/{$stock->id}")->assertOk()->assertSee('Kabel Data');
        $this->actingAs($member)->get('/profile')->assertOk()->assertSee('Profil');
    }

    public function test_member_cannot_open_admin_pages(): void
    {
        $member = User::factory()->create();

        $this->actingAs($member)->get('/admin/users')->assertForbidden();
        $this->actingAs($member)->get("/admin/users/{$member->id}/stocks")->assertForbidden();
    }

    public function test_admin_cannot_open_member_stock_pages(): void
    {
        $admin = User::factory()->admin()->create();
        $stock = Stock::factory()->create();

        $this->actingAs($admin)->get('/stocks')->assertForbidden();
        $this->actingAs($admin)->get("/stocks/{$stock->id}")->assertForbidden();
    }

    public function test_admin_pages_render_for_admin(): void
    {
        $admin = User::factory()->admin()->create();
        $member = User::factory()->create(['name' => 'Budi Pelanggan']);

        $this->actingAs($admin)->get('/admin/users')->assertOk()->assertSee('Budi Pelanggan');
        $this->actingAs($admin)->get("/admin/users/{$member->id}/stocks")->assertOk()->assertSee('hanya baca');
    }

    public function test_stock_ownership_is_enforced_on_routes(): void
    {
        $member = User::factory()->create();
        $foreign = Stock::factory()->create();

        $this->actingAs($member)->get("/stocks/{$foreign->id}")->assertForbidden();
        $this->actingAs($member)->get('/stocks/999999')->assertNotFound();
    }
}