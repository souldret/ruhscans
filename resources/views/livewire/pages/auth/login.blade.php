<?php

use App\Livewire\Forms\LoginForm;
use App\Services\Security\TurnstileService;
use Illuminate\Support\Facades\Session;
use Livewire\Attributes\Layout;
use Livewire\Volt\Component;

new #[Layout('layouts.guest')] class extends Component
{
    public LoginForm $form;
    public string $turnstileToken = '';

    /**
     * Handle an incoming authentication request.
     */
    public function login(TurnstileService $turnstile): void
    {
        $this->validate();

        if (! $turnstile->verify($this->turnstileToken)) {
            $this->addError('turnstileToken', 'Doğrulama başarısız. Lütfen tekrar deneyin.');
            $this->dispatch('turnstile-reset');

            return;
        }

        $this->form->authenticate();

        Session::regenerate();

        $this->redirectIntended(default: route('dashboard', absolute: false), navigate: true);
    }
}; ?>

<div>
    <p class="text-sm font-semibold uppercase tracking-[0.2em] text-gold">Gece Kütüphanesi</p>
    <h1 class="mt-2 font-display text-3xl text-mist">Tekrar hoş geldin</h1>
    <p class="mt-2 text-base leading-7 text-mist/60">Kütüphanene dön, kaldığın yerden oku.</p>

    <x-auth-session-status :status="session('status')" />

    <form wire:submit="login" class="mt-6 space-y-5">
        <div>
            <x-input-label for="email" :value="__('E-posta')" />
            <x-text-input wire:model="form.email" id="email" class="block w-full" type="email" name="email" required autofocus autocomplete="username" placeholder="ornek@ruhscans.com" />
            <x-input-error :messages="$errors->get('form.email')" />
        </div>

        <div>
            <x-input-label for="password" :value="__('Şifre')" />
            <x-text-input wire:model="form.password" id="password" class="block w-full" type="password" name="password" required autocomplete="current-password" placeholder="Şifren" />
            <x-input-error :messages="$errors->get('form.password')" />
        </div>

        <label for="remember" class="flex min-h-11 cursor-pointer items-center gap-3 text-base text-mist/80">
            <input wire:model="form.remember" id="remember" type="checkbox" class="h-5 w-5 shrink-0 rounded border-white/25 bg-ink text-magenta focus:ring-magenta" name="remember">
            <span>Beni hatırla</span>
        </label>

        <div>
            <x-turnstile wire-model="turnstileToken" />
            <x-input-error :messages="$errors->get('turnstileToken')" />
        </div>

        <x-primary-button>{{ __('Giriş yap') }}</x-primary-button>

        @if (Route::has('password.request'))
            <div class="text-center">
                <a class="tap-target inline-flex items-center text-base text-mist/70 underline decoration-magenta/60 underline-offset-4 hover:text-magenta" href="{{ route('password.request') }}" wire:navigate>
                    {{ __('Şifremi unuttum') }}
                </a>
            </div>
        @endif

        <p class="border-t border-white/10 pt-5 text-center text-base text-mist/60">
            Henüz üye değil misin?
            <a class="tap-target inline-flex items-center font-semibold text-magenta hover:underline" href="{{ route('register') }}" wire:navigate>Kayıt ol</a>
        </p>
    </form>
</div>
