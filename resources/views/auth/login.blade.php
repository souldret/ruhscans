@extends('layouts.app')

@section('title', 'Giriş — '.config('app.name'))

@section('content')
    <div class="mx-auto max-w-md">
        <h1 class="mb-6 text-2xl font-semibold">Giriş yap</h1>

        <form method="POST" action="{{ route('login') }}" class="space-y-4">
            @csrf

            <div>
                <label for="email" class="mb-1 block text-sm text-zinc-300">E-posta</label>
                <input id="email" name="email" type="email" value="{{ old('email') }}" required autofocus
                       class="w-full rounded-md border border-zinc-700 bg-zinc-900 px-3 py-2">
                @error('email')
                    <p class="mt-1 text-sm text-rose-400">{{ $message }}</p>
                @enderror
            </div>

            <div>
                <label for="password" class="mb-1 block text-sm text-zinc-300">Parola</label>
                <input id="password" name="password" type="password" required
                       class="w-full rounded-md border border-zinc-700 bg-zinc-900 px-3 py-2">
            </div>

            <label class="flex items-center gap-2 text-sm text-zinc-400">
                <input type="checkbox" name="remember" value="1" class="rounded border-zinc-700 bg-zinc-900">
                Beni hatırla
            </label>

            <button type="submit" class="w-full rounded-md bg-rose-500 px-4 py-2 font-medium text-white hover:bg-rose-400">
                Giriş yap
            </button>
        </form>
    </div>
@endsection
