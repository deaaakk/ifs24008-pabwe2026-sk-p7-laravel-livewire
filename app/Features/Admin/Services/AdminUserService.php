<?php

namespace App\Features\Admin\Services;

use App\Features\Stock\Contracts\StockRepositoryInterface;
use App\Features\Stock\Enums\StockStatus;
use App\Models\User;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;

class AdminUserService
{
    public function __construct(private readonly StockRepositoryInterface $stocks) {}

    /** Daftar member + jumlah stok (read-only). */
    public function paginateUsers(?string $search = null, int $perPage = 10): LengthAwarePaginator
    {
        return User::query()
            ->where('role', 'member')
            ->withCount('stocks')
            ->when($search, fn ($query) => $query->where(function ($q) use ($search) {
                $q->where('name', 'like', "%{$search}%")
                    ->orWhere('username', 'like', "%{$search}%")
                    ->orWhere('email', 'like', "%{$search}%");
            }))
            ->orderBy('name')
            ->paginate($perPage);
    }

    public function stocksOf(User $user, ?string $search = null, ?string $status = null, int $perPage = 10): LengthAwarePaginator
    {
        return $this->stocks->paginateForUser($user->id, $search, $status, $perPage);
    }

    /** @return array{items: int, quantity: int, low: int, out: int} */
    public function summaryOf(User $user): array
    {
        $all = $this->stocks->allForUser($user->id);

        return [
            'items' => $all->count(),
            'quantity' => (int) $all->sum('quantity'),
            'low' => $all->where('status', StockStatus::LowStock)->count(),
            'out' => $all->where('status', StockStatus::OutOfStock)->count(),
        ];
    }
}
