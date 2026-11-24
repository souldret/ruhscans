<?php

use App\Services\Security\TurnstileService;
use Illuminate\Support\Facades\Password;
use Illuminate\Support\Facades\RateLimiter;
use Livewire\Attributes\Layout;
use Livewire\Volt\Component;

new #[Layout('layouts.guest')] class extends Component
{
    public string $email = '';
    public string $turnstileToken = '';

    /**
     * Send a password reset link to the provided email address.
     */
    public function sendPasswordResetLink(TurnstileService $turnstile): void
    {
        $key = 'password-reset|'.request()->ip();

        if (RateLimiter::tooManyAttempts($key, 5)) {
            $this->addError('email', __('auth.throttle', [
                'seconds' => RateLimiter::availableIn($key),
                'minutes' => ceil(RateLimiter::availableIn($key) / 60),
            ]));

            return;
        }

        RateLimiter::hit($key, 60);

        $this->validate([
            'email' => ['required', 'string', 'email'],
        ]);

        if (! $turnstile->verify($this->turnstileToken)) {
            $this->addError('turnstileToken', 'Doğrulama başarısız. Lütfen tekrar deneyin.');
            $this->dispatch('turnstile-reset');

            return;
        }

        $status = Password::sendResetLink(
            $this->only('email')
        );

        if ($status === Password::RESET_THROTTLED) {
            $this->addError('email', __($status));

            return;
        }

        // Kullanıcı numaralandırmayı önlemek için kayıtlı/kayıtsız e-postada aynı yanıt verilir.
        $this->reset('email');

        session()->flash('status', __(Password::RESET_LINK_SENT));
    }
}; ?>

<div>
    <p class="text-sm font-semibold uppercase tracking-[0.2em] text-gold">Gece Kütüphanesi</p>
    <h1 class="mt-2 font-display text-3xl text-mist">Şifre sıfırlama</h1>
    <p class="mt-2 text-base leading-7 text-mist/60">E-posta adresini yaz, sıfırlama bağlantısını gönderelim.</p>

    <x-auth-session-status :status="session('status')" />

    <form wire:submit="sendPasswordResetLink" class="mt-6 space-y-5">
        <div>
            <x-input-label for="email" :value="__('E-posta')" />
            <x-text-input wire:model="email" id="email" class="block w-full" type="email" name="email" required autofocus placeholder="ornek@ruhscans.com" />
            <x-input-error :messages="$errors->get('email')" />
        </div>

        <div>
            <x-turnstile wire-model="turnstileToken" />
            <x-input-error :messages="$errors->get('turnstileToken')" />
        </div>

        <x-primary-button>{{ __('Bağlantıyı gönder') }}</x-primary-button>

        <p class="border-t border-white/10 pt-5 text-center text-base text-mist/60">
            <a class="tap-target inline-flex items-center font-semibold text-magenta hover:underline" href="{{ route('login') }}" wire:navigate>Girişe dön</a>
        </p>
    </form>
</div>
