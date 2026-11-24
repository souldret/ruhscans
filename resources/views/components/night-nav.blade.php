@php
    $panelUser = auth()->check()
        && auth()->user()->hasAnyRole([\App\Models\User::ROLE_ADMIN, \App\Models\User::ROLE_CEVIRI])
        && ! auth()->user()->isBlocked();
    $current = request()->route()?->getName() ?? '';
    $isActive = static fn (string ...$names): bool => in_array($current, $names, true);
    $isSearchFocus = $current === 'discover' && request()->query('odak') === 'ara';
    $isDiscoverActive = ($isActive('discover', 'tags.show') && ! $isSearchFocus);
@endphp

<header class="glass safe-top sticky top-0 z-30 border-b border-white/5">
    <div class="mx-auto flex h-14 max-w-6xl items-center justify-between gap-2 px-4 md:h-16 md:gap-3">
        <x-logo />

        <nav class="ml-auto hidden items-center gap-0.5 md:flex" aria-label="Ana menü">
            <a class="tap-target rounded-xl px-3 text-base transition-colors {{ $isActive('discover', 'tags.show') ? 'text-magenta' : 'text-mist/80 hover:text-magenta' }}" href="{{ route('discover') }}">Keşfet</a>
            @auth
                <a class="tap-target rounded-xl px-3 text-base transition-colors {{ $isActive('library') ? 'text-magenta' : 'text-mist/80 hover:text-magenta' }}" href="{{ route('library') }}">Kütüphane</a>
                <a class="tap-target rounded-xl px-3 text-base transition-colors {{ $isActive('wallet.show') ? 'text-magenta' : 'text-mist/80 hover:text-magenta' }}" href="{{ route('wallet.show') }}">Cüzdan</a>
                <a class="tap-target rounded-xl px-3 text-base transition-colors {{ $isActive('reader.history') ? 'text-magenta' : 'text-mist/80 hover:text-magenta' }}" href="{{ route('reader.history') }}">Geçmiş</a>
                <a class="tap-target rounded-xl px-3 text-base transition-colors {{ $isActive('preferences', 'profile') ? 'text-magenta' : 'text-mist/80 hover:text-magenta' }}" href="{{ route('profile') }}">Profil</a>
                @if ($panelUser)
                    <a class="tap-target rounded-xl px-3 text-base text-gold transition-colors hover:text-magenta" href="{{ url('/admin') }}">Admin</a>
                @endif
            @else
                <a class="tap-target rounded-xl px-3 text-base text-mist/80 transition-colors hover:text-magenta" href="{{ route('login') }}">Giriş</a>
            @endauth
        </nav>

        <div class="ml-auto flex items-center gap-2 md:ml-2">
            <livewire:cover-blur-toggle />
            {{-- Panik tuşu: üst sağda parmakla ulaşılabilir, alt gezinmeden ayrı olduğu için yanlışlıkla basılmaz --}}
            <button
                type="button"
                class="tap-target inline-flex items-center justify-center gap-1.5 rounded-full border border-white/10 bg-ink/40 px-3 text-sm font-medium text-mist/75 transition-colors hover:border-magenta/50 hover:text-magenta md:px-4"
                @click="panic()"
                aria-label="Panik tuşu — nötr sayfaya git"
                title="Hızlı çıkış (Çift Esc)"
            >
                <svg class="h-4 w-4 shrink-0" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8" aria-hidden="true">
                    <path stroke-linecap="round" stroke-linejoin="round" d="M12 3 4 7v5c0 5 3.4 8.7 8 9 4.6-.3 8-4 8-9V7l-8-4Z"/>
                    <path stroke-linecap="round" d="M9.5 12h5"/>
                </svg>
                <span class="hidden sm:inline">Panik</span>
            </button>
        </div>
    </div>
</header>

{{-- Mobil alt gezinme çubuğu --}}
<nav class="glass bottom-nav fixed inset-x-0 bottom-0 z-30 border-t border-white/10 md:hidden" aria-label="Mobil menü">
    <div class="grid grid-cols-4">
        <a
            class="font-medium transition-colors {{ $isDiscoverActive ? 'text-magenta' : 'text-mist/65 hover:text-mist' }}"
            href="{{ route('discover') }}"
            aria-label="Keşfet"
            @if ($isDiscoverActive) aria-current="page" @endif
        >
            <svg class="h-6 w-6" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8" aria-hidden="true">
                <circle cx="12" cy="12" r="9"/>
                <path stroke-linecap="round" stroke-linejoin="round" d="m9.5 7 5 2.5L12 14.5 7 12l2.5-5Z"/>
            </svg>
            <span>Keşfet</span>
        </a>

        <a
            class="font-medium transition-colors {{ $isActive('library') ? 'text-magenta' : 'text-mist/65 hover:text-mist' }}"
            href="{{ auth()->check() ? route('library') : route('login') }}"
            aria-label="Kütüphane"
            @if ($isActive('library')) aria-current="page" @endif
        >
            <svg class="h-6 w-6" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8" aria-hidden="true">
                <path stroke-linecap="round" stroke-linejoin="round" d="M4 5.5A2.5 2.5 0 0 1 6.5 3H20v16H6.5A2.5 2.5 0 0 0 4 21.5V5.5Z"/>
                <path stroke-linecap="round" d="M4 18.5A2.5 2.5 0 0 1 6.5 16H20"/>
            </svg>
            <span>Kütüphane</span>
        </a>

        <a
            class="font-medium transition-colors {{ $isSearchFocus ? 'text-magenta' : 'text-mist/65 hover:text-mist' }}"
            href="{{ route('discover') }}?odak=ara"
            aria-label="Ara"
            @if ($isSearchFocus) aria-current="page" @endif
        >
            <svg class="h-6 w-6" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8" aria-hidden="true">
                <circle cx="11" cy="11" r="7"/>
                <path stroke-linecap="round" d="m20 20-3.5-3.5"/>
            </svg>
            <span>Ara</span>
        </a>

        <a
            class="font-medium transition-colors {{ $isActive('preferences', 'profile', 'wallet.show', 'reader.history') ? 'text-magenta' : 'text-mist/65 hover:text-mist' }}"
            href="{{ auth()->check() ? route('profile') : route('login') }}"
            aria-label="Profil"
            @if ($isActive('preferences', 'profile', 'wallet.show', 'reader.history')) aria-current="page" @endif
        >
            <svg class="h-6 w-6" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8" aria-hidden="true">
                <circle cx="12" cy="8" r="4"/>
                <path stroke-linecap="round" d="M4 21a8 8 0 0 1 16 0"/>
            </svg>
            <span>Profil</span>
        </a>
    </div>
</nav>
