<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}">
    <head>
        <meta charset="utf-8">
        <meta name="viewport" content="width=device-width, initial-scale=1, viewport-fit=cover">
        <meta name="csrf-token" content="{{ csrf_token() }}">
        <meta name="theme-color" content="#08080D">

        <title>{{ config('app.name', 'RuhScans') }}</title>
        <link rel="icon" href="{{ asset('favicon.jpeg') }}" type="image/jpeg">

        @vite(['resources/css/app.css', 'resources/js/app.js'])
    </head>
    <body class="flex min-h-screen flex-col justify-center overflow-x-clip bg-ink font-sans text-ink-text antialiased">
        <div class="ambient" aria-hidden="true"></div>

        <div class="mx-auto w-full max-w-md px-4 py-12">
            <div class="mb-8 text-center">
                <a href="{{ route('home') }}" class="inline-flex flex-col items-center gap-3 group" aria-label="{{ config('app.name') }}">
                    <img src="{{ asset('brand/mark.jpeg') }}" alt="" width="68" height="68" class="h-16 w-16 rounded-full object-cover ring-2 ring-white/10 transition group-hover:ring-brand-pink/50">
                    <span class="font-display text-xl font-bold tracking-tight text-white">{{ config('app.name') }}</span>
                </a>
            </div>

            <div class="card p-6 shadow-2xl sm:p-8">
                {{ $slot }}
            </div>

            <p class="mt-8 text-center text-xs text-ink-faint">
                <a href="{{ route('home') }}" class="text-ink-muted hover:text-ink-text">← Ana sayfaya dön</a>
            </p>
        </div>
    </body>
</html>
