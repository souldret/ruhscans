<x-guest-layout>
    <div class="mb-6">
        <h1 class="font-display text-2xl font-bold tracking-tight">Şifremi Unuttum</h1>
        <p class="mt-1 text-sm text-ink-muted">Kayıtlı e-posta adresini gir, sana şifre sıfırlama bağlantısı gönderelim.</p>
    </div>

    <x-auth-session-status class="mb-5" :status="session('status')" />

    <form method="POST" action="{{ route('password.email') }}" class="space-y-4">
        @csrf

        <div>
            <x-input-label for="email" value="E-posta" />
            <x-text-input id="email" type="email" name="email" :value="old('email')" required autofocus placeholder="ornek@posta.com" />
            <x-input-error :messages="$errors->get('email')" />
        </div>

        <div class="pt-2">
            <x-primary-button class="w-full">
                Sıfırlama bağlantısı gönder
            </x-primary-button>
        </div>

        <p class="pt-3 text-center text-xs text-ink-muted">
            <a href="{{ route('login') }}" class="hover:text-ink-text hover:underline">← Giriş sayfasına dön</a>
        </p>
    </form>
</x-guest-layout>
