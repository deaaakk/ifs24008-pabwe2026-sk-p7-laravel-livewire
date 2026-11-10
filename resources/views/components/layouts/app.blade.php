<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>{{ $title ?? config('app.name', 'Stock Barang') }}</title>
    <meta name="description" content="Aplikasi Stock Barang untuk mengelola stok, mengimpor dan mengekspor Excel, serta membuat item dengan AI.">
    <link rel="icon" href="/favicon.svg" type="image/svg+xml">
    <script src="https://cdn.tailwindcss.com"></script>
    @livewireStyles
</head>
<body class="bg-slate-100 text-slate-800 antialiased">
@auth
    <div class="flex min-h-screen">
        <aside class="flex w-60 shrink-0 flex-col bg-slate-900 p-4 text-slate-100">
            <div class="mb-6 text-lg font-bold">📦 Stock Barang</div>

            <nav class="flex-1 space-y-1 text-sm">
                @if (auth()->user()->isAdmin())
                    <a href="/admin/users" class="block rounded px-3 py-2 hover:bg-slate-800">Pengguna</a>
                @else
                    <a href="/stocks" class="block rounded px-3 py-2 hover:bg-slate-800">Stok Barang</a>
                @endif
                <a href="/profile" class="block rounded px-3 py-2 hover:bg-slate-800">Profil</a>
            </nav>

            <div class="mb-2 text-sm">
                {{ auth()->user()->name }}
                <span class="text-xs uppercase text-slate-400">({{ auth()->user()->role }})</span>
            </div>
            <form method="POST" action="/logout">
                @csrf

                <button type="submit" class="w-full rounded bg-slate-700 px-3 py-2 text-sm hover:bg-slate-600">Keluar</button>
            </form>
        </aside>

        <main class="min-w-0 flex-1 p-6">{{ $slot }}</main>
    </div>
@else
    <main class="flex min-h-screen items-center justify-center p-4">{{ $slot }}</main>
@endauth
@livewireScripts
</body>
</html>