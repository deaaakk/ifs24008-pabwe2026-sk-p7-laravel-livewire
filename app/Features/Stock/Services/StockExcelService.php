<?php

namespace App\Features\Stock\Services;

use App\Models\User;
use Illuminate\Support\Collection;
use PhpOffice\PhpSpreadsheet\IOFactory;
use PhpOffice\PhpSpreadsheet\Spreadsheet;
use PhpOffice\PhpSpreadsheet\Writer\Xlsx;

class StockExcelService
{
    public const HEADERS = ['name', 'sku', 'quantity', 'unit', 'price', 'min_stock', 'status', 'description'];

    public function __construct(private readonly StockService $stocks) {}

    /**
     * Hasilkan isi file .xlsx dari kumpulan stok.
     */
    public function export(Collection $stocks): string
    {
        $spreadsheet = new Spreadsheet();
        $sheet = $spreadsheet->getActiveSheet();
        $sheet->setTitle('Stock');
        $sheet->fromArray(self::HEADERS, null, 'A1');

        $row = 2;
        foreach ($stocks as $stock) {
            $sheet->fromArray([
                $stock->name,
                $stock->sku,
                $stock->quantity,

                $stock->unit,
                $stock->price,
                $stock->min_stock,
                $stock->status->value,
                $stock->description,
            ], null, 'A'.$row);
            $row++;
        }

        $path = tempnam(sys_get_temp_dir(), 'stock');
        (new Xlsx($spreadsheet))->save($path);
        $content = (string) file_get_contents($path);
        unlink($path);

        return $content;
    }

    /**
     * Impor stok milik $user dari file Excel. Kolom status diabaikan
     * (status dihitung ulang dari quantity & min_stock).
     * Hanya format Excel (.xlsx / .xls) yang diterima; file lain melempar exception.
     */
    public function import(User $user, string $path): int
    {
        $rows = IOFactory::load($path, 0, [IOFactory::READER_XLSX, IOFactory::READER_XLS])
            ->getActiveSheet()
            ->toArray(null, true, true, false);

        $count = 0;
        foreach (array_slice($rows, 1) as $row) {
            $name = trim((string) ($row[0] ?? ''));


            if ($name === '') {
                continue;
            }

            $this->stocks->create($user, [
                'name' => $name,
                'sku' => $row[1] ?? null,
                'quantity' => $row[2] ?? 0,
                'unit' => $row[3] ?? null,
                'price' => $row[4] ?? null,
                'min_stock' => $row[5] ?? 5,
                'description' => $row[7] ?? null,
            ]);
            $count++;
        }

        return $count;
    }
}