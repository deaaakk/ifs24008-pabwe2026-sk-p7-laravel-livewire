<div class="space-y-4">
    <div class="flex flex-wrap items-center justify-between gap-2">
        <h1 class="text-2xl font-bold">Daftar Pengguna</h1>
        <input type="search" wire:model.live.debounce.300ms="search" placeholder="Cari nama / username / email…"
               class="rounded border border-slate-300 px-3 py-2 text-sm">
    </div>

    <div class="overflow-x-auto rounded-lg bg-white shadow">
        <table class="min-w-full text-sm">
            <thead class="bg-slate-50 text-left text-xs uppercase text-slate-600">
                <tr>
                    <th class="px-4 py-3">Nama</th>
                    <th class="px-4 py-3">Username</th>
                    <th class="px-4 py-3">Email</th>
                    <th class="px-4 py-3">Jumlah stok</th>
                    <th class="px-4 py-3 text-right">Lihat</th>
                </tr>
            </thead>
            <tbody class="divide-y">
                @forelse ($users as $user)
                    <tr wire:key="user-{{ $user->id }}">
                        <td class="px-4 py-3 font-medium">{{ $user->name }}</td>
                        <td class="px-4 py-3">{{ $user->username }}</td>
                        <td class="px-4 py-3">{{ $user->email }}</td>
                        <td class="px-4 py-3">{{ $user->stocks_count }}</td>
                        <td class="px-4 py-3 text-right">
                            <a href="/admin/users/{{ $user->id }}/stocks" class="text-blue-700 hover:underline">Lihat stok</a>
                        </td>
                    </tr>
                @empty
                    <tr><td colspan="5" class="px-4 py-8 text-center text-slate-600">Belum ada pengguna.</td></tr>
                @endforelse
            </tbody>
        </table>
    </div>

    {{ $users->links() }}
</div>
