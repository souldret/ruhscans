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
<body class="night-grain min-h-[100dvh] bg-ink font-sans text-mist antialiased">
    <div class="safe-top flex min-h-[100dvh] flex-col">
        <header class="mx-auto flex w-full max-w-md items-center justify-between px-5 pt-5">
            <x-logo />
            <a href="{{ route('home') }}" wire:navigate class="tap-target inline-flex items-center rounded-full px-3 text-base text-mist/70 hover:text-magenta">
                ← Geri
            </a>
        </header>

        <main id="icerik" class="mx-auto flex w-full max-w-md flex-1 flex-col justify-center px-5 pb-10 pt-8">
            <div class="glass rounded-3xl p-6 sm:p-8">
                {{ $slot }}
            </div>
            <p class="mt-6 text-center text-sm leading-6 text-mist/50">
                Gece Kütüphanesi · 18+ içerik. Giriş yaparak yaş doğrulamasını kabul etmiş olursun.
            </p>
        </main>
    </div>
    @livewireScripts
</body>
</html>
