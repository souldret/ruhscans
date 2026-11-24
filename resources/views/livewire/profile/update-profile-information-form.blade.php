<?php

use App\Models\User;
use App\Services\Security\TurnstileService;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\RateLimiter;
use Illuminate\Support\Facades\Session;
use Livewire\Volt\Component;

new class extends Component
{
    public string $name = '';

    public string $email = '';

    public string $turnstileToken = '';

    public string $feedbackMessage = '';

    public function mount(): void
    {
        $this->name = Auth::user()->name;
        $this->email = Auth::user()->email;
    }

    public function updateProfileInformation(TurnstileService $turnstile): void
    {
        $user = Auth::user();
        abort_unless($user !== null, 403);

        $key = 'profile-update|'.$user->id;
        if (RateLimiter::tooManyAttempts($key, 10)) {
            $this->addError('email', 'Çok fazla deneme yaptın. Lütfen bir dakika sonra tekrar dene.');

            return;
        }
        RateLimiter::hit($key, 60);

        $validated = $this->validate([
            'name' => ['required', 'string', 'min:2', 'max:255'],
            'email' => ['required', 'string', 'lowercase', 'email', 'max:255'],
        ], [
            'name.required' => 'Takma ad zorunludur.',
            'name.min' => 'Takma ad en az 2 karakter olmalıdır.',
            'name.max' => 'Takma ad en fazla 255 karakter olabilir.',
            'email.required' => 'E-posta adresi zorunludur.',
            'email.email' => 'Geçerli bir e-posta adresi girin.',
        ]);

        if (! $turnstile->verify($this->turnstileToken)) {
            $this->addError('turnstileToken', 'Doğrulama başarısız. Lütfen tekrar deneyin.');
            $this->dispatch('turnstile-reset');

            return;
        }

        $emailChanged = $validated['email'] !== $user->email;
        $emailTaken = $emailChanged
            && User::query()->where('email', $validated['email'])->where('id', '!=', $user->id)->exists();

        $user->name = $validated['name'];

        if ($emailChanged && ! $emailTaken) {
            $user->email = $validated['email'];
            $user->email_verified_at = null;
        }

        $user->save();

        if ($emailChanged && ! $emailTaken) {
            try {
                $user->sendEmailVerificationNotification();
            } catch (\Throwable) {
                // Mail gönderilemezse kayıt akışını kırma.
            }
        }

        // E-posta enumeration sızdırmamak için mevcut veya yeni e-posta ayrımı yapılmaz.
        $this->feedbackMessage = $emailChanged
            ? 'Değişiklikler işlendi. Yeni e-posta adresin uygunsa doğrulama bağlantısı gönderilecektir.'
            : 'Profil bilgilerin güncellendi.';

        $this->email = $user->email;
        $this->dispatch('turnstile-reset');
        $this->dispatch('profile-updated', name: $user->name);
        $this->dispatch('toast', message: $this->feedbackMessage);
    }

    public function sendVerification(): void
    {
        $user = Auth::user();
        abort_unless($user !== null, 403);

        $key = 'email-verify-resend|'.$user->id;
        if (RateLimiter::tooManyAttempts($key, 3)) {
            $this->feedbackMessage = 'Çok sık doğrulama isteği gönderdin. Lütfen biraz bekle.';

            return;
        }
        RateLimiter::hit($key, 300);

        if ($user->hasVerifiedEmail()) {
            $this->redirectIntended(default: route('profile', absolute: false));

            return;
        }

        $user->sendEmailVerificationNotification();

        Session::flash('status', 'verification-link-sent');
        $this->dispatch('toast', message: 'Doğrulama bağlantısı gönderildi.');
    }
}; ?>

<section>
    <header>
        <h2 class="font-display text-2xl text-mist">Hesap bilgileri</h2>
        <p class="mt-1 text-base text-mist/70">Takma adını ve e-posta adresini güncelle. E-posta değiştiğinde yeniden doğrulama gerekir.</p>
    </header>

    <form wire:submit="updateProfileInformation" class="mt-6 space-y-5">
        <div>
            <x-input-label for="name" value="Takma ad" />
            <x-text-input wire:model="name" id="name" name="name" type="text" class="mt-1 block w-full" required autocomplete="name" />
            <x-input-error class="mt-2" :messages="$errors->get('name')" />
        </div>

        <div>
            <x-input-label for="email" value="E-posta adresi" />
            <x-text-input wire:model="email" id="email" name="email" type="email" class="mt-1 block w-full" required autocomplete="username" />
            <x-input-error class="mt-2" :messages="$errors->get('email')" />

            @if (auth()->user() instanceof \Illuminate\Contracts\Auth\MustVerifyEmail && ! auth()->user()->hasVerifiedEmail())
                <div class="mt-3 rounded-2xl border border-gold/30 bg-gold/10 p-3.5">
                    <p class="text-sm text-gold">
                        E-posta adresin henüz doğrulanmamış.
                        <button type="button" wire:click.prevent="sendVerification" class="tap-target ml-1 inline-flex items-center font-semibold text-mist underline hover:text-magenta">
                            Doğrulama bağlantısını yeniden gönder
                        </button>
                    </p>

                    @if (session('status') === 'verification-link-sent')
                        <p class="mt-2 text-sm font-medium text-gold">
                            E-posta adresine yeni bir doğrulama bağlantısı gönderildi.
                        </p>
                    @endif
                </div>
            @endif
        </div>

        <div>
            <x-turnstile wire-model="turnstileToken" />
            <x-input-error class="mt-2" :messages="$errors->get('turnstileToken')" />
        </div>

        @if ($feedbackMessage !== '')
            <p class="rounded-2xl border border-gold/30 bg-gold/10 px-4 py-2.5 text-sm text-gold" aria-live="polite">
                {{ $feedbackMessage }}
            </p>
        @endif

        <div class="flex flex-wrap items-center gap-4">
            <x-primary-button class="sm:w-auto sm:px-8">Kaydet</x-primary-button>
            <x-action-message class="text-gold" on="profile-updated">Kaydedildi.</x-action-message>
        </div>
    </form>
</section>
