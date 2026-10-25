@props(['title', 'games', 'href' => null, 'eyebrow' => null])

<section {{ $attributes->merge(['class' => 'mt-12']) }}
    x-data="rowScroller"
 x-init="$nextTick(() => update())"
>
    <div class="mb-4 flex items-end justify-between gap-4">
        <div>
            @if ($eyebrow)
                <p class="eyebrow mb-1">{{ $eyebrow }}</p>
            @endif
            <h2 class="section-title">{{ $title }}</h2>
        </div>
        <div class="flex items-center gap-2">
            @unless ($games->isEmpty())
                <div class="hidden gap-1.5 sm:flex">
                    <button type="button" @click="back()" :disabled="start" class="grid h-9 w-9 place-items-center rounded-full border border-ink-line bg-white/[0.03] text-ink-text transition hover:bg-white/10 disabled:opacity-30" aria-label="{{ $title }} — geri kaydır">
                        <svg viewBox="0 0 20 20" class="h-4 w-4" fill="none" stroke="currentColor" stroke-width="2" aria-hidden="true"><path d="M12.5 15 7.5 10l5-5" stroke-linecap="round" stroke-linejoin="round"/></svg>
                    </button>
                    <button type="button" @click="forward()" :disabled="end" class="grid h-9 w-9 place-items-center rounded-full border border-ink-line bg-white/[0.03] text-ink-text transition hover:bg-white/10 disabled:opacity-30" aria-label="{{ $title }} — ileri kaydır">
                        <svg viewBox="0 0 20 20" class="h-4 w-4" fill="none" stroke="currentColor" stroke-width="2" aria-hidden="true"><path d="m7.5 15 5-5-5-5" stroke-linecap="round" stroke-linejoin="round"/></svg>
                    </button>
                </div>
            @endunless
            <a href="{{ $href ?? route('games.index') }}" class="group inline-flex items-center gap-1 rounded-full px-3 py-1.5 text-sm text-ink-muted transition hover:bg-white/[0.06] hover:text-ink-text">
                Tümü
                <svg viewBox="0 0 20 20" class="h-4 w-4 transition-transform group-hover:translate-x-0.5" fill="none" stroke="currentColor" stroke-width="2" aria-hidden="true"><path d="m7.5 15 5-5-5-5" stroke-linecap="round" stroke-linejoin="round"/></svg>
            </a>
        </div>
    </div>

    @if ($games->isEmpty())
        <p class="card-flat px-5 py-8 text-sm text-ink-muted">Bu şeritte henüz oyun yok.</p>
    @else
        <div x-ref="track" @scroll.passive.debounce.50ms="update()" class="no-scrollbar flex snap-x snap-mandatory gap-4 overflow-x-auto pb-3 pt-1">
            @foreach ($games as $game)
                <x-game-card :game="$game" class="shrink-0 snap-start" />
            @endforeach
        </div>
    @endif
</section>
