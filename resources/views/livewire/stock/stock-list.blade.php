<div class="space-y-4">
    <div class="flex flex-wrap items-center justify-between gap-2">
        <h1 class="text-2xl font-bold">Stok Barang</h1>
        <div class="flex flex-wrap gap-2">
            <button wire:click="openCreate" class="rounded bg-blue-600 px-3 py-2 text-sm text-white">+ Tambah Stok</button>
            <button wire:click="exportExcel" class="rounded bg-emerald-700 px-3 py-2 text-sm text-white">Export Excel</button>
            <button wire:click="openAi" class="rounded bg-violet-600 px-3 py-2 text-sm text-white">Generate AI</button>
        </div>
    </div>

    @if ($notice)
        <div class="rounded bg-green-100 px-3 py-2 text-sm text-green-800">{{ $notice }}</div>
    @endif

    <div class="flex flex-wrap items-end gap-2">
        <input type="search" wire:model.live.debounce.300ms="search" placeholder="Cari nama / SKU…"
               class="rounded border border-slate-300 px-3 py-2 text-sm">
        <select aria-label="Filter status" wire:model.live="statusFilter" class="rounded border border-slate-300 px-3 py-2 text-sm">
            <option value="">Semua status</option>
            @foreach ($statuses as $status)
                <option value="{{ $status->value }}">{{ $status->label() }}</option>
            @endforeach
        </select>

        <form wire:submit="importExcel" class="ml-auto flex items-center gap-2">
            <input type="file" aria-label="Pilih berkas Excel" wire:model="importFile" accept=".xlsx,.xls" class="text-sm">
            <button type="submit" class="rounded bg-slate-700 px-3 py-2 text-sm text-white">Import Excel</button>
        </form>
    </div>
    @error('importFile') <p class="text-sm text-red-700">{{ $message }}</p> @enderror

    <div class="overflow-x-auto rounded-lg bg-white shadow">
        <table class="min-w-full text-sm">
            <thead class="bg-slate-50 text-left text-xs uppercase text-slate-600">
                <tr>
                    <th class="px-4 py-3">Barang</th>
                    <th class="px-4 py-3">Qty</th>
                    <th class="px-4 py-3">Sesuaikan</th>
                    <th class="px-4 py-3">Harga</th>
                    <th class="px-4 py-3">Status</th>
                    <th class="px-4 py-3 text-right">Aksi</th>
                </tr>
            </thead>
            <tbody class="divide-y">
                @forelse ($stocks as $stock)
                    <tr wire:key="stock-{{ $stock->id }}">
                        <td class="px-4 py-3">
                            <a href="/stocks/{{ $stock->id }}" class="font-medium text-blue-700 hover:underline">{{ $stock->name }}</a>
                            <div class="text-xs text-slate-600">{{ $stock->sku }}</div>
                        </td>
                        <td class="px-4 py-3">{{ $stock->quantity }} {{ $stock->unit }}</td>
                        <td class="px-4 py-3">
                            <div class="flex gap-1">
                                <button wire:click="adjust({{ $stock->id }}, -1)" class="rounded border px-2 py-1">−</button>
                                <button wire:click="adjust({{ $stock->id }}, 1)" class="rounded border px-2 py-1">+</button>
                            </div>
                        </td>
                        <td class="px-4 py-3">{{ $stock->price !== null ? 'Rp '.number_format((float) $stock->price, 0, ',', '.') : '-' }}</td>
                        <td class="px-4 py-3">
                            <span class="rounded px-2 py-1 text-xs {{ $stock->status->badgeClass() }}">{{ $stock->status->label() }}</span>
                        </td>
                        <td class="px-4 py-3 text-right">
                            <button wire:click="openEdit({{ $stock->id }})" class="text-blue-700 hover:underline">Edit</button>
                            <button wire:click="delete({{ $stock->id }})" wire:confirm="Hapus stok ini?" class="ml-2 text-red-700 hover:underline">Hapus</button>
                        </td>
                    </tr>
                @empty
                    <tr><td colspan="6" class="px-4 py-8 text-center text-slate-600">Belum ada stok.</td></tr>
                @endforelse
            </tbody>
        </table>
    </div>

    {{ $stocks->links() }}

    @if ($showForm)
        <div class="fixed inset-0 z-40 flex items-center justify-center bg-black/50 p-4">
            <form wire:submit="save" class="max-h-full w-full max-w-lg space-y-3 overflow-y-auto rounded-xl bg-white p-6 shadow-xl">
                <h2 class="text-lg font-bold">{{ $editingId ? 'Edit Stok' : 'Tambah Stok' }}</h2>

                <div>
                    <label for="stock-name" class="block text-sm font-medium">Nama</label>
                    <input id="stock-name" type="text" wire:model="name" class="w-full rounded border px-3 py-2">
                    @error('name') <p class="text-sm text-red-700">{{ $message }}</p> @enderror
                </div>
                <div>
                    <label for="stock-sku" class="block text-sm font-medium">SKU</label>
                    <input id="stock-sku" type="text" wire:model="sku" class="w-full rounded border px-3 py-2">
                    @error('sku') <p class="text-sm text-red-700">{{ $message }}</p> @enderror
                </div>
                <div class="grid grid-cols-3 gap-3">
                    <div>
                        <label for="stock-quantity" class="block text-sm font-medium">Quantity</label>
                        <input id="stock-quantity" type="number" min="0" wire:model="quantity" class="w-full rounded border px-3 py-2">
                        @error('quantity') <p class="text-sm text-red-700">{{ $message }}</p> @enderror
                    </div>
                    <div>
                        <label for="stock-unit" class="block text-sm font-medium">Satuan</label>
                        <input id="stock-unit" type="text" wire:model="unit" class="w-full rounded border px-3 py-2">
                        @error('unit') <p class="text-sm text-red-700">{{ $message }}</p> @enderror
                    </div>
                    <div>
                        <label for="stock-min" class="block text-sm font-medium">Min. stok</label>
                        <input id="stock-min" type="number" min="0" wire:model="min_stock" class="w-full rounded border px-3 py-2">
                        @error('min_stock') <p class="text-sm text-red-700">{{ $message }}</p> @enderror
                    </div>
                </div>
                <div>
                    <label for="stock-price" class="block text-sm font-medium">Harga (Rp)</label>
                    <input id="stock-price" type="number" min="0" step="any" wire:model="price" class="w-full rounded border px-3 py-2">
                    @error('price') <p class="text-sm text-red-700">{{ $message }}</p> @enderror
                </div>
                <div>
                    <label for="stock-description" class="block text-sm font-medium">Deskripsi (Markdown)</label>
                    <textarea id="stock-description" rows="4" wire:model="description" class="w-full rounded border px-3 py-2"></textarea>
                    @error('description') <p class="text-sm text-red-700">{{ $message }}</p> @enderror
                </div>
                <p class="text-xs text-slate-600">Status dihitung otomatis dari quantity dan min. stok.</p>

                <div class="flex justify-end gap-2 pt-2">
                    <button type="button" wire:click="cancel" class="rounded border px-4 py-2 text-sm">Batal</button>
                    <button type="submit" class="rounded bg-blue-600 px-4 py-2 text-sm text-white">Simpan</button>
                </div>
            </form>
        </div>
    @endif

    @if ($showAi)
        <div class="fixed inset-0 z-40 flex items-center justify-center bg-black/50 p-4">
            <div class="max-h-full w-full max-w-2xl space-y-3 overflow-y-auto rounded-xl bg-white p-6 shadow-xl">
                <h2 class="text-lg font-bold">Generate Stok dengan AI</h2>

                <form wire:submit="generateAi" class="flex flex-wrap items-end gap-2">
                    <div class="min-w-0 flex-1">
                        <label for="ai-prompt" class="block text-sm font-medium">Jenis barang / kebutuhan</label>
                        <input id="ai-prompt" type="text" wire:model="aiPrompt" placeholder="mis. perlengkapan alat tulis kantor"
                               class="w-full rounded border px-3 py-2">
                    </div>
                    <div>
                        <label for="ai-count" class="block text-sm font-medium">Jumlah</label>
                        <input id="ai-count" type="number" min="1" max="10" wire:model="aiCount" class="w-20 rounded border px-3 py-2">
                    </div>
                    <button type="submit" class="rounded bg-violet-600 px-4 py-2 text-sm text-white">Generate</button>
                </form>
                @error('aiPrompt') <p class="text-sm text-red-700">{{ $message }}</p> @enderror
                @error('aiCount') <p class="text-sm text-red-700">{{ $message }}</p> @enderror

                @if ($aiItems)
                    <form wire:submit="saveAiItems" class="space-y-2">
                        <p class="text-sm text-slate-600">Pilih item yang ingin disimpan:</p>
                        @foreach ($aiItems as $index => $item)
                            <label wire:key="ai-{{ $index }}" class="flex items-start gap-2 rounded border p-2 text-sm">
                                <input type="checkbox" value="{{ $index }}" wire:model="aiSelected" class="mt-1">
                                <span>
                                    <strong>{{ $item['name'] }}</strong>
                                    <span class="text-slate-600">· {{ $item['quantity'] }} {{ $item['unit'] }}
                                        @if ($item['price'] !== null) · Rp {{ number_format((float) $item['price'], 0, ',', '.') }} @endif
                                    </span>
                                    <span class="block text-xs text-slate-600">{{ $item['sku'] }}</span>
                                </span>
                            </label>
                        @endforeach
                        @error('aiSelected') <p class="text-sm text-red-700">{{ $message }}</p> @enderror
                        <button type="submit" class="rounded bg-blue-600 px-4 py-2 text-sm text-white">Simpan yang dipilih</button>
                    </form>
                @endif

                <div class="text-right">
                    <button type="button" wire:click="closeAi" class="rounded border px-4 py-2 text-sm">Tutup</button>
                </div>
            </div>
        </div>
    @endif
</div>
