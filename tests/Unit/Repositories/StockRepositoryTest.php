<?php

namespace Tests\Unit\Repositories;

use App\Features\Stock\Models\Stock;
use App\Features\Stock\Repositories\StockRepository;
use App\Models\User;
use Illuminate\Database\Eloquent\ModelNotFoundException;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class StockRepositoryTest extends TestCase
{
    use RefreshDatabase;

    private StockRepository $repository;

    protected function setUp(): void
    {
        parent::setUp();

        $this->repository = new StockRepository();
    }

    public function test_paginate_filters_by_user_search_and_status(): void
    {
        $user = User::factory()->create();
        Stock::factory()->for($user)->create(['name' => 'Kabel HDMI', 'sku' => 'KBL-1', 'status' => 'available']);
        Stock::factory()->for($user)->create(['name' => 'Mouse', 'sku' => 'MSE-1', 'status' => 'out_of_stock']);
        Stock::factory()->create(['name' => 'Kabel Orang Lain']);

        $this->assertSame(2, $this->repository->paginateForUser($user->id)->total());
        $this->assertSame(1, $this->repository->paginateForUser($user->id, 'Kabel')->total());
        $this->assertSame(1, $this->repository->paginateForUser($user->id, 'MSE-1')->total());
        $this->assertSame(1, $this->repository->paginateForUser($user->id, null, 'out_of_stock')->total());
        $this->assertSame(0, $this->repository->paginateForUser($user->id, 'Kabel', 'out_of_stock')->total());
    }

    public function test_pagination_respects_per_page(): void
    {
        $user = User::factory()->create();
        Stock::factory()->for($user)->count(5)->create();

        $page = $this->repository->paginateForUser($user->id, null, null, 2);

        $this->assertCount(2, $page->items());
        $this->assertSame(5, $page->total());
    }

    public function test_all_for_user_is_sorted_by_name(): void
    {
        $user = User::factory()->create();
        Stock::factory()->for($user)->create(['name' => 'B']);
        Stock::factory()->for($user)->create(['name' => 'A']);
        Stock::factory()->create(['name' => 'Z']);

        $this->assertSame(['A', 'B'], $this->repository->allForUser($user->id)->pluck('name')->all());
    }

    public function test_find_or_fail(): void
    {
        $stock = Stock::factory()->create();

        $this->assertTrue($this->repository->findOrFail($stock->id)->is($stock));

        $this->expectException(ModelNotFoundException::class);
        $this->repository->findOrFail(999999);
    }

    public function test_create_update_delete(): void
    {
        $user = User::factory()->create();

        $stock = $this->repository->create([
            'user_id' => $user->id,
            'name' => 'Baru',
            'quantity' => 1,
            'unit' => 'pcs',
            'min_stock' => 5,
            'status' => 'low_stock',
        ]);
        $this->assertDatabaseHas('stocks', ['name' => 'Baru']);

        $updated = $this->repository->update($stock, ['name' => 'Diubah']);
        $this->assertSame('Diubah', $updated->name);

        $this->repository->delete($stock);
        $this->assertDatabaseMissing('stocks', ['id' => $stock->id]);
    }
}
