<div class="mx-auto max-w-3xl space-y-4">
    <h1 class="text-2xl font-bold">Profil</h1>

    @if ($notice)
        <div class="rounded bg-green-100 px-3 py-2 text-sm text-green-800">{{ $notice }}</div>
    @endif

    <div class="rounded-lg bg-white p-4 shadow">
        <h2 class="mb-3 font-semibold">Foto profil</h2>
        <div class="flex items-center gap-4">
            @if ($user->photoUrl())
                <img src="{{ $user->photoUrl() }}" alt="Foto profil" class="h-20 w-20 rounded-full object-cover">
            @else
                <div class="flex h-20 w-20 items-center justify-center rounded-full bg-slate-200 text-2xl">👤</div>
            @endif

            <form wire:submit="savePhoto" class="space-y-2">
                <input type="file" aria-label="Pilih foto profil" wire:model="photo" accept="image/*" class="text-sm">
                @error('photo') <p class="text-sm text-red-700">{{ $message }}</p> @enderror
                <div class="flex gap-2">
                    <button type="submit" class="rounded bg-slate-700 px-3 py-1 text-sm text-white">Unggah foto</button>
                    @if ($user->profile_photo_path)
                        <button type="button" wire:click="removePhoto" class="rounded border px-3 py-1 text-sm">Hapus foto</button>
                    @endif
                </div>
            </form>
        </div>
    </div>

    <form wire:submit="saveProfile" class="space-y-3 rounded-lg bg-white p-4 shadow">
        <h2 class="font-semibold">Data diri</h2>
        <div>
            <label for="profile-name" class="block text-sm font-medium">Nama</label>
            <input id="profile-name" type="text" wire:model="name" class="w-full rounded border px-3 py-2">
            @error('name') <p class="text-sm text-red-700">{{ $message }}</p> @enderror
        </div>
        <div>
            <label for="profile-username" class="block text-sm font-medium">Username</label>
            <input id="profile-username" type="text" wire:model="username" class="w-full rounded border px-3 py-2">
            @error('username') <p class="text-sm text-red-700">{{ $message }}</p> @enderror
        </div>
        <div>
            <label for="profile-email" class="block text-sm font-medium">Email</label>
            <input id="profile-email" type="email" wire:model="email" class="w-full rounded border px-3 py-2">
            @error('email') <p class="text-sm text-red-700">{{ $message }}</p> @enderror
        </div>
        <button type="submit" class="rounded bg-blue-600 px-4 py-2 text-sm text-white">Simpan profil</button>
    </form>

    <form wire:submit="savePassword" class="space-y-3 rounded-lg bg-white p-4 shadow">
        <h2 class="font-semibold">Ubah password</h2>
        <div>
            <label for="profile-current" class="block text-sm font-medium">Password saat ini</label>
            <input id="profile-current" type="password" wire:model="current_password" class="w-full rounded border px-3 py-2">
            @error('current_password') <p class="text-sm text-red-700">{{ $message }}</p> @enderror
        </div>
        <div>
            <label for="profile-password" class="block text-sm font-medium">Password baru</label>
            <input id="profile-password" type="password" wire:model="password" class="w-full rounded border px-3 py-2">
            @error('password') <p class="text-sm text-red-700">{{ $message }}</p> @enderror
        </div>
        <div>
            <label for="profile-confirm" class="block text-sm font-medium">Konfirmasi password baru</label>
            <input id="profile-confirm" type="password" wire:model="password_confirmation" class="w-full rounded border px-3 py-2">
        </div>
        <button type="submit" class="rounded bg-blue-600 px-4 py-2 text-sm text-white">Ubah password</button>
    </form>
</div>
