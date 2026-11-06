<?php

namespace App\Features\Auth\Livewire;

use App\Features\Auth\Actions\LoginUserAction;
use Illuminate\Contracts\View\View;
use Illuminate\Support\Facades\Auth;
use Livewire\Attributes\Layout;
use Livewire\Attributes\Title;
use Livewire\Component;

#[Layout('components.layouts.app')]
#[Title('Masuk')]
class Login extends Component
{
    public string $login = '';

    public string $password = '';

    public bool $remember = false;

    public function submit(LoginUserAction $action): void
    {
        $this->validate([
            'login' => ['required', 'string'],
            'password' => ['required', 'string'],
        ]);

        if (! $action->execute($this->login, $this->password, $this->remember)) {
            $this->addError('login', 'Username/email atau password salah.');

            return;
        }

        session()->regenerate();

        $this->redirect(Auth::user()->isAdmin() ? '/admin/users' : '/stocks');
    }

    public function render(): View
    {
        return view('livewire.auth.login');
    }
}
