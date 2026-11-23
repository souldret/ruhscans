<?php

use App\Actions\Auth\RegisterUser;
use App\Models\User;
use App\Services\Security\TurnstileService;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\RateLimiter;
use Illuminate\Validation\Rules;
use Livewire\Attributes\Layout;
use Livewire\Volt\Component;

new #[Layout('layouts.guest')] class extends Component
{
    public string $name = '';
    public string $email = '';
    public string $password = '';
    public string $password_confirmation = '';
    public string $turnstileToken = '';

    /**
     * Handle an incoming registration request.
     */
    public function register(RegisterUser $registerUser, TurnstileService $turnstile): void
    {
        $key = 'register|'.request()->ip();

        if (RateLimiter::tooManyAttempts($key, 5)) {
            $this->addError('email', __('auth.throttle', [
                'seconds' => RateLimiter::availableIn($key),
                'minutes' => ceil(RateLimiter::availableIn($key) / 60),
            ]));

            return;
        }

        RateLimiter::hit($key, 60);

        $validated = $this->validate([
            'name' => ['required', 'string', 'max:255'],
            'email' => ['required', 'string', 'lowercase', 'email', 'max:255', 'unique:'.User::class],
            'password' => ['required', 'string', 'confirmed', Rules\Password::defaults()],
        ]);

        if (! $turnstile->verify($this->turnstileToken)) {
            $this->addError('turnstileToken', 'Doğrulama başarısız. Lütfen tekrar deneyin.');
            $this->dispatch('turnstile-reset');

            return;
        }

        Auth::login($registerUser->handle($validated));

        $this->redirect(route('dashboard', absolute: false), navigate: true);
    }
}; ?>

<div>
    <p class="text-sm font-semibold uppercase tracking-[0.2em] text-gold">Gece Kütüphanesi</p>
    <h1 class="mt-2 font-display text-3xl text-mist">Ruhunu kaydet</h1>
    <p class="mt-2 text-base leading-7 text-mist/60">Ücretsiz üye ol, kütüphaneni oluştur.</p>

    <form wire:submit="register" class="mt-6 space-y-5">
        <div>
            <x-input-label for="name" :value="__('Takma ad')" />
            <x-text-input wire:model="name" id="name" class="block w-full" type="text" name="name" required autofocus autocomplete="name" placeholder="Nasıl hitap edelim?" />
            <x-input-error :messages="$errors->get('name')" />
        </div>

        <div>
            <x-input-label for="email" :value="__('E-posta')" />
            <x-text-input wire:model="email" id="email" class="block w-full" type="email" name="email" required autocomplete="username" placeholder="ornek@ruhscans.com" />
            <x-input-error :messages="$errors->get('email')" />
        </div>

        <div>
            <x-input-label for="password" :value="__('Şifre')" />
            <x-text-input wire:model="password" id="password" class="block w-full" type="password" name="password" required autocomplete="new-password" placeholder="En az 12 karakter" />
            <x-input-error :messages="$errors->get('password')" />
        </div>

        <div>
            <x-input-label for="password_confirmation" :value="__('Şifre (tekrar)')" />
            <x-text-input wire:model="password_confirmation" id="password_confirmation" class="block w-full" type="password" name="password_confirmation" required autocomplete="new-password" placeholder="Şifreni tekrar yaz" />
            <x-input-error :messages="$errors->get('password_confirmation')" />
        </div>

        <div>
            <x-turnstile wire-model="turnstileToken" />
            <x-input-error :messages="$errors->get('turnstileToken')" />
        </div>

        <x-primary-button>{{ __('Kayıt ol') }}</x-primary-button>

        <p class="border-t border-white/10 pt-5 text-center text-base text-mist/60">
            Zaten üye misin?
            <a class="tap-target inline-flex items-center font-semibold text-magenta hover:underline" href="{{ route('login') }}" wire:navigate>Giriş yap</a>
        </p>
    </form>
</div>
