@props(['game'])

@php
    $percent = max(0, min(100, (int) $game->translation_percent));
       $version = $game->translation_version ?: $game->game_version;
   @endphp

<a href="{{ route('games.show', $game) }}" {{ $attributes->merge(['class' => 'group block w-36 rounded-2xl focus-visible:outline sm:w-40']) }}>
    <article>
        <div class="relative overflow-hidden rounded-2xl bg-ink-raised ring-1 ring-inset ring-white/5 transition duration-300 ease-out group-hover:-translate-y-1 group-hover:shadow-glow" style="aspect-ratio:3/4">
            <x-cover :game="$game" class="transition duration-500 ease-out group-hover:scale-105 {{ $adult ? '' : 'blur-md' }}" />
            <div class="pointer-events-none absolute inset-0 bg-gradient-to-t from-black/70 via-transparent to-black/20 opacity-80 transition-opacity duration-300 group-hover:opacity-100"></div>
            <div class="absolute left-2 top-2 flex flex-col items-start gap-1">
 @php $sale = ! $game->isFree() && $game->sale_price !== null && ($game->sale_ends_at === null || $game->sale_ends_at->isFuture()) && $game->sale_price < $game->coin_price; @endphp
 @if ($game->owned ?? false)
 <x-badge tone="ok">Sahipsin</x-badge>
 @elseif ($game->isFree())
 <x-badge>ÜCRETSİZ</x-badge>
 @else
 <x-badge tone="vip">
 <svg viewBox="0 0 16 16" class="h-3 w-3" fill="currentColor" aria-hidden="true"><path d="M8 1.2 9.7 5.6H14.4L10.6 8.3 12 12.8 8 10.1 4 12.8l1.4-4.5L1.6 5.6h4.7L8 1.2Z"/></svg>
 {{ $game->effectivePrice() }}
 </x-badge>
 @if ($sale)
 <span class="rounded-full bg-black/60 px-2 py-0.5 text-[10px] text-white/70 line-through">{{ $game->coin_price }}</span>
 @endif
 @endif
 </div>
 <div class="absolute bottom-2 right-2 rounded-full bg-black/70 px-2 py-1 text-[11px] font-semibold text-white backdrop-blur">%{{ $percent }}</div>
        </div>

        <div class="mt-3 min-w-0 px-0.5">
            <h3 class="line-clamp-2 break-words font-display text-sm font-semibold leading-snug text-ink-text transition-colors group-hover:text-white" title="{{ $game->title }}">{{ $game->title }}</h3>
            @if ($version)
                <p class="mt-1 truncate text-xs text-ink-muted">sürüm {{ $version }}</p>
            @endif
        </div>
    </article>
</a>
