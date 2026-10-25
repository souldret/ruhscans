<x-guest-layout>
    <div class="mb-6">
        <h1 class="font-display text-2xl font-bold tracking-tight">Yeni Şifre Belirle</h1>
        <p class="mt-1 text-sm text-ink-muted">Hesabın için yeni ve güvenli bir şifre gir.</p>
    </div>

    <form method="POST" action="{{ route('password.store') }}" class="space-y-4">
        @csrf

        <input type="hidden" name="token" value="{{ $request->route('token') }}">

        <div>
            <x-input-label for="email" value="E-posta" />
            <x-text-input id="email" type="email" name="email" :value="old('email', $request->email)" required autofocus autocomplete="username" />
            <x-input-error :messages="$errors->get('email')" />
        </div>

        <div>
            <x-input-label for="password" value="Yeni Şifre" />
            <x-text-input id="password" type="password" name="password" required autocomplete="new-password" minlength="12" placeholder="En az 12 karakter, büyük-küçük harf ve rakam" />
            <x-input-error :messages="$errors->get('password')" />
        </div>

        <div>
            <x-input-label for="password_confirmation" value="Yeni Şifre Tekrar" />
            <x-text-input id="password_confirmation" type="password" name="password_confirmation" required autocomplete="new-password" placeholder="Şifreni doğrula" />
            <x-input-error :messages="$errors->get('password_confirmation')" />
        </div>

        <div class="pt-2">
            <x-primary-button class="w-full">
                Şifreyi sıfırla
            </x-primary-button>
        </div>
    </form>
</x-guest-layout>
