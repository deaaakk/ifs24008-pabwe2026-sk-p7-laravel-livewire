<div class="w-full max-w-md rounded-xl bg-white p-8 shadow">
    <h1 class="text-2xl font-bold">Daftar</h1>
    <p class="mb-6 text-sm text-slate-600">Buat akun member baru</p>

    <form wire:submit="submit" class="space-y-4">
        @foreach ([
            ['name', 'Nama lengkap', 'text'],
            ['username', 'Username', 'text'],
            ['email', 'Email', 'email'],
            ['password', 'Password', 'password'],
            ['password_confirmation', 'Konfirmasi password', 'password'],
        ] as [$field, $label, $type])
            <div wire:key="register-{{ $field }}">
                <label for="register-{{ $field }}" class="mb-1 block text-sm font-medium">{{ $label }}</label>
                <input id="register-{{ $field }}" type="{{ $type }}" wire:model="{{ $field }}"
                       class="w-full rounded border border-slate-300 px-3 py-2">
                @error($field) <p class="mt-1 text-sm text-red-700">{{ $message }}</p> @enderror
            </div>
        @endforeach

        <button id="register-submit" type="submit"
                class="w-full rounded bg-blue-600 px-4 py-2 font-medium text-white hover:bg-blue-700">Daftar</button>
    </form>

    <p class="mt-4 text-center text-sm">Sudah punya akun? <a href="/login" class="text-blue-700 underline">Masuk</a></p>
</div>
