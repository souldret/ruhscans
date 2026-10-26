<x-guest-layout>
    <div class="mb-6">
        <h1 class="font-display text-2xl font-bold tracking-tight">Kayıt Ol</h1>
        <p class="mt-1 text-sm text-ink-muted">Bir dakikada ücretsiz hesabını aç, arşive eriş.</p>
    </div>

    <form method="POST" action="{{ route('register') }}" class="space-y-4">
        @csrf

        <div>
            <x-input-label for="name" value="Kullanıcı Adı" />
            <x-text-input id="name" type="text" name="name" :value="old('name')" required autofocus autocomplete="name" placeholder="Adın veya takma adın" />
            <x-input-error :messages="$errors->get('name')" />
        </div>

        <div>
            <x-input-label for="email" value="E-posta" />
            <x-text-input id="email" type="email" name="email" :value="old('email')" required autocomplete="username" placeholder="ornek@posta.com" />
            <x-input-error :messages="$errors->get('email')" />
        </div>

        <div>
            <x-input-label for="password" value="Şifre" />
            <x-text-input id="password" type="password" name="password" required autocomplete="new-password" minlength="12" placeholder="En az 12 karakter, büyük-küçük harf ve rakam" />
            <p class="mt-1.5 text-xs text-ink-muted">En az 12 karakter. Büyük harf, küçük harf ve rakam içermeli.</p>
 <x-input-error :messages="$errors->get('password')" />
        </div>

        <div>
            <x-input-label for="password_confirmation" value="Şifre tekrar" />
            <x-text-input id="password_confirmation" type="password" name="password_confirmation" required autocomplete="new-password" placeholder="Şifreni doğrula" />
            <x-input-error :messages="$errors->get('password_confirmation')" />
        </div>

        @if (app(\App\Services\Turnstile::class)->enabled())
            <div class="mt-4">
                <div class="cf-turnstile" data-sitekey="{{ config('membership.turnstile_site') }}"></div>
                <x-input-error :messages="$errors->get('cf-turnstile-response')" />
            </div>
            <script src="https://challenges.cloudflare.com/turnstile/v0/api.js" async defer></script>
        @endif

        {{-- Honeypot --}}
        <div class="absolute -left-[9999px]" aria-hidden="true">
            <label for="company">Şirket</label>
            <input id="company" name="company" type="text" tabindex="-1" autocomplete="off">
        </div>

        <div class="pt-2">
            <x-primary-button class="w-full">
                Kayıt ol
            </x-primary-button>
        </div>

        <p class="pt-4 text-center text-xs text-ink-muted">
            Zaten üye misin?
            <a href="{{ route('login') }}" class="font-semibold text-white hover:underline">Giriş yap</a>
        </p>
    </form>
</x-guest-layout>
