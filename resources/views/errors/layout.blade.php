<!DOCTYPE html>
<html lang="tr">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1, viewport-fit=cover">
    <meta name="theme-color" content="#0B0709">
    <link rel="manifest" href="{{ asset('manifest.json') }}">
    <link rel="apple-touch-icon" href="{{ asset('icons/icon-192.png') }}">
    <meta name="apple-mobile-web-app-capable" content="yes">
    <meta name="apple-mobile-web-app-status-bar-style" content="black-translucent">
    <meta name="apple-mobile-web-app-title" content="Ruhscans">
    <meta name="robots" content="noindex">
    <title>{{ $title }} · Ruhscans</title>
    <link rel="icon" href="{{ asset('favicon-neutral.svg') }}" type="image/svg+xml">
    @vite(['resources/css/app.css', 'resources/js/app.js'])
</head>
<body class="night-grain min-h-[100dvh] bg-ink font-sans text-mist antialiased">
    <main class="safe-top mx-auto flex min-h-[100dvh] max-w-lg flex-col justify-center px-5 pb-10 pt-8">
        <div class="glass rounded-3xl p-6 sm:p-8">
            <p class="text-sm font-semibold uppercase tracking-[0.2em] text-gold">Ruhscans · 18+</p>
            <h1 class="mt-3 font-display text-4xl leading-tight text-mist">{{ $title }}</h1>
            <p class="mt-4 text-base leading-7 text-mist/75">{{ $body }}</p>
            @isset($action)
                <a href="{{ $action }}" class="tap-target mt-8 inline-flex w-full items-center justify-center rounded-full bg-magenta px-6 py-4 text-lg font-semibold text-ink hover:shadow-glow">
                    Kütüphaneye dön
                </a>
            @else
                <a href="{{ url('/') }}" class="tap-target mt-8 inline-flex w-full items-center justify-center rounded-full bg-magenta px-6 py-4 text-lg font-semibold text-ink hover:shadow-glow">
                    Ana sayfaya dön
                </a>
            @endisset
        </div>
    </main>
</body>
</html>
