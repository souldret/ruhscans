@extends('layouts.app')

@section('title', 'Kayıt ol — '.config('app.name'))

@section('content')
    <div class="mx-auto max-w-md">
        <h1 class="mb-6 text-2xl font-semibold">Kayıt ol</h1>

        <form method="POST" action="{{ route('register') }}" class="space-y-4">
            @csrf

            <div>
                <label for="name" class="mb-1 block text-sm text-zinc-300">Ad</label>
                <input id="name" name="name" type="text" value="{{ old('name') }}" required autofocus
                       class="w-full rounded-md border border-zinc-700 bg-zinc-900 px-3 py-2">
                @error('name')
                    <p class="mt-1 text-sm text-rose-400">{{ $message }}</p>
                @enderror
            </div>

            <div>
                <label for="email" class="mb-1 block text-sm text-zinc-300">E-posta</label>
                <input id="email" name="email" type="email" value="{{ old('email') }}" required
                       class="w-full rounded-md border border-zinc-700 bg-zinc-900 px-3 py-2">
                @error('email')
                    <p class="mt-1 text-sm text-rose-400">{{ $message }}</p>
                @enderror
            </div>

            <div>
                <label for="password" class="mb-1 block text-sm text-zinc-300">Parola</label>
                <input id="password" name="password" type="password" required
                       class="w-full rounded-md border border-zinc-700 bg-zinc-900 px-3 py-2">
                @error('password')
                    <p class="mt-1 text-sm text-rose-400">{{ $message }}</p>
                @enderror
            </div>

            <div>
                <label for="password_confirmation" class="mb-1 block text-sm text-zinc-300">Parola tekrar</label>
                <input id="password_confirmation" name="password_confirmation" type="password" required
                       class="w-full rounded-md border border-zinc-700 bg-zinc-900 px-3 py-2">
            </div>

            <button type="submit" class="w-full rounded-md bg-rose-500 px-4 py-2 font-medium text-white hover:bg-rose-400">
                Hesap oluştur
            </button>
        </form>
    </div>
@endsection
