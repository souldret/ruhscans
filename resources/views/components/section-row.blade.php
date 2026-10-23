@props(['title', 'games'])

<section {{ $attributes->merge(['class' => 'mt-10']) }}>
    <div class="mb-4 flex items-end justify-between">
        <h2 class="font-display text-xl font-semibold tracking-tight">{{ $title }}</h2>
        <a href="{{ route('games.index') }}" class="text-sm text-ink-muted hover:text-ink-text">Tümü</a>
    </div>

    @if ($games->isEmpty())
        <p class="rounded-2xl border border-ink-line bg-ink-surface px-5 py-8 text-sm text-ink-muted">Bu şeritte henüz oyun yok.</p>
    @else
        <div class="no-scrollbar flex gap-4 overflow-x-auto pb-2">
            @foreach ($games as $game)
                <x-game-card :game="$game" />
            @endforeach
        </div>
    @endif
</section>
