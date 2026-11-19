<x-guest-layout>
    <div class="space-y-4">
        <h1 class="text-xl font-semibold text-gray-900">Yönetici doğrulaması</h1>
        <p class="text-sm text-gray-600">
            Yönetici paneline girmeden önce authenticator uygulamanıza bu gizli anahtarı ekleyin ve 6 haneli kodu girin.
        </p>
        <p class="break-all rounded bg-gray-100 px-3 py-2 font-mono text-sm">{{ $secret }}</p>
        <p class="break-all text-xs text-gray-500">{{ $otpauth }}</p>
        <form method="POST" action="{{ route('admin.two-factor.store') }}" class="space-y-3">
            @csrf
            <div>
                <x-input-label for="code" value="Doğrulama kodu" />
                <x-text-input id="code" name="code" type="text" inputmode="numeric" maxlength="6" class="mt-1 block w-full" required autofocus />
                <x-input-error :messages="$errors->get('code')" class="mt-2" />
            </div>
            <x-primary-button>Doğrula ve panele gir</x-primary-button>
        </form>
    </div>
</x-guest-layout>
