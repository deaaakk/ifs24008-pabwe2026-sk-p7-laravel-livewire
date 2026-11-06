<?php

namespace App\Features\Stock\Livewire;

use App\Features\Stock\Models\Stock;
use App\Features\Stock\Services\StockService;
use Illuminate\Contracts\View\View;
use Illuminate\Foundation\Auth\Access\AuthorizesRequests;
use Illuminate\Support\Str;
use Livewire\Attributes\Layout;
use Livewire\Attributes\Title;
use Livewire\Component;
use Livewire\WithFileUploads;

#[Layout('components.layouts.app')]
#[Title('Detail Stok')]
class StockDetail extends Component
{
    use AuthorizesRequests;
    use WithFileUploads;

    public Stock $stock;

    public string $notice = '';

    public string $name = '';

    public string $sku = '';

    public string $description = '';

    public int|string $quantity = 0;

    public string $unit = 'pcs';

    public int|float|string|null $price = null;

    public int|string $min_stock = 5;

    public $cover = null;

    public function mount(Stock $stock): void
    {
        $this->authorize('view', $stock);

        $this->stock = $stock;
        $this->fillForm();
    }

    public function save(): void
    {
        $this->authorize('update', $this->stock);
        $data = $this->validate(StockService::rules());

        $this->stock = app(StockService::class)->update($this->stock, $data);
        $this->fillForm();
        $this->notice = 'Perubahan berhasil disimpan.';
    }

    public function adjust(int $delta): void
    {
        $this->authorize('update', $this->stock);

        $this->stock = app(StockService::class)->adjustQuantity($this->stock, $delta);
        $this->quantity = $this->stock->quantity;
    }

    public function uploadCover(): void
    {
        $this->authorize('update', $this->stock);
        $this->validate(['cover' => ['required', 'image', 'max:2048']]);

        $this->stock = app(StockService::class)->storeCover($this->stock, $this->cover);
        $this->reset('cover');
        $this->notice = 'Cover berhasil diperbarui.';
    }

    public function removeCover(): void
    {
        $this->authorize('update', $this->stock);

        $this->stock = app(StockService::class)->removeCover($this->stock);
        $this->notice = 'Cover berhasil dihapus.';
    }

    public function render(): View
    {
        return view('livewire.stock.stock-detail', [
            'descriptionHtml' => Str::markdown((string) $this->stock->description, [
                'html_input' => 'strip',
                'allow_unsafe_links' => false,
            ]),
        ]);
    }

    private function fillForm(): void
    {
        $this->name = $this->stock->name;
        $this->sku = (string) $this->stock->sku;
        $this->description = (string) $this->stock->description;
        $this->quantity = $this->stock->quantity;
        $this->unit = $this->stock->unit;
        $this->price = $this->stock->price;
        $this->min_stock = $this->stock->min_stock;
    }
}
