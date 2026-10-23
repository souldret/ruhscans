<!DOCTYPE html>
<html lang="tr">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <meta name="csrf-token" content="{{ csrf_token() }}">
    <title>@yield('title', config('app.name'))</title>
    @vite(['resources/css/app.css', 'resources/js/app.js'])
</head>
<body class="min-h-screen bg-ink font-sans text-ink-text antialiased">
    <a href="#icerik" class="sr-only focus:not-sr-only focus:absolute focus:left-4 focus:top-4 focus:z-50 focus:rounded-full focus:bg-ink-surface focus:px-4 focus:py-2">İçeriğe geç</a>

    <header class="sticky top-0 z-40 border-b border-ink-line bg-ink/70 backdrop-blur-xl" x-data="{ open: false }" @keydown.window.ctrl.k.prevent="open = true" @keydown.window.meta.k.prevent="open = true" @keydown.escape.window="open = false">
        <div class="mx-auto flex h-16 max-w-6xl items-center gap-6 px-4">
            <a href="{{ route('home') }}" class="font-display text-lg font-semibold tracking-tight">{{ config('app.name') }}</a>

            <nav class="hidden items-center gap-5 text-sm text-ink-muted md:flex" aria-label="Ana menü">
                <a href="{{ route('games.index') }}" class="hover:text-ink-text">Oyunlar</a>
                <a href="{{ route('vip') }}" class="hover:text-ink-text">VIP</a>
            </nav>

            <button type="button" @click="open = true" class="ml-auto hidden items-center gap-3 rounded-full border border-ink-line bg-white/5 px-3 py-1.5 text-sm text-ink-muted md:flex">
                <span>Oyun ara</span>
                <kbd class="rounded bg-white/10 px-1.5 text-xs">Ctrl K</kbd>
            </button>

            <div class="ml-auto text-sm md:ml-0">
                @auth
                    <a href="{{ route('dashboard') }}" class="text-ink-muted hover:text-ink-text">Hesap</a>
                @else
                    <a href="{{ route('login') }}" class="text-ink-muted hover:text-ink-text">Giriş</a>
                @endauth
            </div>
        </div>

        <div x-show="open" x-cloak x-transition.opacity.duration.200ms class="fixed inset-0 z-50 bg-black/60" @click.self="open = false">
            <form action="{{ route('games.index') }}" method="GET" role="search" class="mx-auto mt-24 w-[min(36rem,calc(100%-2rem))] rounded-2xl border border-ink-line bg-ink-surface p-3">
                <label for="arama" class="sr-only">Oyun ara</label>
                <input id="arama" name="q" x-ref="search" x-init="$watch('open', value => value && $nextTick(() => $refs.search.focus()))" placeholder="Oyun, geliştirici veya etiket" class="w-full border-0 bg-transparent text-ink-text placeholder:text-ink-muted focus:ring-0">
            </form>
        </div>
    </header>

    <main id="icerik" class="mx-auto max-w-6xl px-4 pb-28 pt-6 md:pb-10">
        @yield('content')
    </main>

    <nav class="fixed inset-x-0 bottom-0 z-40 border-t border-ink-line bg-ink/90 backdrop-blur md:hidden" aria-label="Mobil menü">
        <div class="grid grid-cols-4 text-center text-xs text-ink-muted">
            <a href="{{ route('home') }}" class="py-3 hover:text-ink-text">Ana sayfa</a>
            <a href="{{ route('games.index') }}" class="py-3 hover:text-ink-text">Ara</a>
            <a href="{{ route('vip') }}" class="py-3 hover:text-ink-text">VIP</a>
            <a href="{{ auth()->check() ? route('dashboard') : route('login') }}" class="py-3 hover:text-ink-text">Hesap</a>
        </div>
    </nav>
</body>
</html>
