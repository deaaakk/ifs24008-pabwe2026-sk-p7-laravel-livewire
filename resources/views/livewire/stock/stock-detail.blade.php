<div class="mx-auto max-w-4xl space-y-4">
    <div class="flex items-center justify-between">
        <h1 class="text-2xl font-bold">{{ $stock->name }}</h1>
        <a href="/stocks" class="text-sm text-blue-600 hover:underline">← Kembali</a>
    </div>

    @if ($notice)
        <div class="rounded bg-green-100 px-3 py-2 text-sm text-green-800">{{ $notice }}</div>
    @endif

    <div class="grid gap-4 md:grid-cols-3">
        <div class="space-y-3 rounded-lg bg-white p-4 shadow">
            @if ($stock->coverUrl())
                <img src="{{ $stock->coverUrl() }}" alt="Cover {{ $stock->name }}" class="w-full rounded">
            @else
                <div class="flex h-40 items-center justify-center rounded bg-slate-100 text-slate-400">Belum ada cover</div>
            @endif

            <form wire:submit="uploadCover" class="space-y-2">
                <input type="file" wire:model="cover" accept="image/*" class="w-full text-sm">
                @error('cover') <p class="text-sm text-red-600">{{ $message }}</p> @enderror
                <div class="flex gap-2">
                    <button type="submit" class="rounded bg-slate-700 px-3 py-1 text-sm text-white">Unggah cover</button>
                    @if ($stock->cover_image_path)
                        <button type="button" wire:click="removeCover" class="rounded border px-3 py-1 text-sm">Hapus cover</button>
                    @endif
                </div>
            </form>

            <div>
                <div class="text-xs uppercase text-slate-500">Status</div>
                <span class="rounded px-2 py-1 text-xs {{ $stock->status->badgeClass() }}">{{ $stock->status->label() }}</span>
            </div>

            <div>
                <div class="mb-1 text-xs uppercase text-slate-500">Sesuaikan quantity</div>
                <div class="flex gap-1">
                    <button wire:click="adjust(-10)" class="rounded border px-2 py-1 text-sm">−10</button>
                    <button wire:click="adjust(-1)" class="rounded border px-2 py-1 text-sm">−1</button>
                    <button wire:click="adjust(1)" class="rounded border px-2 py-1 text-sm">+1</button>
                    <button wire:click="adjust(10)" class="rounded border px-2 py-1 text-sm">+10</button>
                </div>
            </div>
        </div>

        <form wire:submit="save" class="space-y-3 rounded-lg bg-white p-4 shadow md:col-span-2">
            <div>
                <label for="detail-name" class="block text-sm font-medium">Nama</label>
                <input id="detail-name" type="text" wire:model="name" class="w-full rounded border px-3 py-2">
                @error('name') <p class="text-sm text-red-600">{{ $message }}</p> @enderror
            </div>
            <div class="grid grid-cols-2 gap-3">
                <div>
                    <label for="detail-sku" class="block text-sm font-medium">SKU</label>
                    <input id="detail-sku" type="text" wire:model="sku" class="w-full rounded border px-3 py-2">
                    @error('sku') <p class="text-sm text-red-600">{{ $message }}</p> @enderror
                </div>
                <div>
                    <label for="detail-price" class="block text-sm font-medium">Harga (Rp)</label>
                    <input id="detail-price" type="number" min="0" step="any" wire:model="price" class="w-full rounded border px-3 py-2">
                    @error('price') <p class="text-sm text-red-600">{{ $message }}</p> @enderror
                </div>
                <div>
                    <label for="detail-quantity" class="block text-sm font-medium">Quantity</label>
                    <input id="detail-quantity" type="number" min="0" wire:model="quantity" class="w-full rounded border px-3 py-2">
                    @error('quantity') <p class="text-sm text-red-600">{{ $message }}</p> @enderror
                </div>
                <div>
                    <label for="detail-unit" class="block text-sm font-medium">Satuan</label>
                    <input id="detail-unit" type="text" wire:model="unit" class="w-full rounded border px-3 py-2">
                    @error('unit') <p class="text-sm text-red-600">{{ $message }}</p> @enderror
                </div>
                <div>
                    <label for="detail-min" class="block text-sm font-medium">Min. stok</label>
                    <input id="detail-min" type="number" min="0" wire:model="min_stock" class="w-full rounded border px-3 py-2">
                    @error('min_stock') <p class="text-sm text-red-600">{{ $message }}</p> @enderror
                </div>
            </div>

            <div>
                <div class="mb-1 text-sm font-medium">Deskripsi (Markdown)</div>
                <div wire:ignore><div id="md-editor"></div></div>
                @error('description') <p class="text-sm text-red-600">{{ $message }}</p> @enderror
            </div>

            <button type="submit" class="rounded bg-blue-600 px-4 py-2 text-sm text-white">Simpan perubahan</button>
        </form>
    </div>

    <div class="rounded-lg bg-white p-4 shadow">
        <h2 class="mb-2 text-sm font-semibold uppercase text-slate-500">Pratinjau deskripsi</h2>
        <div class="space-y-2 text-sm">{!! $descriptionHtml !!}</div>
    </div>

    @assets
    <link rel="stylesheet" href="https://uicdn.toast.com/editor/latest/toastui-editor.min.css">
    <script src="https://uicdn.toast.com/editor/latest/toastui-editor-all.min.js"></script>
    @endassets

    @script
    <script>
        const editor = new toastui.Editor({
            el: document.querySelector('#md-editor'),
            height: '280px',
            initialEditType: 'markdown',
            previewStyle: 'vertical',
            initialValue: $wire.description ?? '',
        });

        editor.on('change', () => {
            $wire.description = editor.getMarkdown();
        });
    </script>
    @endscript
</div>
