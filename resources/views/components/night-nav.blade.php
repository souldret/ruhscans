@php
    $panelUser = auth()->check()
        && auth()->user()->hasAnyRole([\App\Models\User::ROLE_ADMIN, \App\Models\User::ROLE_CEVIRI])
        && ! auth()->user()->isBlocked;
@endphp

<header class="glass sticky top-0 z-30">
    <div class="mx-auto flex h-16 max-w-6xl items-center gap-4 px-4">
        <x-logo />
        <nav class="ml-auto hidden items-center gap-5 text-sm md:flex" aria-label="Ana menü">
            <a class="hover:text-magenta" href="{{ route('discover') }}">Keşfet</a>
            @auth
                <a class="hover:text-magenta" href="{{ route('library') }}">Kütüphane</a>
                <a class="hover:text-magenta" href="{{ route('wallet.show') }}">Cüzdan</a>
                <a class="hover:text-magenta" href="{{ route('reader.history') }}">Geçmiş</a>
                <a class="hover:text-magenta" href="{{ route('preferences') }}">Profil</a>
                @if ($panelUser)
                    <a class="text-gold hover:text-magenta" href="{{ url('/admin') }}">Admin Paneli</a>
                @endif
            @else
                <a class="hover:text-magenta" href="{{ route('login') }}">Giriş</a>
            @endauth
        </nav>
        <livewire:cover-blur-toggle />
        <button type="button" class="btn-ghost !px-3 !py-1.5 text-xs" @click="panic()" aria-label="Panik tuşu, nötr sayfaya git">
            Panik
        </button>
    </div>
</header>
<nav class="glass fixed inset-x-3 bottom-3 z-30 grid {{ $panelUser ? 'grid-cols-5' : 'grid-cols-4' }} rounded-2xl p-1 text-center text-[11px] md:hidden" aria-label="Mobil menü">
    <a class="rounded-xl py-2 hover:text-magenta" href="{{ route('discover') }}">Keşfet</a>
    <a class="rounded-xl py-2 hover:text-magenta" href="{{ auth()->check() ? route('reader.history') : route('login') }}">Geçmiş</a>
    <a class="rounded-xl py-2 hover:text-magenta" href="{{ route('discover') }}?odak=ara">Ara</a>
    <a class="rounded-xl py-2 hover:text-magenta" href="{{ auth()->check() ? route('wallet.show') : route('login') }}">Cüzdan</a>
    @if ($panelUser)
        <a class="rounded-xl py-2 text-gold hover:text-magenta" href="{{ url('/admin') }}">Admin</a>
    @endif
</nav>
