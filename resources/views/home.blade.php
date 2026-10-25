@extends('layouts.site')

@section('title', config('app.name').' — Türkçeleştirilmiş Ren\'Py oyunları')

@section('image', asset('brand/banner-alt.jpeg'))

@section('content')
    <h1 class="sr-only">{{ config('app.name') }} — Türkçeleştirilmiş Ren'Py oyunları</h1>

    <x-hero :games="$featured" />

    <x-section-row title="Yeni Eklenenler" eyebrow="Taze çeviriler" :games="$newest" :href="route('games.index', ['sirala' => 'yeni'])" />
    <x-section-row title="Popüler" eyebrow="En çok indirilen" :games="$popular" :href="route('games.index', ['sirala' => 'populer'])" />
 <x-section-row title="Ücretsiz Oyunlar" eyebrow="Coin gerekmez" :games="$free" :href="route('games.index', ['erisim' => 'ucretsiz'])" />
    <x-section-row title="Güncellenenler" eyebrow="Yeni sürümler" :games="$updated" :href="route('games.index', ['sirala' => 'guncellenen'])" />

    <section class="relative mt-16 overflow-hidden rounded-[2rem] border border-brand-gold/25 bg-ink-surface p-8 sm:p-12">
        <div class="pointer-events-none absolute -right-24 -top-24 h-72 w-72 rounded-full bg-brand-gold/20 blur-3xl"></div>
        <div class="pointer-events-none absolute -bottom-24 left-1/3 h-64 w-64 rounded-full bg-brand-pink/10 blur-3xl"></div>
        <div class="relative flex flex-col gap-6 md:flex-row md:items-center md:justify-between">
            <div class="max-w-xl">
 <p class="eyebrow text-brand-gold">Coin</p>
                <h2 class="mt-2 font-display text-3xl font-bold tracking-tight">Oyunları <span class="text-gold">coin ile aç</span></h2>
                <p class="mt-3 text-ink-muted">Paketini seç, kodu gir, oyunu aç. Açtığın oyunun sonraki yamaları da sana açık kalır.</p>
            </div>
 <div class="flex shrink-0 flex-col gap-3 sm:flex-row">
 <x-button :href="route('coins')" variant="gold" size="lg">Coin paketleri</x-button>
 <x-button :href="route('support.index')" variant="ghost" size="lg">Destek</x-button>
 </div>
        </div>
    </section>
@endsection
