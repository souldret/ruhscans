@extends('layouts.site')

@section('title', 'İndirme sınırı — '.config('app.name'))

@section('content')
    <section class="card mx-auto max-w-lg p-8 py-16 text-center sm:p-12">
        <div class="mx-auto grid h-16 w-16 place-items-center rounded-2xl bg-brand-pink/10 text-brand-pink ring-1 ring-brand-pink/30">
            <svg viewBox="0 0 24 24" class="h-8 w-8" fill="none" stroke="currentColor" stroke-width="2"><circle cx="12" cy="12" r="10"/><path d="M12 6v6l4 2" stroke-linecap="round"/></svg>
        </div>
        <h1 class="mt-6 font-display text-2xl font-bold tracking-tight sm:text-3xl">Günlük indirme sınırına ulaştın</h1>
        <p class="mx-auto mt-3 max-w-md text-sm leading-6 text-ink-muted">Bugün {{ $limit }} dosya indirebildin. Yarın yeniden açılır.</p>
        <div class="mt-8 flex flex-col justify-center gap-3 sm:flex-row">
            <x-button :href="route('games.index')">Oyunlara dön</x-button>
            <x-button :href="route('support.index')" variant="ghost">Destek</x-button>
        </div>
    </section>
@endsection
