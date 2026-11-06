<?php

namespace Tests\Unit\Models;

use App\Features\Stock\Enums\StockStatus;
use App\Features\Stock\Models\Stock;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Storage;
use Tests\TestCase;

class StockModelTest extends TestCase
{
    use RefreshDatabase;

    public function test_casts_attributes(): void
    {
        $stock = Stock::factory()->create(['quantity' => '7', 'price' => 1500, 'status' => 'low_stock'])->fresh();

        $this->assertSame(7, $stock->quantity);
        $this->assertSame('1500.00', $stock->price);
        $this->assertSame(StockStatus::LowStock, $stock->status);
    }

    public function test_belongs_to_user(): void
    {
        $user = User::factory()->create();
        $stock = Stock::factory()->for($user)->create();

        $this->assertTrue($stock->user->is($user));
    }

    public function test_cover_url(): void
    {
        Storage::fake('public');

        $this->assertNull(Stock::factory()->create()->coverUrl());

        $stock = Stock::factory()->create(['cover_image_path' => 'stock-covers/a.jpg']);
        $this->assertStringContainsString('stock-covers/a.jpg', $stock->coverUrl());
    }
}
