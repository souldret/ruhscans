<x-guest-layout>
    <div class="space-y-4">
        <h1 class="text-xl font-semibold text-gray-900">Kurtarma kodları</h1>
        <p class="text-sm text-gray-600">
            Bu kodlar yalnızca şimdi gösterilir. Telefonu kaybederseniz her biri bir kez panele giriş sağlar. Güvenli bir yere yazın.
        </p>
        <ul class="space-y-1 rounded bg-gray-100 px-3 py-2 font-mono text-sm">
            @foreach ($recoveryCodes as $code)
                <li>{{ $code }}</li>
            @endforeach
        </ul>
        <a href="{{ url('/admin') }}" class="inline-flex items-center rounded-md bg-gray-800 px-4 py-2 text-xs font-semibold uppercase tracking-widest text-white">
            Kodları kaydettim, panele gir
        </a>
    </div>
</x-guest-layout>
