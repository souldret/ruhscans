<?php

use App\Livewire\Actions\Logout;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Session;
use Livewire\Attributes\Layout;
use Livewire\Volt\Component;

new #[Layout('layouts.guest')] class extends Component
{
    /**
     * Send an email verification notification to the user.
     */
    public function sendVerification(): void
    {
        if (Auth::user()->hasVerifiedEmail()) {
            $this->redirectIntended(default: route('dashboard', absolute: false), navigate: true);

            return;
        }

        Auth::user()->sendEmailVerificationNotification();

        Session::flash('status', 'verification-link-sent');
    }

    /**
     * Log the current user out of the application.
     */
    public function logout(Logout $logout): void
    {
        $logout();

        $this->redirect('/', navigate: true);
    }
}; ?>

<div>
    <p class="text-sm font-semibold uppercase tracking-[0.2em] text-gold">Gece Kütüphanesi</p>
    <h1 class="mt-2 font-display text-3xl text-mist">E-postanı doğrula</h1>
    <p class="mt-2 text-base leading-7 text-mist/60">Gönderdiğimiz bağlantıya tıkla. Gelmediyse yeniden gönder.</p>

    @if (session('status') == 'verification-link-sent')
        <div class="mt-5 rounded-2xl border border-gold/30 bg-gold/10 px-4 py-3 text-base text-gold">
            {{ __('Yeni doğrulama bağlantısı gönderildi.') }}
        </div>
    @endif

    <div class="mt-6 space-y-3">
        <x-primary-button wire:click="sendVerification">
            {{ __('Doğrulama bağlantısını yeniden gönder') }}
        </x-primary-button>

        <button wire:click="logout" type="submit" class="tap-target inline-flex w-full items-center justify-center rounded-full border border-white/10 text-base text-mist/80 hover:border-magenta/60 hover:text-magenta">
            {{ __('Çıkış yap') }}
        </button>
    </div>
</div>
