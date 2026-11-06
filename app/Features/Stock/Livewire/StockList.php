<?php

namespace App\Features\Stock\Livewire;

use App\Features\Ai\Contracts\AiClientInterface;
use App\Features\Ai\DTO\GeneratedStockItem;
use App\Features\Stock\Enums\StockStatus;
use App\Features\Stock\Models\Stock;
use App\Features\Stock\Services\StockExcelService;
use App\Features\Stock\Services\StockService;
use Illuminate\Contracts\View\View;
use Illuminate\Foundation\Auth\Access\AuthorizesRequests;
use Illuminate\Support\Facades\Auth;
use Livewire\Attributes\Layout;
use Livewire\Attributes\Title;
use Livewire\Attributes\Url;
use Livewire\Component;
use Livewire\WithFileUploads;
use Livewire\WithPagination;
use Symfony\Component\HttpFoundation\StreamedResponse;
use Throwable;

#[Layout('components.layouts.app')]
#[Title('Stok Barang')]
class StockList extends Component
{
    use AuthorizesRequests;
    use WithFileUploads;
    use WithPagination;

    #[Url(as: 'q')]
    public string $search = '';

    #[Url(as: 'status')]
    public string $statusFilter = '';

    public string $notice = '';

    // Form create/edit
    public bool $showForm = false;

    public ?int $editingId = null;

    public string $name = '';

    public string $sku = '';

    public string $description = '';

    public int|string $quantity = 0;

    public string $unit = 'pcs';

    public int|float|string|null $price = null;

    public int|string $min_stock = 5;

    // Import Excel
    public $importFile = null;

    // Generator AI
    public bool $showAi = false;

    public string $aiPrompt = '';

    public int|string $aiCount = 5;

    /** @var array<int, array<string, mixed>> */
    public array $aiItems = [];

    /** @var array<int, int|string> */
    public array $aiSelected = [];

    public function updatingSearch(): void
    {
        $this->resetPage();
    }

    public function updatingStatusFilter(): void
    {
        $this->resetPage();
    }

    public function openCreate(): void
    {
        $this->authorize('create', Stock::class);
        $this->resetForm();
        $this->showForm = true;
    }

    public function openEdit(int $id): void
    {
        $stock = $this->ownedStock($id);

        $this->editingId = $stock->id;
        $this->name = $stock->name;
        $this->sku = (string) $stock->sku;
        $this->description = (string) $stock->description;
        $this->quantity = $stock->quantity;
        $this->unit = $stock->unit;
        $this->price = $stock->price;
        $this->min_stock = $stock->min_stock;
        $this->showForm = true;
    }

    public function save(): void
    {
        $data = $this->validate(StockService::rules());

        if ($this->editingId) {
            $this->service()->update($this->ownedStock($this->editingId), $data);
            $this->notice = 'Stok berhasil diperbarui.';
        } else {
            $this->authorize('create', Stock::class);
            $this->service()->create(Auth::user(), $data);
            $this->notice = 'Stok berhasil ditambahkan.';
        }

        $this->cancel();
    }

    public function cancel(): void
    {
        $this->resetForm();
        $this->showForm = false;
    }

    public function delete(int $id): void
    {
        $this->service()->delete($this->ownedStock($id, 'delete'));
        $this->notice = 'Stok berhasil dihapus.';
    }

    public function adjust(int $id, int $delta): void
    {
        $this->service()->adjustQuantity($this->ownedStock($id), $delta);
    }

    public function exportExcel(StockExcelService $excel): StreamedResponse
    {
        $stocks = $this->service()->all(Auth::user());

        return response()->streamDownload(fn () => print ($excel->export($stocks)), 'stok-'.now()->format('Ymd-His').'.xlsx');
    }

    public function importExcel(StockExcelService $excel): void
    {
        $this->authorize('create', Stock::class);
        $this->validate(['importFile' => ['required', 'file', 'extensions:xlsx,xls', 'max:5120']]);

        try {
            $count = $excel->import(Auth::user(), $this->importFile->getRealPath());
        } catch (Throwable) {
            $this->addError('importFile', 'File Excel tidak dapat dibaca.');

            return;
        }

        $this->reset('importFile');
        $this->notice = "{$count} stok berhasil diimpor.";
        $this->resetPage();
    }

    public function openAi(): void
    {
        $this->authorize('create', Stock::class);
        $this->closeAi();
        $this->showAi = true;
    }

    public function closeAi(): void
    {
        $this->reset(['showAi', 'aiPrompt', 'aiItems', 'aiSelected']);
        $this->aiCount = 5;
        $this->resetValidation();
    }

    public function generateAi(AiClientInterface $ai): void
    {
        $this->authorize('create', Stock::class);
        $this->validate([
            'aiPrompt' => ['required', 'string', 'min:3', 'max:500'],
            'aiCount' => ['required', 'integer', 'between:1,10'],
        ]);

        try {
            $items = $ai->generate($this->aiPrompt, (int) $this->aiCount);
        } catch (Throwable $e) {
            $this->addError('aiPrompt', 'Gagal membuat saran: '.$e->getMessage());

            return;
        }

        if ($items === []) {
            $this->addError('aiPrompt', 'AI tidak menghasilkan item. Coba prompt lain.');

            return;
        }

        $this->aiItems = array_map(fn (GeneratedStockItem $item) => $item->toArray(), array_values($items));
        $this->aiSelected = array_keys($this->aiItems);
    }

    public function saveAiItems(): void
    {
        $this->authorize('create', Stock::class);
        $this->validate(['aiSelected' => ['required', 'array', 'min:1']]);

        $chosen = array_intersect_key($this->aiItems, array_flip($this->aiSelected));

        foreach ($chosen as $item) {
            $this->service()->create(Auth::user(), $item);
        }

        $this->notice = count($chosen).' stok dari AI berhasil disimpan.';
        $this->closeAi();
        $this->resetPage();
    }

    public function render(): View
    {
        return view('livewire.stock.stock-list', [
            'stocks' => $this->service()->list(Auth::user(), $this->search, $this->statusFilter ?: null),
            'statuses' => StockStatus::cases(),
        ]);
    }

    private function service(): StockService
    {
        return app(StockService::class);
    }

    private function ownedStock(int $id, string $ability = 'update'): Stock
    {
        $stock = $this->service()->find($id);
        $this->authorize($ability, $stock);

        return $stock;
    }

    private function resetForm(): void
    {
        $this->reset(['editingId', 'name', 'sku', 'description', 'price']);
        $this->quantity = 0;
        $this->unit = 'pcs';
        $this->min_stock = 5;
        $this->resetValidation();
    }
}
