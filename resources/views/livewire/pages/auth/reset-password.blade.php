<?php

use Illuminate\Auth\Events\PasswordReset;
use Illuminate\Support\Facades\Password;
use Illuminate\Support\Facades\Session;
use Illuminate\Support\Str;
use Illuminate\Validation\Rules;
use Livewire\Attributes\Layout;
use Livewire\Attributes\Locked;
use Livewire\Volt\Component;

new #[Layout('layouts.guest')] class extends Component
{
    #[Locked]
    public string $token = '';
    public string $email = '';
    public string $password = '';
    public string $password_confirmation = '';

    /**
     * Mount the component.
     */
    public function mount(string $token): void
    {
        $this->token = $token;

        $this->email = request()->string('email');
    }

    /**
     * Reset the password for the given user.
     */
    public function resetPassword(): void
    {
        $this->validate([
            'token' => ['required'],
            'email' => ['required', 'string', 'email'],
            'password' => ['required', 'string', 'confirmed', Rules\Password::defaults()],
        ]);

        // Here we will attempt to reset the user's password. If it is successful we
        // will update the password on an actual user model and persist it to the
        // database. Otherwise we will parse the error and return the response.
        $status = Password::reset(
            $this->only('email', 'password', 'password_confirmation', 'token'),
            function ($user) {
                $user->forceFill([
                    'password' => $this->password,
                    'remember_token' => Str::random(60),
                ])->save();

                event(new PasswordReset($user));
            }
        );

        // If the password was successfully reset, we will redirect the user back to
        // the application's home authenticated view. If there is an error we can
        // redirect them back to where they came from with their error message.
        if ($status != Password::PASSWORD_RESET) {
            $this->addError('email', __($status));

            return;
        }

        Session::flash('status', __($status));

        $this->redirectRoute('login', navigate: true);
    }
}; ?>

<div>
    <p class="text-sm font-semibold uppercase tracking-[0.2em] text-gold">Gece Kütüphanesi</p>
    <h1 class="mt-2 font-display text-3xl text-mist">Yeni şifre</h1>
    <p class="mt-2 text-base leading-7 text-mist/60">Bu bağlantıyı sen istedin. Yeni şifreni belirle.</p>

    <form wire:submit="resetPassword" class="mt-6 space-y-5">
        <div>
            <x-input-label for="email" :value="__('E-posta')" />
            <x-text-input wire:model="email" id="email" class="block w-full" type="email" name="email" required autofocus autocomplete="username" />
            <x-input-error :messages="$errors->get('email')" />
        </div>

        <div>
            <x-input-label for="password" :value="__('Yeni şifre')" />
            <x-text-input wire:model="password" id="password" class="block w-full" type="password" name="password" required autocomplete="new-password" placeholder="En az 12 karakter" />
            <x-input-error :messages="$errors->get('password')" />
        </div>

        <div>
            <x-input-label for="password_confirmation" :value="__('Yeni şifre (tekrar)')" />
            <x-text-input wire:model="password_confirmation" id="password_confirmation" class="block w-full" type="password" name="password_confirmation" required autocomplete="new-password" placeholder="Şifreni tekrar yaz" />
            <x-input-error :messages="$errors->get('password_confirmation')" />
        </div>

        <x-primary-button>{{ __('Şifreyi değiştir') }}</x-primary-button>
    </form>
</div>
