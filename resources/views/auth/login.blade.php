<x-guest-layout>
    <div class="mb-6">
        <h1 class="font-display text-2xl font-bold tracking-tight">Giriş Yap</h1>
        <p class="mt-1 text-sm text-ink-muted">Hesabına erişerek oyunları ve yamaları hemen indir.</p>
    </div>

    <x-auth-session-status class="mb-5" :status="session('status')" />

    <form method="POST" action="{{ route('login') }}" class="space-y-4">
        @csrf

        <div>
            <x-input-label for="email" value="E-posta" />
            <x-text-input id="email" type="email" name="email" :value="old('email')" required autofocus autocomplete="username" placeholder="ornek@posta.com" />
            <x-input-error :messages="$errors->get('email')" />
        </div>

        <div>
            <div class="flex items-center justify-between">
                <x-input-label for="password" value="Şifre" />
                @if (Route::has('password.request'))
                    <a class="text-xs text-ink-muted hover:text-ink-text hover:underline" href="{{ route('password.request') }}">
                        Şifremi unuttum
                    </a>
                @endif
            </div>
            <x-text-input id="password" type="password" name="password" required autocomplete="current-password" placeholder="••••••••" />
            <x-input-error :messages="$errors->get('password')" />
        </div>

        <div class="flex items-center justify-between pt-1">
            <label for="remember_me" class="inline-flex cursor-pointer items-center gap-2">
                <input id="remember_me" type="checkbox" name="remember" class="rounded">
                <span class="text-xs text-ink-muted">Beni hatırla</span>
            </label>
        </div>

        @if (config('membership.turnstile_site'))
            <div class="mt-4">
                <div class="cf-turnstile" data-sitekey="{{ config('membership.turnstile_site') }}"></div>
                <x-input-error :messages="$errors->get('cf-turnstile-response')" />
            </div>
            <script src="https://challenges.cloudflare.com/turnstile/v0/api.js" async defer></script>
        @endif

        <div class="pt-2">
            <x-primary-button class="w-full">
                Giriş yap
            </x-primary-button>
        </div>

        <p class="pt-4 text-center text-xs text-ink-muted">
            Hesabın yok mu?
            <a href="{{ route('register') }}" class="font-semibold text-white hover:underline">Hemen üye ol</a>
        </p>
    </form>
</x-guest-layout>
