@props(['game'])

@php
    $cover = $game->cover_path ? asset('storage/'.$game->cover_path) : asset('images/poster.svg');
    $percent = (int) $game->translation_percent;
    $dash = round(97.4 * $percent / 100, 1);
@endphp

<a href="{{ route('games.show', $game) }}" class="group block w-44 shrink-0 focus-visible:outline sm:w-52">
    <article>
        <div class="relative aspect-[3/4] overflow-hidden rounded-2xl bg-ink-raised transition duration-base ease-out group-hover:-translate-y-1 group-hover:glow-accent motion-reduce:transform-none">
            <img
                src="{{ $cover }}"
                alt="{{ $game->title }} afişi"
                width="600"
                height="800"
                loading="lazy"
                decoding="async"
                class="h-full w-full object-cover"
            >
            <div class="absolute left-2 top-2">
                @if ($game->access->value === 'vip')
                    <x-badge tone="vip">
                        <svg viewBox="0 0 16 16" class="h-3 w-3" aria-hidden="true" fill="currentColor"><path d="M2 12.5h12l-1.2-6.2-3.2 2.4L8 4.2 6.4 8.7 3.2 6.3 2 12.5Z"/></svg>
                        VIP
                    </x-badge>
                @else
                    <x-badge>Ücretsiz</x-badge>
                @endif
            </div>
        </div>

        <div class="mt-3 flex items-start justify-between gap-3">
            <div class="min-w-0">
                <h3 class="truncate font-display text-sm font-semibold text-ink-text">{{ $game->title }}</h3>
                <p class="mt-0.5 text-xs text-ink-muted">sürüm {{ $game->translation_version ?: $game->game_version }}</p>
            </div>
            <svg viewBox="0 0 36 36" class="h-9 w-9 shrink-0" role="img" aria-label="Çeviri yüzde {{ $percent }}">
                <circle cx="18" cy="18" r="15.5" fill="none" stroke="rgba(255,255,255,0.08)" stroke-width="3"></circle>
                <circle cx="18" cy="18" r="15.5" fill="none" stroke="#ff4d8d" stroke-width="3" stroke-linecap="round"
                        stroke-dasharray="{{ $dash }} 97.4" transform="rotate(-90 18 18)"></circle>
                <text x="18" y="19" text-anchor="middle" dominant-baseline="middle" fill="#F2F2F7" font-size="8" font-family="Plus Jakarta Sans">{{ $percent }}</text>
            </svg>
        </div>
    </article>
</a>
