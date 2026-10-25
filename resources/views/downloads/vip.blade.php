@extends('layouts.site')

@section('title', 'Coin gerekiyor — '.config('app.name'))

@section('content')
    <section class="card mx-auto max-w-lg p-8 py-16 text-center sm:p-12">
        <p class="font-display text-7xl font-extrabold text-gold">Coin</p>
        <h1 class="mt-4 font-display text-2xl font-bold tracking-tight sm:text-3xl">Bu oyunun kilidi kapalı</h1>
        <p class="mx-auto mt-3 max-w-md text-sm leading-6 text-ink-muted">Oyunu coin ile açtığında dosyaları ve sonraki yamaları da senin olur.</p>
        <div class="mt-8 flex flex-col justify-center gap-3 sm:flex-row">
            <x-button :href="route('coins')" variant="gold">Coin yükle</x-button>
            <x-button :href="route('account.show')" variant="ghost">Kod kullan</x-button>
        </div>
        <p class="mt-5 text-sm text-ink-muted">Kod gelmedi ya da bakiye tutmadıysa <a href="{{ route('support.index') }}" class="link">desteğe yaz</a>.</p>
    </section>
@endsection
