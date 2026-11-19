<!DOCTYPE html>
<html lang="tr">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>18+ uyarı · {{ config('app.name') }}</title>
    @vite(['resources/css/app.css', 'resources/js/app.js'])
</head>
<body class="min-h-screen bg-zinc-950 text-zinc-100">
    <main class="mx-auto flex min-h-screen max-w-lg flex-col justify-center px-6 py-12">
        <p class="text-sm uppercase tracking-[0.2em] text-rose-400">Yetişkin içerik</p>
        <h1 class="mt-3 text-3xl font-semibold">Bu site 18 yaş ve üzeri içindir.</h1>
        <p class="mt-4 text-sm leading-6 text-zinc-300">
            Ruhscans, yetişkinlere yönelik manga ve manhwa barındırabilir. Devam ederek 18 yaşından büyük olduğunuzu ve
            <a class="underline" href="{{ route('legal.terms') }}">kullanım şartlarını</a> kabul ettiğinizi onaylarsınız.
        </p>
        <form method="POST" action="{{ route('age-gate.confirm') }}" class="mt-8">
            @csrf
            <input type="hidden" name="intended" value="{{ $intended }}">
            <button type="submit" class="w-full rounded-md bg-rose-600 px-4 py-3 text-sm font-medium text-white">
                18 yaşından büyüğüm
            </button>
        </form>
        <a href="{{ route('legal.age') }}" class="mt-4 text-center text-sm text-zinc-400 underline">Ayrıl / ayrıntıları oku</a>
    </main>
</body>
</html>
