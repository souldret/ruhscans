<?php

use App\Services\Profile\SessionManager;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\RateLimiter;
use Illuminate\Validation\Rules\Password;
use Illuminate\Validation\ValidationException;
use Livewire\Volt\Component;

new class extends Component
{
    public string $current_password = '';

    public string $password = '';

    public string $password_confirmation = '';

    public string $statusMessage = '';

    public function updatePassword(SessionManager $sessions): void
    {
        $user = Auth::user();
        abort_unless($user !== null, 403);

        $key = 'password-update|'.$user->id;
        if (RateLimiter::tooManyAttempts($key, 5)) {
            $this->addError('current_password', 'Çok fazla şifre değiştirme denemesi. Lütfen 10 dakika sonra tekrar dene.');

            return;
        }
        RateLimiter::hit($key, 600);

        try {
            $validated = $this->validate([
                'current_password' => ['required', 'string', 'current_password'],
                'password' => ['required', 'string', Password::defaults(), 'confirmed'],
            ], [
                'current_password.required' => 'Mevcut şifreni girmelisin.',
                'current_password.current_password' => 'Mevcut şifre hatalı.',
                'password.required' => 'Yeni şifre zorunludur.',
                'password.confirmed' => 'Yeni şifre tekrarı eşleşmiyor.',
            ]);
        } catch (ValidationException $e) {
            $this->reset('current_password', 'password', 'password_confirmation');

            throw $e;
        }

        $user->update([
            'password' => $validated['password'],
        ]);

        $revoked = $sessions->logoutOtherDevices($user, session()->getId());

        activity('profile')
            ->causedBy($user)
            ->performedOn($user)
            ->withProperties(['other_sessions_revoked' => $revoked])
            ->log('Şifre güncellendi.');

        $this->reset('current_password', 'password', 'password_confirmation');
        $this->statusMessage = 'Şifren güncellendi ve diğer cihazlardaki oturumlar kapatıldı.';

        $this->dispatch('password-updated');
        $this->dispatch('toast', message: $this->statusMessage);
    }
}; ?>

<section>
    <header>
        <h2 class="font-display text-2xl text-mist">Şifre değiştir</h2>
        <p class="mt-1 text-base text-mist/70">En az 12 karakter; büyük-küçük harf, rakam ve sembol içermelidir. Şifre değişince diğer tüm cihazlardaki oturumların otomatik kapatılır.</p>
    </header>

    <form wire:submit="updatePassword" class="mt-6 space-y-5">
        <div>
            <x-input-label for="update_password_current_password" value="Mevcut şifre" />
            <x-text-input wire:model="current_password" id="update_password_current_password" name="current_password" type="password" class="mt-1 block w-full" autocomplete="current-password" />
            <x-input-error :messages="$errors->get('current_password')" class="mt-2" />
        </div>

        <div>
            <x-input-label for="update_password_password" value="Yeni şifre" />
            <x-text-input wire:model="password" id="update_password_password" name="password" type="password" class="mt-1 block w-full" autocomplete="new-password" placeholder="En az 12 karakter" />
            <x-input-error :messages="$errors->get('password')" class="mt-2" />
        </div>

        <div>
            <x-input-label for="update_password_password_confirmation" value="Yeni şifre (tekrar)" />
            <x-text-input wire:model="password_confirmation" id="update_password_password_confirmation" name="password_confirmation" type="password" class="mt-1 block w-full" autocomplete="new-password" />
            <x-input-error :messages="$errors->get('password_confirmation')" class="mt-2" />
        </div>

        @if ($statusMessage !== '')
            <p class="rounded-2xl border border-gold/30 bg-gold/10 px-4 py-2.5 text-sm text-gold" aria-live="polite">
                {{ $statusMessage }}
            </p>
        @endif

        <div class="flex flex-wrap items-center gap-4">
            <x-primary-button class="sm:w-auto sm:px-8">Şifreyi güncelle</x-primary-button>
            <x-action-message class="text-gold" on="password-updated">Kaydedildi.</x-action-message>
        </div>
    </form>
</section>
