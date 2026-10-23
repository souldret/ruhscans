@extends('layouts.site')

@section('title', 'VIP — '.config('app.name'))

@section('content')
    <section class="overflow-hidden rounded-3xl border border-ink-line bg-ink-surface p-8">
        <p class="text-sm uppercase tracking-[0.2em] text-ink-muted">Üyelik</p>
        <h1 class="mt-2 font-display text-4xl font-semibold">VIP ile erken erişim</h1>
        <p class="mt-3 max-w-xl text-ink-muted">Altın rozetli oyunlar, yeni yamalar ve güncellemeler önce VIP üyelere açılır.</p>
        <div class="mt-6">
            <x-button href="{{ route('register') }}" variant="gold">Üye ol</x-button>
        </div>
    </section>

    <x-section-row title="VIP oyunlar" :games="$games" class="mt-8" />
@endsection
