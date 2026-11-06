<?php

namespace Database\Seeders;

use App\Features\Stock\Enums\StockStatus;
use App\Features\Stock\Models\Stock;
use App\Models\User;
use Illuminate\Database\Seeder;

class DatabaseSeeder extends Seeder
{
    public function run(): void
    {
        User::factory()->admin()->create([
            'name' => 'Administrator',
            'username' => 'admin',
            'email' => 'admin@example.com',
            'password' => 'admin',
        ]);

        $member = User::factory()->create([
            'name' => 'Member Demo',
            'username' => 'member',
            'email' => 'member@example.com',
            'password' => 'member',
        ]);

        $samples = [
            ['Kertas A4 80gr', 'KRT-A4-80', 'rim', 45000, 40, 10],
            ['Pulpen Gel Hitam', 'PLP-GEL-HTM', 'pcs', 3500, 4, 10],
            ['Stapler Besar', 'STP-BSR-01', 'pcs', 28000, 0, 3],
            ['Map Plastik Folio', 'MAP-PLS-FL', 'pak', 12000, 25, 5],
        ];

        foreach ($samples as [$name, $sku, $unit, $price, $quantity, $minStock]) {
            Stock::factory()->for($member)->create([
                'name' => $name,
                'sku' => $sku,
                'unit' => $unit,
                'price' => $price,
                'quantity' => $quantity,
                'min_stock' => $minStock,
                'status' => StockStatus::fromQuantity($quantity, $minStock),
            ]);
        }
    }
}
