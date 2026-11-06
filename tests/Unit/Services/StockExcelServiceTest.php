<?php

namespace Tests\Unit\Services;

use App\Features\Stock\Models\Stock;
use App\Features\Stock\Services\StockExcelService;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use PhpOffice\PhpSpreadsheet\IOFactory;
use PhpOffice\PhpSpreadsheet\Spreadsheet;
use PhpOffice\PhpSpreadsheet\Writer\Xlsx;
use Tests\TestCase;

class StockExcelServiceTest extends TestCase
{
    use RefreshDatabase;

    private StockExcelService $service;

    protected function setUp(): void
    {
        parent::setUp();

        $this->service = app(StockExcelService::class);
    }

    private function writeXlsx(array $rows): string
    {
        $spreadsheet = new Spreadsheet();
        $spreadsheet->getActiveSheet()->fromArray($rows, null, 'A1');

        $path = tempnam(sys_get_temp_dir(), 'tst');
        (new Xlsx($spreadsheet))->save($path);

        return $path;
    }

    public function test_export_writes_header_and_rows(): void
    {
        $user = User::factory()->create();
        $stock = Stock::factory()->for($user)->create([
            'name' => 'Kabel LAN',
            'sku' => 'LAN-1',
            'quantity' => 8,
            'unit' => 'roll',
            'price' => 25000,
            'min_stock' => 3,
            'status' => 'available',
            'description' => 'Cat6',
        ]);

        $binary = $this->service->export(collect([$stock]));

        $path = tempnam(sys_get_temp_dir(), 'tst');
        file_put_contents($path, $binary);
        $rows = IOFactory::load($path)->getActiveSheet()->toArray(null, true, true, false);
        unlink($path);

        $this->assertSame(StockExcelService::HEADERS, $rows[0]);
        $this->assertSame('Kabel LAN', $rows[1][0]);
        $this->assertSame('LAN-1', $rows[1][1]);
        $this->assertEquals(8, $rows[1][2]);
        $this->assertSame('available', $rows[1][6]);
        $this->assertSame('Cat6', $rows[1][7]);
    }

    public function test_export_empty_collection_only_has_header(): void
    {
        $path = tempnam(sys_get_temp_dir(), 'tst');
        file_put_contents($path, $this->service->export(collect()));
        $rows = IOFactory::load($path)->getActiveSheet()->toArray(null, true, true, false);
        unlink($path);

        $this->assertCount(1, $rows);
    }

    public function test_import_creates_stocks_and_skips_blank_names(): void
    {
        $user = User::factory()->create();
        $path = $this->writeXlsx([
            StockExcelService::HEADERS,
            ['Spidol', 'SPD-1', 10, 'pcs', 6000, 5, 'ignored', 'Hitam'],
            [null, 'KOSONG', 1, 'pcs', 1, 1, null, null],
            ['Penghapus', null, 0, null, null, null, null, null],
        ]);

        $count = $this->service->import($user, $path);
        unlink($path);

        $this->assertSame(2, $count);
        $this->assertDatabaseHas('stocks', ['user_id' => $user->id, 'name' => 'Spidol', 'status' => 'available', 'description' => 'Hitam']);
        $this->assertDatabaseHas('stocks', ['user_id' => $user->id, 'name' => 'Penghapus', 'quantity' => 0, 'unit' => 'pcs', 'min_stock' => 5, 'status' => 'out_of_stock']);
        $this->assertDatabaseMissing('stocks', ['sku' => 'KOSONG']);
    }
}
