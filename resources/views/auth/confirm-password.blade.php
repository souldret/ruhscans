<x-guest-layout>
    <div class="mb-6">
        <h1 class="font-display text-2xl font-bold tracking-tight">Güvenlik Onayı</h1>
        <p class="mt-1 text-sm text-ink-muted">Bu işlem için lütfen mevcut şifreni onaylayarak devam et.</p>
    </div>

    <form method="POST" action="{{ route('password.confirm') }}" class="space-y-4">
        @csrf

        <div>
            <x-input-label for="password" value="Mevcut Şifre" />
            <x-text-input id="password" type="password" name="password" required autocomplete="current-password" placeholder="••••••••" />
            <x-input-error :messages="$errors->get('password')" />
        </div>

        <div class="pt-2">
            <x-primary-button class="w-full">
                Onayla
            </x-primary-button>
        </div>
    </form>
</x-guest-layout>
