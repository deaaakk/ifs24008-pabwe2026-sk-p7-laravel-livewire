<?php

namespace Tests\Unit\Services;

use App\Features\Admin\Services\AdminUserService;
use App\Features\Stock\Models\Stock;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class AdminUserServiceTest extends TestCase
{
    use RefreshDatabase;

    private AdminUserService $service;

    protected function setUp(): void
    {
        parent::setUp();

        $this->service = app(AdminUserService::class);
    }

    public function test_paginate_users_lists_only_members_with_counts(): void
    {
        User::factory()->admin()->create();
        $member = User::factory()->create(['name' => 'Budi']);
        Stock::factory()->for($member)->count(2)->create();

        $page = $this->service->paginateUsers();

        $this->assertSame(1, $page->total());
        $this->assertSame(2, $page->items()[0]->stocks_count);
    }

    public function test_paginate_users_can_search_by_name_username_or_email(): void
    {
        User::factory()->create(['name' => 'Budi Santoso', 'username' => 'budi', 'email' => 'budi@x.test']);
        User::factory()->create(['name' => 'Citra Lestari', 'username' => 'citra', 'email' => 'citra@x.test']);

        $this->assertSame(1, $this->service->paginateUsers('Citra')->total());
        $this->assertSame(1, $this->service->paginateUsers('budi')->total());
        $this->assertSame(1, $this->service->paginateUsers('citra@x.test')->total());
        $this->assertSame(0, $this->service->paginateUsers('tidak-ada')->total());
    }

    public function test_stocks_of_user(): void
    {
        $member = User::factory()->create();
        Stock::factory()->for($member)->create(['name' => 'Router', 'status' => 'available']);
        Stock::factory()->for($member)->create(['name' => 'Switch', 'status' => 'out_of_stock']);
        Stock::factory()->create();

        $this->assertSame(2, $this->service->stocksOf($member)->total());
        $this->assertSame(1, $this->service->stocksOf($member, 'Router')->total());
        $this->assertSame(1, $this->service->stocksOf($member, null, 'out_of_stock')->total());
    }

    public function test_summary_of_user(): void
    {
        $member = User::factory()->create();
        Stock::factory()->for($member)->create(['quantity' => 20, 'status' => 'available']);
        Stock::factory()->for($member)->create(['quantity' => 3, 'status' => 'low_stock']);
        Stock::factory()->for($member)->create(['quantity' => 0, 'status' => 'out_of_stock']);

        $this->assertSame(
            ['items' => 3, 'quantity' => 23, 'low' => 1, 'out' => 1],
            $this->service->summaryOf($member),
        );
    }
}
