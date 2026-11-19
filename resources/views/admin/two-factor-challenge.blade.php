<x-guest-layout>
    <div class="space-y-4">
        <h1 class="text-xl font-semibold text-gray-900">İki adımlı doğrulama</h1>
        <p class="text-sm text-gray-600">Authenticator kodunu veya kurtarma kodunu girin.</p>
        <form method="POST" action="{{ route('admin.two-factor.verify') }}" class="space-y-3">
            @csrf
            <div>
                <x-input-label for="code" value="Kod" />
                <x-text-input id="code" name="code" type="text" class="mt-1 block w-full" required autofocus autocomplete="one-time-code" />
                <x-input-error :messages="$errors->get('code')" class="mt-2" />
            </div>
            <x-primary-button>Panele gir</x-primary-button>
        </form>
    </div>
</x-guest-layout>
