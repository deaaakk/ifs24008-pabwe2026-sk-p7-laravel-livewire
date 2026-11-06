<?php

namespace Tests\Feature\Admin;

use App\Features\Admin\Livewire\AdminUserStockList;
use App\Features\Admin\Livewire\UserList;
use App\Features\Stock\Models\Stock;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Livewire\Livewire;
use Tests\TestCase;

class AdminLivewireTest extends TestCase
{
    use RefreshDatabase;

    private User $admin;

    protected function setUp(): void
    {
        parent::setUp();

        $this->admin = User::factory()->admin()->create();
    }

    public function test_user_list_shows_members_with_stock_counts(): void
    {
        $member = User::factory()->create(['name' => 'Budi Santoso']);
        Stock::factory()->for($member)->count(3)->create();

        Livewire::actingAs($this->admin)->test(UserList::class)
            ->assertSee('Budi Santoso')
            ->assertSeeInOrder(['Budi Santoso', '3'])
            ->assertDontSee($this->admin->email);
    }

    public function test_user_list_shows_empty_state(): void
    {
        Livewire::actingAs($this->admin)->test(UserList::class)->assertSee('Belum ada pengguna.');
    }

    public function test_user_list_can_search(): void
    {
        User::factory()->create(['name' => 'Budi Santoso']);
        User::factory()->create(['name' => 'Citra Lestari']);

        Livewire::actingAs($this->admin)->test(UserList::class)
            ->set('search', 'Citra')
            ->assertSee('Citra Lestari')
            ->assertDontSee('Budi Santoso');
    }

    public function test_admin_sees_user_stocks_read_only(): void
    {
        $member = User::factory()->create();
        Stock::factory()->for($member)->create(['name' => 'Router WiFi', 'quantity' => 0, 'status' => 'out_of_stock']);
        Stock::factory()->for($member)->create(['name' => 'Kabel LAN', 'quantity' => 50, 'status' => 'available']);

        Livewire::actingAs($this->admin)->test(AdminUserStockList::class, ['user' => $member])
            ->assertSee('Router WiFi')
            ->assertSee('Kabel LAN')
            ->assertDontSee('Hapus')
            ->assertDontSee('Edit')
            ->set('statusFilter', 'out_of_stock')
            ->assertSee('Router WiFi')
            ->assertDontSee('Kabel LAN')
            ->set('statusFilter', '')
            ->set('search', 'Kabel')
            ->assertSee('Kabel LAN')
            ->assertDontSee('Router WiFi');
    }

    public function test_admin_stock_list_shows_empty_state(): void
    {
        Livewire::actingAs($this->admin)->test(AdminUserStockList::class, ['user' => User::factory()->create()])
            ->assertSee('Belum ada stok.');
    }

    public function test_cannot_open_stock_list_of_another_admin(): void
    {
        Livewire::actingAs($this->admin)
            ->test(AdminUserStockList::class, ['user' => User::factory()->admin()->create()])
            ->assertStatus(404);
    }
}
