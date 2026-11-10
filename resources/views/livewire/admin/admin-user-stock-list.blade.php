<div class="space-y-4">
    <div class="flex items-center justify-between">
        <div>
            <h1 class="text-2xl font-bold">Stok milik {{ $user->name }}</h1>
            <p class="text-sm text-slate-600">{{ '@'.$user->username }} · hanya baca</p>
        </div>
        <a href="/admin/users" class="text-sm text-blue-700 hover:underline">← Kembali</a>
    </div>

    <div class="grid grid-cols-2 gap-3 md:grid-cols-4">
        <div class="rounded-lg bg-white p-4 shadow"><div class="text-xs uppercase text-slate-600">Jenis barang</div><div class="text-2xl font-bold">{{ $summary['items'] }}</div></div>
        <div class="rounded-lg bg-white p-4 shadow"><div class="text-xs uppercase text-slate-600">Total qty</div><div class="text-2xl font-bold">{{ $summary['quantity'] }}</div></div>
        <div class="rounded-lg bg-white p-4 shadow"><div class="text-xs uppercase text-slate-600">Menipis</div><div class="text-2xl font-bold text-yellow-800">{{ $summary['low'] }}</div></div>
        <div class="rounded-lg bg-white p-4 shadow"><div class="text-xs uppercase text-slate-600">Habis</div><div class="text-2xl font-bold text-red-700">{{ $summary['out'] }}</div></div>
    </div>

    <div class="flex flex-wrap gap-2">
        <input type="search" wire:model.live.debounce.300ms="search" placeholder="Cari nama / SKU…"
               class="rounded border border-slate-300 px-3 py-2 text-sm">
        <select aria-label="Filter status" wire:model.live="statusFilter" class="rounded border border-slate-300 px-3 py-2 text-sm">
            <option value="">Semua status</option>
            @foreach ($statuses as $status)
                <option value="{{ $status->value }}">{{ $status->label() }}</option>
            @endforeach
        </select>
    </div>

    <div class="overflow-x-auto rounded-lg bg-white shadow">
        <table class="min-w-full text-sm">
            <thead class="bg-slate-50 text-left text-xs uppercase text-slate-600">
                <tr>
                    <th class="px-4 py-3">Barang</th>
                    <th class="px-4 py-3">Qty</th>
                    <th class="px-4 py-3">Min. stok</th>
                    <th class="px-4 py-3">Harga</th>
                    <th class="px-4 py-3">Status</th>
                </tr>
            </thead>
            <tbody class="divide-y">
                @forelse ($stocks as $stock)
                    <tr wire:key="admin-stock-{{ $stock->id }}">
                        <td class="px-4 py-3">
                            <div class="font-medium">{{ $stock->name }}</div>
                            <div class="text-xs text-slate-600">{{ $stock->sku }}</div>
                        </td>
                        <td class="px-4 py-3">{{ $stock->quantity }} {{ $stock->unit }}</td>
                        <td class="px-4 py-3">{{ $stock->min_stock }}</td>
                        <td class="px-4 py-3">{{ $stock->price !== null ? 'Rp '.number_format((float) $stock->price, 0, ',', '.') : '-' }}</td>
                        <td class="px-4 py-3">
                            <span class="rounded px-2 py-1 text-xs {{ $stock->status->badgeClass() }}">{{ $stock->status->label() }}</span>
                        </td>
                    </tr>
                @empty
                    <tr><td colspan="5" class="px-4 py-8 text-center text-slate-600">Belum ada stok.</td></tr>
                @endforelse
            </tbody>
        </table>
    </div>

    {{ $stocks->links() }}
</div>
