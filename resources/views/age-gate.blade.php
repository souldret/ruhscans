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
    <meta name="robots" content="noindex, nofollow">
    <title>18+ uyarı · Ruhscans</title>
    <link rel="icon" href="{{ asset('favicon-neutral.svg') }}" type="image/svg+xml">
    @vite(['resources/css/app.css', 'resources/js/app.js'])
</head>
<body class="night-grain min-h-[100dvh] bg-ink font-sans text-mist antialiased">
    <main class="safe-top mx-auto flex min-h-[100dvh] max-w-lg flex-col justify-center px-5 pb-10 pt-8">
        <div class="glass rounded-3xl p-6 sm:p-8">
            <p class="text-sm font-semibold uppercase tracking-[0.2em] text-gold">Yetişkin içerik</p>
            <h1 class="mt-3 font-display text-4xl leading-tight text-mist">Bu site yalnızca 18 yaş ve üzeri okurlar içindir.</h1>
            <p class="mt-4 text-base leading-7 text-mist/75">
                Ruhscans, yetişkinlere yönelik manga ve manhwa barındırır. Devam ederek 18 yaşından büyük olduğunu ve
                <a class="inline-flex items-center text-gold underline decoration-gold/50 underline-offset-4 hover:text-magenta" href="{{ route('legal.terms') }}">kullanım şartlarını</a>
                kabul ettiğini onaylarsın.
            </p>

            <form method="POST" action="{{ route('age-gate.confirm') }}" class="mt-8">
                @csrf
                <input type="hidden" name="intended" value="{{ $intended }}">
                <button type="submit" class="tap-target inline-flex w-full items-center justify-center rounded-full bg-magenta px-6 py-4 text-lg font-semibold text-ink hover:shadow-glow">
                    18 yaşından büyüğüm
                </button>
            </form>

            <a href="{{ route('legal.age') }}" class="tap-target mt-3 inline-flex w-full items-center justify-center rounded-full border border-white/10 text-base text-mist/70 hover:border-magenta/60 hover:text-magenta">
                Ayrıl / ayrıntıları oku
            </a>
        </div>
    </main>
</body>
</html>
