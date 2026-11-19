<!DOCTYPE html>
<html lang="tr">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <meta name="csrf-token" content="{{ csrf_token() }}">
    <meta name="theme-color" content="#0B0709">
    <title>{{ $title ?? config('app.name', 'Ruhscans') }}</title>
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
            'panic' => $viewer->panik_url ?? 'https://www.google.com',
        ];
    @endphp
    <div
        x-data="nightShell({{ \Illuminate\Support\Js::from($shell) }})"
        wire:ignore.self
        :class="stealth ? 'stealth' : ''"
        x-on:blur-changed.window="setBlur($event.detail.blur)"
    >
        <x-night-nav />
        <main id="icerik">
            {{ $slot }}
        </main>
        <div class="pointer-events-none fixed right-4 top-20 z-50 flex w-72 flex-col gap-2" aria-live="polite">
            <template x-for="toast in toasts" :key="toast.id">
                <p class="glass pointer-events-auto rounded-2xl px-4 py-3 text-sm" x-text="toast.message"></p>
            </template>
        </div>
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
