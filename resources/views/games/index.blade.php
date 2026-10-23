@extends('layouts.site')

@section('title', 'Oyunlar — '.config('app.name'))

@section('content')
    <h1 class="font-display text-3xl font-semibold">Oyunlar</h1>

    <form method="GET" action="{{ route('games.index') }}" role="search" class="mt-4">
        <label for="q" class="sr-only">Ara</label>
        <input id="q" name="q" value="{{ $query }}" placeholder="Oyun, geliştirici veya etiket" class="w-full rounded-full border-ink-line bg-ink-surface px-4 py-2.5 text-sm text-ink-text placeholder:text-ink-muted">
    </form>

    @if ($games->isEmpty())
        <p class="mt-8 rounded-2xl border border-ink-line bg-ink-surface px-5 py-10 text-ink-muted">Aramanla eşleşen oyun yok. Başka bir kelime dene.</p>
    @else
        <div class="mt-8 flex flex-wrap gap-4">
            @foreach ($games as $game)
                <x-game-card :game="$game" />
            @endforeach
        </div>
        <div class="mt-8">{{ $games->links() }}</div>
    @endif
@endsection
