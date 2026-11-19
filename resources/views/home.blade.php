<x-guest-layout>
    <div class="space-y-4">
        <p class="text-xs uppercase tracking-widest text-gray-500">Geçici ön yüz</p>
        <h1 class="text-2xl font-semibold text-gray-900">Ruhscans</h1>
        <p class="text-sm text-gray-600">
            Türkçe manga ve manhwa okuma alanı. Bu ekran temel fazın yer tutucusudur; asıl tasarım sonraki fazda gelecek.
        </p>
        <div class="flex flex-col gap-2 sm:flex-row">
            @auth
                <a href="{{ route('dashboard') }}" class="rounded-md bg-gray-900 px-4 py-2 text-center text-sm text-white">Panele git</a>
            @else
                <a href="{{ route('login') }}" class="rounded-md bg-gray-900 px-4 py-2 text-center text-sm text-white">Giriş yap</a>
                <a href="{{ route('register') }}" class="rounded-md border border-gray-300 px-4 py-2 text-center text-sm text-gray-800">Kayıt ol</a>
            @endauth
        </div>
    </div>
</x-guest-layout>
