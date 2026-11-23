<!DOCTYPE html>
<html lang="tr">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1, viewport-fit=cover">
    <meta name="csrf-token" content="{{ csrf_token() }}">
    <meta name="theme-color" content="#0B0709">
    <x-seo-meta :meta="$seo ?? ['title' => $title ?? config('seo.site_name')]" />
    <link rel="icon" href="{{ asset('favicon.svg') }}" type="image/svg+xml">
    @vite(['resources/css/app.css', 'resources/js/app.js'])
    @livewireStyles
</head>
<body class="night-grain bg-ink pb-24 font-sans text-mist antialiased md:pb-10">
    @php
        $viewer = auth()->user();
        $shell = [
            'blur' => (bool) ($viewer->kapak_blur ?? true),
            'stealth' => (bool) ($viewer->gizli_mod ?? false),
            'panic' => $viewer->panik_url ?? app(\App\Services\Ops\SiteOperations::class)->panicUrl(),
        ];
    @endphp
    <div
        x-data="nightShell({{ \Illuminate\Support\Js::from($shell) }})"
        wire:ignore.self
        :class="stealth ? 'stealth' : ''"
        x-on:blur-changed.window="setBlur($event.detail.blur)"
    >
        @php $announcement = \App\Models\SiteSetting::get('announcement'); @endphp
        @if (filled($announcement))
            <p class="bg-wine/80 px-4 py-2 text-center text-sm text-gold">{{ $announcement }}</p>
        @endif
        <x-night-nav />
        <main id="icerik" class="min-h-[60dvh]">
            {{ $slot }}
        </main>
        <footer class="site-footer border-t border-white/5 bg-ink/80">
            <div class="mx-auto flex max-w-6xl flex-col gap-4 px-4 py-8 text-sm text-mist/60 sm:flex-row sm:items-center sm:justify-between">
                <p class="text-sm text-mist/60">Ruhscans · Gece Kütüphanesi · 18+ yetişkin içerik</p>
                <nav class="flex flex-wrap items-center gap-x-4 gap-y-1" aria-label="Yasal bağlantılar">
                    <a class="tap-target text-sm text-mist/70 hover:text-magenta" href="{{ route('legal.terms') }}">Kullanım şartları</a>
                    <a class="tap-target text-sm text-mist/70 hover:text-magenta" href="{{ route('legal.privacy') }}">Gizlilik</a>
                    <a class="tap-target text-sm text-mist/70 hover:text-magenta" href="{{ route('legal.dmca') }}">DMCA</a>
                    <a class="tap-target text-sm text-mist/70 hover:text-magenta" href="{{ route('legal.age') }}">18+ uyarı</a>
                    <a class="tap-target text-sm text-mist/70 hover:text-magenta" href="{{ route('legal.report') }}">Şikayet</a>
                </nav>
            </div>
        </footer>
        <div class="toast-stack" aria-live="polite">
            <template x-for="toast in toasts" :key="toast.id">
                <p class="glass pointer-events-auto rounded-2xl px-4 py-3 text-sm text-mist" x-text="toast.message"></p>
            </template>
        </div>
        <button
            type="button"
            class="pwa-install glass tap-target fixed bottom-20 right-4 z-40 rounded-full border border-gold/30 px-5 text-sm font-semibold text-gold shadow-lg md:hidden"
            x-cloak
            x-show="installAvailable"
            @click="window.dispatchEvent(new CustomEvent('pwa-install-request'))"
        >
            Ana ekrana ekle
        </button>
    </div>
    @livewireScripts
    <style>
        .cover-art { filter: blur(16px); transform: scale(1.08); transition: filter 200ms ease; }
        html.covers-open .cover-art,
        .cover-tilt:hover .cover-art,
        .cover-tilt:focus-within .cover-art { filter: none; }
        .stealth .cover-art { filter: blur(22px) !important; }
    </style>
</body>
</html>
