<!DOCTYPE html>
<html lang="tr">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>@yield('title', config('app.name'))</title>
    @vite(['resources/css/app.css', 'resources/js/app.js'])
</head>
<body class="min-h-screen bg-zinc-950 text-zinc-100 antialiased">
    <header class="border-b border-zinc-800">
        <div class="mx-auto flex max-w-6xl items-center justify-between px-4 py-4">
            <a href="{{ route('home') }}" class="text-lg font-semibold tracking-tight text-rose-400">
                {{ config('app.name') }}
            </a>

            <nav class="flex items-center gap-4 text-sm">
                <a href="{{ route('games.index') }}" class="text-zinc-300 hover:text-white">Oyunlar</a>

                @auth
                    <span class="text-zinc-400">{{ auth()->user()->name }}</span>
                    <form method="POST" action="{{ route('logout') }}">
                        @csrf
                        <button type="submit" class="text-zinc-300 hover:text-white">Çıkış</button>
                    </form>
                @else
                    <a href="{{ route('login') }}" class="text-zinc-300 hover:text-white">Giriş</a>
                    <a href="{{ route('register') }}" class="rounded-md bg-rose-500 px-3 py-1.5 font-medium text-white hover:bg-rose-400">Kayıt ol</a>
                @endauth
            </nav>
        </div>
    </header>

    <main class="mx-auto max-w-6xl px-4 py-8">
        @if (session('status'))
            <div class="mb-6 rounded-md border border-emerald-800 bg-emerald-950 px-4 py-3 text-sm text-emerald-200">
                {{ session('status') }}
            </div>
        @endif

        @if (session('error'))
            <div class="mb-6 rounded-md border border-rose-800 bg-rose-950 px-4 py-3 text-sm text-rose-200">
                {{ session('error') }}
            </div>
        @endif

        @yield('content')
    </main>

    <footer class="border-t border-zinc-800">
        <div class="mx-auto max-w-6xl px-4 py-6 text-sm text-zinc-500">
            Türkçeleştirilmiş bağımsız Ren'Py oyunları.
        </div>
    </footer>
</body>
</html>
