<?php

namespace App\Features\Stock\Services;

use App\Features\Stock\Contracts\StockRepositoryInterface;
use App\Features\Stock\Enums\StockStatus;
use App\Features\Stock\Models\Stock;
use App\Models\User;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;

class StockService
{
    public function __construct(private readonly StockRepositoryInterface $repository) {}

    /**
     * Aturan validasi form stok (dipakai StockList & StockDetail).
     *
     * @return array<string, array<int, string>>
     */
    public static function rules(): array
    {
        return [
            'name' => ['required', 'string', 'max:150'],
            'sku' => ['nullable', 'string', 'max:64'],
            'description' => ['nullable', 'string', 'max:10000'],
            'quantity' => ['required', 'integer', 'min:0'],
            'unit' => ['required', 'string', 'max:20'],
            'price' => ['nullable', 'numeric', 'min:0'],
            'min_stock' => ['required', 'integer', 'min:0'],
        ];
    }

    public function list(User $user, ?string $search = null, ?string $status = null, int $perPage = 10): LengthAwarePaginator
    {
        return $this->repository->paginateForUser($user->id, $search, $status, $perPage);
    }

    /** @return Collection<int, Stock> */
    public function all(User $user): Collection
    {
        return $this->repository->allForUser($user->id);
    }

    public function find(int $id): Stock
    {
        return $this->repository->findOrFail($id);
    }

    /** @param array<string, mixed> $data */
    public function create(User $user, array $data): Stock
    {
        return $this->repository->create($this->normalize($data) + ['user_id' => $user->id]);
    }

    /** @param array<string, mixed> $data */
    public function update(Stock $stock, array $data): Stock
    {
        return $this->repository->update($stock, $this->normalize($data));
    }

    public function delete(Stock $stock): void
    {
        $this->deleteCoverFile($stock);
        $this->repository->delete($stock);
    }

    /** Tambah/kurangi quantity (tidak pernah di bawah 0) dan sinkronkan status. */
    public function adjustQuantity(Stock $stock, int $delta): Stock
    {
        $quantity = max(0, $stock->quantity + $delta);

        return $this->repository->update($stock, [
            'quantity' => $quantity,
            'status' => StockStatus::fromQuantity($quantity, $stock->min_stock),
        ]);
    }

    public function storeCover(Stock $stock, UploadedFile $file): Stock
    {
        $path = $file->store('stock-covers', 'public');
        $this->deleteCoverFile($stock);

        return $this->repository->update($stock, ['cover_image_path' => $path]);
    }

    public function removeCover(Stock $stock): Stock
    {
        $this->deleteCoverFile($stock);

        return $this->repository->update($stock, ['cover_image_path' => null]);
    }

    private function deleteCoverFile(Stock $stock): void
    {
        if ($stock->cover_image_path) {
            Storage::disk('public')->delete($stock->cover_image_path);
        }
    }

    /**
     * Rapikan input dan turunkan status dari quantity vs min_stock.
     *
     * @param  array<string, mixed>  $data
     * @return array<string, mixed>
     */
    private function normalize(array $data): array
    {
        $quantity = max(0, (int) ($data['quantity'] ?? 0));
        $minStock = max(0, (int) ($data['min_stock'] ?? 5));

        return [
            'name' => trim((string) $data['name']),
            'sku' => filled($data['sku'] ?? null) ? trim((string) $data['sku']) : null,
            'description' => filled($data['description'] ?? null) ? (string) $data['description'] : null,
            'quantity' => $quantity,
            'unit' => filled($data['unit'] ?? null) ? trim((string) $data['unit']) : 'pcs',
            'price' => filled($data['price'] ?? null) ? (float) $data['price'] : null,
            'min_stock' => $minStock,
            'status' => StockStatus::fromQuantity($quantity, $minStock),
        ];
    }
}
