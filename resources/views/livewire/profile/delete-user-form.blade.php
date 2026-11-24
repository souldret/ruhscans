<?php

use App\Livewire\Actions\Logout;
use App\Services\Profile\AccountDeletionService;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\RateLimiter;
use Livewire\Volt\Component;

new class extends Component
{
    public string $password = '';

    public function deleteUser(Logout $logout, AccountDeletionService $deletion): void
    {
        $user = Auth::user();
        abort_unless($user !== null, 403);

        $key = 'account-delete|'.$user->id;
        if (RateLimiter::tooManyAttempts($key, 5)) {
            $this->addError('password', 'Çok fazla deneme yaptın. Lütfen 15 dakika sonra tekrar dene.');

            return;
        }
        RateLimiter::hit($key, 900);

        $this->validate([
            'password' => ['required', 'string', 'current_password'],
        ], [
            'password.required' => 'Hesabını silmek için mevcut şifreni girmelisin.',
            'password.current_password' => 'Girdiğin şifre hatalı.',
        ]);

        $logout();
        $deletion->handle($user);

        $this->redirect('/', navigate: true);
    }
}; ?>

<section class="space-y-5">
    <header>
        <h2 class="font-display text-2xl text-mist">Hesabı sil</h2>
        <p class="mt-1 text-base text-mist/75">Hesabını sildiğinde bu işlem geri alınamaz.</p>
    </header>

    <div class="space-y-2 rounded-2xl border border-white/10 bg-ink/60 p-4 text-sm leading-6 text-mist/75">
        <p><strong class="text-mist">Kalıcı olarak silinenler:</strong> Hesap profilin, avatar dosyan, kütüphane kayıtların, okuma geçmişin, aktif oturumların ve kalan ruh taşı/VIP hakların.</p>
        <p><strong class="text-gold">Anonimleştirilerek saklananlar:</strong> Mali denetim bütünlüğü gereği cüzdan defteri (<code class="text-mist/90">wallet_transactions</code>) ve kullanılan kod kayıtları kimliğinle bağı koparılarak (<code class="text-mist/90">user_id = null</code>) anonim şekilde tutulur.</p>
        <p>Silmeden önce <a href="{{ route('profile.export') }}" class="text-gold underline hover:text-magenta">verilerini JSON olarak indirebilirsin</a>.</p>
    </div>

    <x-danger-button
        x-data=""
        x-on:click.prevent="$dispatch('open-modal', 'confirm-user-deletion')"
    >Hesabımı kalıcı olarak sil</x-danger-button>

    <x-modal name="confirm-user-deletion" :show="$errors->isNotEmpty()" focusable>
        <form wire:submit="deleteUser" class="bg-surface p-6 text-mist">
            <h2 class="font-display text-2xl text-mist">Hesabını silmek istediğine emin misin?</h2>

            <p class="mt-2 text-base leading-6 text-mist/75">
                Profilin, avatarın, kütüphanen, okuma geçmişin ve aktif oturumların kalıcı olarak silinir. Cüzdan defteri (ledger) kayıtları kimlikten arındırılarak anonim saklanır. Onaylamak için şifreni gir.
            </p>

            <div class="mt-6">
                <x-input-label for="delete_account_password" value="Mevcut şifren" />
                <x-text-input
                    wire:model="password"
                    id="delete_account_password"
                    name="password"
                    type="password"
                    class="mt-1 block w-full"
                    placeholder="Mevcut şifren"
                />
                <x-input-error :messages="$errors->get('password')" class="mt-2" />
            </div>

            <div class="mt-6 flex flex-wrap justify-end gap-3">
                <x-secondary-button x-on:click="$dispatch('close')">Vazgeç</x-secondary-button>
                <x-danger-button>Hesabı sil</x-danger-button>
            </div>
        </form>
    </x-modal>
</section>
