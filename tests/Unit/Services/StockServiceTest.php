<?php

namespace Tests\Unit\Services;

use App\Features\Stock\Enums\StockStatus;
use App\Features\Stock\Models\Stock;
use App\Features\Stock\Services\StockService;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
use Tests\TestCase;

class StockServiceTest extends TestCase
{
    use RefreshDatabase;

    private StockService $service;

    protected function setUp(): void
    {
        parent::setUp();

        Storage::fake('public');
        $this->service = app(StockService::class);
    }

    public function test_rules_cover_all_form_fields(): void
    {
        $this->assertSame(
            ['name', 'sku', 'description', 'quantity', 'unit', 'price', 'min_stock'],
            array_keys(StockService::rules()),
        );
    }

    public function test_create_normalizes_input_and_derives_status(): void
    {
        $user = User::factory()->create();

        $stock = $this->service->create($user, [
            'name' => '  Spidol  ',
            'sku' => '',
            'description' => '',
            'quantity' => '3',
            'unit' => '',
            'price' => '',
            'min_stock' => '5',
        ]);

        $this->assertSame($user->id, $stock->user_id);
        $this->assertSame('Spidol', $stock->name);
        $this->assertNull($stock->sku);
        $this->assertNull($stock->description);
        $this->assertSame('pcs', $stock->unit);
        $this->assertNull($stock->price);
        $this->assertSame(StockStatus::LowStock, $stock->status);
    }

    public function test_create_keeps_provided_optional_values(): void
    {
        $stock = $this->service->create(User::factory()->create(), [
            'name' => 'Kertas',
            'sku' => ' KRT-1 ',
            'description' => 'Kertas A4',
            'quantity' => 50,
            'unit' => ' rim ',
            'price' => '45000',
            'min_stock' => 10,
        ]);

        $this->assertSame('KRT-1', $stock->sku);
        $this->assertSame('rim', $stock->unit);
        $this->assertSame('45000.00', $stock->price);
        $this->assertSame(StockStatus::Available, $stock->status);
    }

    public function test_update_recomputes_status(): void
    {
        $stock = Stock::factory()->create(['quantity' => 50, 'min_stock' => 5]);

        $updated = $this->service->update($stock, ['name' => 'Habis', 'quantity' => 0, 'min_stock' => 5]);

        $this->assertSame(StockStatus::OutOfStock, $updated->status);
    }

    public function test_list_all_and_find(): void
    {
        $user = User::factory()->create();
        $stock = Stock::factory()->for($user)->create(['name' => 'Kabel']);
        Stock::factory()->create();

        $this->assertSame(1, $this->service->list($user)->total());
        $this->assertCount(1, $this->service->all($user));
        $this->assertTrue($this->service->find($stock->id)->is($stock));
    }

    public function test_adjust_quantity_never_goes_below_zero(): void
    {
        $stock = Stock::factory()->create(['quantity' => 2, 'min_stock' => 5, 'status' => 'low_stock']);

        $stock = $this->service->adjustQuantity($stock, -10);
        $this->assertSame(0, $stock->quantity);
        $this->assertSame(StockStatus::OutOfStock, $stock->status);

        $stock = $this->service->adjustQuantity($stock, 6);
        $this->assertSame(StockStatus::Available, $stock->status);
    }

    public function test_cover_lifecycle(): void
    {
        $stock = Stock::factory()->create();

        $stock = $this->service->storeCover($stock, UploadedFile::fake()->image('a.jpg'));
        $first = $stock->cover_image_path;
        Storage::disk('public')->assertExists($first);

        $stock = $this->service->storeCover($stock, UploadedFile::fake()->image('b.jpg'));
        Storage::disk('public')->assertMissing($first);

        $second = $stock->cover_image_path;
        $stock = $this->service->removeCover($stock);
        $this->assertNull($stock->cover_image_path);
        Storage::disk('public')->assertMissing($second);

        // remove tanpa cover tidak error
        $this->assertNull($this->service->removeCover($stock)->cover_image_path);
    }

    public function test_delete_removes_cover_file_and_record(): void
    {
        $stock = $this->service->storeCover(Stock::factory()->create(), UploadedFile::fake()->image('a.jpg'));
        $path = $stock->cover_image_path;

        $this->service->delete($stock);

        $this->assertDatabaseMissing('stocks', ['id' => $stock->id]);
        Storage::disk('public')->assertMissing($path);
    }

    public function test_delete_without_cover(): void
    {
        $stock = Stock::factory()->create();

        $this->service->delete($stock);

        $this->assertDatabaseMissing('stocks', ['id' => $stock->id]);
    }
}
