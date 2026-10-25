<!DOCTYPE html>
<html lang="tr">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1, viewport-fit=cover">
    <meta name="csrf-token" content="{{ csrf_token() }}">
    <meta name="theme-color" content="#08080D">
    <title>@yield('title', config('app.name').' — Türkçeleştirilmiş oyunlar')</title>
    <meta name="description" content="@yield('description', config('app.name').' bağımsız Ren\'Py oyunlarının Türkçe yamalarını sunar. 18 yaş ve üzeri.')">
    <link rel="canonical" href="{{ url()->current() }}">
    <link rel="icon" href="{{ asset('favicon.jpeg') }}" type="image/jpeg">
    @php
        // Derleme sonrası dosya adı değiştiği için font yolunu manifestten çöz.
        try {
            $displayFont = \Illuminate\Support\Facades\Vite::asset('resources/fonts/sora-600.woff2');
        } catch (\Throwable) {
            $displayFont = null;
        }
    @endphp
    @if ($displayFont)
        <link rel="preload" href="{{ $displayFont }}" as="font" type="font/woff2" crossorigin>
 @endif
    <link rel="apple-touch-icon" href="{{ asset('brand/mark.jpeg') }}">
    <meta property="og:locale" content="tr_TR">
    <meta property="og:type" content="website">
    <meta property="og:site_name" content="{{ config('app.name') }}">
    <meta property="og:title" content="@yield('title', config('app.name'))">
    <meta property="og:description" content="@yield('description', config('app.name').' Türkçe yama arşivi.')">
    <meta property="og:url" content="{{ url()->current() }}">
    <meta property="og:image" content="@yield('image', asset('brand/banner.jpeg'))">
    <meta name="twitter:card" content="summary_large_image">
    <meta name="twitter:title" content="@yield('title', config('app.name'))">
    <meta name="twitter:description" content="@yield('description', config('app.name').' Türkçe yama arşivi.')">
    <meta name="twitter:image" content="@yield('image', asset('brand/banner.jpeg'))">
    @yield('meta')
    @vite(['resources/css/app.css', 'resources/js/app.js'])
</head>
<body class="flex min-h-screen flex-col overflow-x-clip bg-ink font-sans text-ink-text antialiased">
    <div class="ambient" aria-hidden="true"></div>

    <a href="#icerik" class="sr-only focus:not-sr-only focus:fixed focus:left-4 focus:top-4 focus:z-[80] focus:rounded-full focus:bg-ink-surface focus:px-4 focus:py-2">İçeriğe geç</a>
    <noscript>
        <p class="bg-ink-raised px-4 py-3 text-center text-sm text-ink-text">JavaScript kapalı. Sayfalar ve formlar yine çalışır; kaydırmalı şeritler ve arama penceresi sınırlı kalır.</p>
    </noscript>

    @php
        $navItems = [
            ['route' => 'home', 'match' => 'home', 'label' => 'Keşfet'],
            ['route' => 'games.index', 'match' => 'games.*', 'label' => 'Oyunlar'],
            ['route' => 'coins', 'match' => 'coins', 'label' => 'Coin'],
            ['route' => 'support.index', 'match' => 'support.*', 'label' => 'Destek'],
        ];
    @endphp

    <div x-data="searchBox"
 @keydown.window.ctrl.k.prevent="open()"
 @keydown.window.meta.k.prevent="open()"
 @keydown.window.slash="shortcut($event)"
 @keydown.escape.window="close()"
 @open-search.window="open()">
 <header class="sticky top-0 z-40 border-b border-ink-line bg-ink/70 backdrop-blur-xl supports-[backdrop-filter]:bg-ink/55">
 <div class="mx-auto flex h-16 max-w-6xl items-center gap-6 px-4">
 <a href="{{ route('home') }}" class="group flex shrink-0 items-center gap-2.5" aria-label="{{ config('app.name') }} ana sayfa">
 <img src="{{ asset('brand/mark.jpeg') }}" alt="" width="40" height="40" class="h-9 w-9 rounded-full object-cover ring-2 ring-white/10 transition group-hover:ring-brand-pink/50">
 <span class="max-w-[9rem] truncate font-display text-lg font-semibold tracking-tight sm:max-w-none">{{ config('app.name') }}</span>
 </a>

 <nav class="hidden items-center gap-1 text-sm md:flex" aria-label="Ana menü">
 @foreach ($navItems as $item)
 @php $active = request()->routeIs($item['match']); @endphp
 <a href="{{ route($item['route']) }}"
 @if ($active) aria-current="page" @endif
 class="relative rounded-full px-3.5 py-2 transition {{ $active ? 'bg-white/[0.07] text-white' : 'text-ink-muted hover:bg-white/[0.04] hover:text-ink-text' }} {{ $item['route'] === 'vip' && ! $active ? 'hover:text-brand-gold' : '' }}">
 {{ $item['label'] }}
 </a>
 @endforeach
 </nav>

 <button type="button" @click="open()" class="group ml-auto hidden min-w-[15rem] items-center gap-3 rounded-full border border-ink-line bg-white/[0.03] py-1.5 pl-3.5 pr-1.5 text-sm text-ink-faint transition hover:border-ink-line-strong hover:text-ink-muted md:flex" aria-label="Oyun ara (Ctrl K)">
 <svg viewBox="0 0 20 20" class="h-4 w-4" fill="none" stroke="currentColor" stroke-width="2" aria-hidden="true"><circle cx="9" cy="9" r="6"/><path d="m14 14 3.5 3.5" stroke-linecap="round"/></svg>
 <span class="flex-1 text-left">Oyun ara…</span>
 <kbd class="rounded-full border border-ink-line bg-white/5 px-2 py-0.5 font-sans text-[11px] text-ink-muted">Ctrl K</kbd>
 </button>

 <div class="ml-auto flex items-center gap-2 text-sm md:ml-0">
 <button type="button" @click="open()" class="grid h-9 w-9 place-items-center rounded-full text-ink-muted transition hover:bg-white/[0.06] hover:text-ink-text md:hidden" aria-label="Oyun ara">
 <svg viewBox="0 0 20 20" class="h-5 w-5" fill="none" stroke="currentColor" stroke-width="2" aria-hidden="true"><circle cx="9" cy="9" r="6"/><path d="m14 14 3.5 3.5" stroke-linecap="round"/></svg>
 </button>
 @auth
 @if (auth()->user()->isAdmin())
 <a href="{{ url('/yonetim') }}" class="hidden rounded-full px-3 py-2 text-brand-gold transition hover:bg-brand-gold/10 sm:inline-flex">Yönetim</a>
 @endif
 <a href="{{ route('coins') }}" class="inline-flex min-h-9 items-center gap-1.5 rounded-full border border-brand-gold/40 bg-brand-gold/10 px-2.5 text-brand-gold transition hover:bg-brand-gold/20" aria-label="Coin bakiyen, paketlere git">
 <svg viewBox="0 0 16 16" class="h-3.5 w-3.5" fill="currentColor" aria-hidden="true"><path d="M8 1.2 9.7 5.6H14.4L10.6 8.3 12 12.8 8 10.1 4 12.8l1.4-4.5L1.6 5.6h4.7L8 1.2Z"/></svg>
 <span class="inline-block font-display text-sm font-bold transition duration-200">{{ number_format(auth()->user()->coin_balance ?? 0, 0, ',', '.') }}</span>
 </a>
 <a href="{{ route('account.show') }}" class="inline-flex items-center gap-2 rounded-full border border-ink-line bg-white/[0.03] py-1 pl-1 pr-3.5 transition hover:border-ink-line-strong hover:bg-white/[0.06] {{ request()->routeIs('account.*', 'profile.*') ? 'border-brand-pink/40' : '' }}">
 <span class="grid h-7 w-7 place-items-center rounded-full bg-accent text-xs font-bold text-white">{{ mb_strtoupper(mb_substr(auth()->user()->name, 0, 1)) }}</span>
 <span class="hidden sm:inline">Hesap</span>
 </a>
 @else
 <a href="{{ route('login') }}" class="rounded-full px-3 py-2 text-ink-muted transition hover:text-ink-text">Giriş</a>
 <x-button :href="route('register')" size="sm" class="hidden sm:inline-flex">Üye ol</x-button>
 @endauth
 </div>
 </div>
 </header>

 {{-- Arama penceresi. Header disinda, ayni Alpine kokunde; sticky menunun icinde kalmaz. --}}
 <div x-show="search" x-cloak
 x-transition:enter="transition duration-200 ease-out" x-transition:enter-start="opacity-0" x-transition:enter-end="opacity-100"
 x-transition:leave="transition duration-150 ease-in" x-transition:leave-start="opacity-100" x-transition:leave-end="opacity-0"
 class="fixed inset-0 z-[60] bg-black/70 backdrop-blur-sm" @click.self="close()">
 <form action="{{ route('games.index') }}" method="GET" role="search" aria-label="Oyun ara"
 x-show="search" x-transition:enter="transition duration-300 ease-out" x-transition:enter-start="opacity-0 -translate-y-3 scale-[0.98]" x-transition:enter-end="opacity-100 translate-y-0 scale-100"
 class="mx-auto mt-[12vh] w-[min(40rem,calc(100%-2rem))] overflow-hidden rounded-3xl border border-ink-line-strong bg-ink-surface shadow-2xl">
 <div class="flex items-center gap-3 px-5">
 <svg viewBox="0 0 20 20" class="h-5 w-5 shrink-0 text-ink-muted" fill="none" stroke="currentColor" stroke-width="2" aria-hidden="true"><circle cx="9" cy="9" r="6"/><path d="m14 14 3.5 3.5" stroke-linecap="round"/></svg>
 <label for="arama" class="sr-only">Oyun ara</label>
 <input id="arama" name="q" type="search" autocomplete="off" maxlength="100" x-ref="query" x-effect="focusQuery()" placeholder="Oyun veya geliştirici adı" class="h-16 w-full border-0 bg-transparent text-base text-ink-text placeholder:text-ink-faint focus:ring-0">
 <button type="button" @click="close()" class="grid h-8 w-8 shrink-0 place-items-center rounded-full text-ink-muted transition hover:bg-white/10 hover:text-ink-text" aria-label="Aramayı kapat">
 <svg viewBox="0 0 20 20" class="h-4 w-4" fill="none" stroke="currentColor" stroke-width="2" aria-hidden="true"><path d="M5 5l10 10M15 5 5 15" stroke-linecap="round"/></svg>
 </button>
 </div>
 <div class="flex flex-wrap items-center gap-2 border-t border-ink-line px-5 py-3 text-xs text-ink-muted">
 <span>Hızlı:</span>
 <a href="{{ route('games.index', ['sirala' => 'populer']) }}" class="rounded-full border border-ink-line px-2.5 py-1 hover:text-ink-text">Popüler</a>
 <a href="{{ route('games.index', ['ceviri' => 'complete']) }}" class="rounded-full border border-ink-line px-2.5 py-1 hover:text-ink-text">Tamamlanan</a>
 <a href="{{ route('games.index', ['erisim' => 'ucretsiz']) }}" class="rounded-full border border-ink-line px-2.5 py-1 hover:text-ink-text">Ücretsiz</a>
 <a href="{{ route('coins') }}" class="rounded-full border border-brand-gold/30 px-2.5 py-1 text-brand-gold hover:bg-brand-gold/10">Coin</a>
 <span class="ml-auto hidden sm:inline">Enter ile ara · Esc kapatır</span>
 </div>
 </form>
 </div>


 <main id="icerik" class="mx-auto w-full max-w-6xl flex-1 px-4 pb-20 pt-6 sm:pt-8">
        @yield('content')
    </main>

    <footer class="relative border-t border-ink-line bg-ink-deep/60 pb-24 md:pb-0">
        <div class="mx-auto grid max-w-6xl gap-10 px-4 py-12 md:grid-cols-[1.4fr_1fr_1fr]">
            <div>
                <div class="flex items-center gap-3">
                    <img src="{{ asset('brand/mark.jpeg') }}" alt="" width="40" height="40" class="h-10 w-10 rounded-full object-cover ring-2 ring-white/10">
                    <div>
                        <p class="font-display font-semibold">{{ config('app.name') }}</p>
                        <p class="text-xs text-ink-muted">18+ Türkçe yama arşivi</p>
                    </div>
                </div>
                <p class="mt-4 max-w-sm text-sm leading-6 text-ink-muted">Bağımsız Ren'Py oyunlarının gönüllü Türkçe çevirileri. Oyunların tüm hakları geliştiricilerine aittir.</p>
            </div>
            <nav aria-label="Keşfet">
                <p class="eyebrow">Keşfet</p>
                <ul class="mt-4 space-y-2.5 text-sm text-ink-muted">
                    <li><a href="{{ route('games.index') }}" class="transition hover:text-ink-text">Tüm oyunlar</a></li>
                    <li><a href="{{ route('games.index', ['sirala' => 'populer']) }}" class="transition hover:text-ink-text">Popüler</a></li>
                    <li><a href="{{ route('games.index', ['sirala' => 'guncellenen']) }}" class="transition hover:text-ink-text">Güncellenenler</a></li>
                    <li><a href="{{ route('coins') }}" class="transition hover:text-brand-gold">Coin</a></li>
                    <li><a href="{{ route('support.index') }}" class="transition hover:text-ink-text">Destek</a></li>
                </ul>
            </nav>
            <nav aria-label="Alt menü">
                <p class="eyebrow">Kurumsal</p>
                <ul class="mt-4 space-y-2.5 text-sm text-ink-muted">
                    <li><a href="{{ route('legal.terms') }}" class="transition hover:text-ink-text">Kullanım Şartları</a></li>
                    <li><a href="{{ route('legal.privacy') }}" class="transition hover:text-ink-text">Gizlilik</a></li>
                    <li><a href="{{ route('legal.cookies') }}" class="transition hover:text-ink-text">Çerezler</a></li>
                    <li><a href="{{ route('legal.takedown') }}" class="transition hover:text-ink-text">İçerik Kaldırma</a></li>
                    <li><a href="{{ route('legal.about') }}" class="transition hover:text-ink-text">Hakkımızda</a></li>
                </ul>
            </nav>
        </div>
        <div class="border-t border-ink-line">
            <p class="mx-auto max-w-6xl px-4 py-5 text-xs text-ink-faint">© {{ now()->year }} {{ config('app.name') }}. Tüm içerik 18 yaş ve üzeri içindir.</p>
        </div>
    </footer>

    {{-- Mobil alt menü --}}
 @php
 $mobileItems = [
 ['href' => route('home'), 'match' => ['home'], 'label' => 'Keşfet', 'icon' => 'M3 10.5 10 4l7 6.5V17a1 1 0 0 1-1 1h-3.5v-5h-5v5H4a1 1 0 0 1-1-1v-6.5Z'],
 ['href' => route('games.index'), 'match' => ['games.*'], 'label' => 'Oyunlar', 'icon' => 'M3 4h5v5H3zM12 4h5v5h-5zM3 12h5v5H3zM12 12h5v5h-5z'],
 ['href' => route('coins'), 'match' => ['coins'], 'label' => 'Coin', 'icon' => 'M2.5 15.5h15l-1.5-8-4 3L10 4.5 8 10.5l-4-3-1.5 8Z'],
 ['href' => route('support.index'), 'match' => ['support.*'], 'label' => 'Destek', 'icon' => 'M4 4h12a1 1 0 0 1 1 1v7a1 1 0 0 1-1 1H8l-3 3v-3H4a1 1 0 0 1-1-1V5a1 1 0 0 1 1-1Z'],
 ['href' => auth()->check() ? route('account.show') : route('login'), 'match' => ['account.*', 'profile.*', 'login'], 'label' => 'Hesap', 'icon' => 'M10 10a3.5 3.5 0 1 0 0-7 3.5 3.5 0 0 0 0 7Zm-6.5 7.5c.8-3 3.4-5 6.5-5s5.7 2 6.5 5'],
 ];
 @endphp
 <nav class="fixed inset-x-0 bottom-0 z-40 border-t border-ink-line bg-ink/85 pb-[env(safe-area-inset-bottom)] backdrop-blur-xl md:hidden" aria-label="Mobil menü">
 <div class="grid grid-cols-5 text-center text-[11px]">
 @foreach ($mobileItems as $item)
 @php $active = request()->routeIs(...$item['match']); @endphp
 <a href="{{ $item['href'] }}" @if ($active) aria-current="page" @endif class="relative flex flex-col items-center gap-1 py-2.5 transition {{ $active ? 'text-white' : 'text-ink-muted hover:text-ink-text' }}">
 @if ($active)
 <span class="absolute top-0 h-0.5 w-8 rounded-full bg-accent"></span>
 @endif
 <svg viewBox="0 0 20 20" class="h-5 w-5" fill="none" stroke="currentColor" stroke-width="1.7" stroke-linejoin="round" aria-hidden="true"><path d="{{ $item['icon'] }}"/></svg>
 {{ $item['label'] }}
 </a>
 @endforeach
 </div>
 </nav>
 </div>


    @unless ($adult)
        <div class="fixed inset-0 z-[70] grid place-items-center overflow-y-auto bg-ink/90 p-6 backdrop-blur-2xl" role="dialog" aria-modal="true" aria-labelledby="yas-baslik">
            <div class="relative w-full max-w-sm overflow-hidden rounded-[2rem] border border-ink-line-strong bg-ink-surface px-7 py-9 text-center shadow-2xl motion-safe:animate-fade-up">
                <div class="pointer-events-none absolute -top-20 left-1/2 h-40 w-40 -translate-x-1/2 rounded-full bg-brand-pink/30 blur-3xl"></div>
                <img src="{{ asset('brand/mark.jpeg') }}" alt="" width="64" height="64" class="relative mx-auto h-16 w-16 rounded-full object-cover ring-4 ring-white/10">
                <p class="relative mt-5 font-display text-5xl font-bold text-accent">18+</p>
                <h2 id="yas-baslik" class="relative mt-3 font-display text-xl font-semibold">Bu site yetişkinler içindir</h2>
                <p class="relative mt-3 text-sm leading-6 text-ink-muted">Kapaklar onayından sonra açılır. Devam ederek 18 yaşından büyük olduğunu onaylarsın. Bu seçim bir yıl hatırlanır.</p>
                <form method="POST" action="{{ route('adult.confirm') }}" class="relative mt-7">
                    @csrf
                    <x-button type="submit" size="lg" class="w-full">18 yaşındayım</x-button>
                </form>
                <a href="https://www.google.com" rel="noopener noreferrer" class="relative mt-4 inline-block text-xs text-ink-faint transition hover:text-ink-muted">Hayır, çıkış yap</a>
            </div>
        </div>
    @endunless

    @if ($adult && request()->cookie('notice') !== '1')
        <div class="fixed inset-x-3 bottom-[5.25rem] z-50 mx-auto flex max-w-3xl flex-col gap-3 rounded-2xl border border-ink-line-strong bg-ink-surface/95 px-4 py-3 text-sm shadow-2xl backdrop-blur motion-safe:animate-fade-up sm:flex-row sm:items-center md:bottom-5" role="region" aria-label="Çerez bildirimi">
            <p class="min-w-0 flex-1 text-ink-muted">Zorunlu çerezler oturumu ve yaş onayını tutar. Ayrıntı <a href="{{ route('legal.cookies') }}" class="link">çerez politikasında</a>.</p>
            <form method="POST" action="{{ route('cookies.notice') }}" class="shrink-0">
                @csrf
                <x-button type="submit" variant="ghost" size="sm">Tamam</x-button>
            </form>
        </div>
 @endif
</body>
</html>



