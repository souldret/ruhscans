<?php

use Illuminate\Support\Facades\Auth;
use Illuminate\Validation\ValidationException;
use Livewire\Attributes\Layout;
use Livewire\Volt\Component;

new #[Layout('layouts.guest')] class extends Component
{
    public string $password = '';

    /**
     * Confirm the current user's password.
     */
    public function confirmPassword(): void
    {
        $this->validate([
            'password' => ['required', 'string'],
        ]);

        if (! Auth::guard('web')->validate([
            'email' => Auth::user()->email,
            'password' => $this->password,
        ])) {
            throw ValidationException::withMessages([
                'password' => __('auth.password'),
            ]);
        }

        session(['auth.password_confirmed_at' => time()]);

        $this->redirectIntended(default: route('dashboard', absolute: false), navigate: true);
    }
}; ?>

<div>
    <p class="text-sm font-semibold uppercase tracking-[0.2em] text-gold">Güvenli alan</p>
    <h1 class="mt-2 font-display text-3xl text-mist">Şifreni doğrula</h1>
    <p class="mt-2 text-base leading-7 text-mist/60">Devam etmek için hesap şifreni gir.</p>

    <form wire:submit="confirmPassword" class="mt-6 space-y-5">
        <div>
            <x-input-label for="password" :value="__('Şifre')" />
            <x-text-input wire:model="password" id="password" class="block w-full" type="password" name="password" required autocomplete="current-password" placeholder="Şifren" />
            <x-input-error :messages="$errors->get('password')" />
        </div>

        <x-primary-button>{{ __('Doğrula') }}</x-primary-button>
    </form>
</div>
