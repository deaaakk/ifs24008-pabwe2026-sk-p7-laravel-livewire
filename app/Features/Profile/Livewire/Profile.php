<?php

namespace App\Features\Profile\Livewire;

use App\Features\Profile\Services\ProfileService;
use Illuminate\Contracts\View\View;
use Illuminate\Support\Facades\Auth;
use Illuminate\Validation\Rule;
use Livewire\Attributes\Layout;
use Livewire\Attributes\Title;
use Livewire\Component;
use Livewire\WithFileUploads;

#[Layout('components.layouts.app')]
#[Title('Profil')]
class Profile extends Component
{
    use WithFileUploads;

    public string $name = '';

    public string $username = '';

    public string $email = '';

    public string $current_password = '';

    public string $password = '';

    public string $password_confirmation = '';

    public $photo = null;

    public string $notice = '';

    public function mount(): void
    {
        $user = Auth::user();

        $this->name = $user->name;
        $this->username = $user->username;
        $this->email = $user->email;
    }

    public function saveProfile(ProfileService $service): void
    {
        $id = Auth::id();

        $data = $this->validate([
            'name' => ['required', 'string', 'max:100'],
            'username' => ['required', 'alpha_dash', 'max:50', Rule::unique('users', 'username')->ignore($id)],
            'email' => ['required', 'email', 'max:150', Rule::unique('users', 'email')->ignore($id)],
        ]);

        $service->updateProfile(Auth::user(), $data);
        $this->notice = 'Profil berhasil diperbarui.';
    }

    public function savePassword(ProfileService $service): void
    {
        $this->validate([
            'current_password' => ['required', 'current_password'],
            'password' => ['required', 'string', 'min:8', 'confirmed'],
        ]);

        $service->updatePassword(Auth::user(), $this->password);
        $this->reset(['current_password', 'password', 'password_confirmation']);
        $this->notice = 'Password berhasil diubah.';
    }

    public function savePhoto(ProfileService $service): void
    {
        $this->validate(['photo' => ['required', 'image', 'max:2048']]);

        $service->updatePhoto(Auth::user(), $this->photo);
        $this->reset('photo');
        $this->notice = 'Foto profil berhasil diperbarui.';
    }

    public function removePhoto(ProfileService $service): void
    {
        $service->removePhoto(Auth::user());
        $this->notice = 'Foto profil dihapus.';
    }

    public function render(): View
    {
        return view('livewire.profile.profile', ['user' => Auth::user()]);
    }
}
