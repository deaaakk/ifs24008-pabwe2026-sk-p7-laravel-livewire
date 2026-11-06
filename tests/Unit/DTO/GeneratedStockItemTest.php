<?php

namespace Tests\Unit\DTO;

use App\Features\Ai\DTO\GeneratedStockItem;
use PHPUnit\Framework\TestCase;

class GeneratedStockItemTest extends TestCase
{
    public function test_from_array_maps_and_sanitizes_values(): void
    {
        $item = GeneratedStockItem::fromArray([
            'name' => '  Pulpen  ',
            'sku' => 'PLP-01',
            'description' => 'Pulpen gel',
            'quantity' => '12',
            'unit' => 'box',
            'price' => '3500.5',
            'min_stock' => '2',
        ]);

        $this->assertSame('Pulpen', $item->name);
        $this->assertSame('PLP-01', $item->sku);
        $this->assertSame('Pulpen gel', $item->description);
        $this->assertSame(12, $item->quantity);
        $this->assertSame('box', $item->unit);
        $this->assertSame(3500.5, $item->price);
        $this->assertSame(2, $item->minStock);
    }

    public function test_from_array_uses_defaults_and_clamps_negatives(): void
    {
        $item = GeneratedStockItem::fromArray(['quantity' => -5, 'price' => -10, 'min_stock' => -1]);

        $this->assertSame('', $item->name);
        $this->assertNull($item->sku);
        $this->assertNull($item->description);
        $this->assertSame(0, $item->quantity);
        $this->assertSame('pcs', $item->unit);
        $this->assertSame(0.0, $item->price);
        $this->assertSame(0, $item->minStock);
    }

    public function test_non_numeric_price_becomes_null(): void
    {
        $this->assertNull(GeneratedStockItem::fromArray(['name' => 'X', 'price' => 'gratis'])->price);
        $this->assertSame(5, GeneratedStockItem::fromArray(['name' => 'X'])->minStock);
    }

    public function test_to_array_uses_snake_case_keys(): void
    {
        $array = (new GeneratedStockItem('Buku', 'BK', 'Catatan', 4, 'pcs', 9000.0, 3))->toArray();

        $this->assertSame([
            'name' => 'Buku',
            'sku' => 'BK',
            'description' => 'Catatan',
            'quantity' => 4,
            'unit' => 'pcs',
            'price' => 9000.0,
            'min_stock' => 3,
        ], $array);
    }
}
