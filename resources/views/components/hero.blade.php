@props(['games'])

@php $total = $games->count(); @endphp

<section
    data-hero
    tabindex="0"
    class="relative isolate h-[24rem] overflow-hidden rounded-[2rem] border border-ink-line bg-ink-surface shadow-card outline-none sm:h-[28rem]"
    aria-roledescription="slayt gösterisi"
    aria-label="Öne çıkan oyunlar"
>
    @forelse ($games as $i => $game)
        @php
            $cover = $game->cover_path ? asset('storage/'.$game->cover_path) : asset('images/poster.svg');
            $banner = $game->banner_path ? asset('storage/'.$game->banner_path) : null;
        @endphp
        <div
            data-slide
            @if ($i > 0) hidden @endif
            class="absolute inset-0"
            role="group"
            aria-roledescription="slayt"
            aria-hidden="{{ $i === 0 ? 'false' : 'true' }}"
            aria-label="{{ $i + 1 }} / {{ $total }}"
        >
            @if ($banner)
                <img src="{{ $banner }}" alt="" aria-hidden="true" @if ($i > 0) loading="lazy" @endif class="absolute inset-0 h-full w-full object-cover opacity-60 motion-safe:animate-ken-burns {{ $adult ? '' : 'blur-2xl' }}">
            @else
                <img src="{{ $cover }}" alt="" aria-hidden="true" @if ($i > 0) loading="lazy" @endif class="absolute inset-0 h-full w-full scale-110 object-cover opacity-40 blur-2xl">
            @endif
            <div class="absolute inset-0 bg-gradient-to-r from-ink via-ink/85 to-ink/10"></div>
            <div class="absolute inset-0 bg-gradient-to-t from-ink via-transparent to-transparent"></div>

            <div class="relative grid h-full min-h-0 items-center gap-6 overflow-hidden px-6 py-8 pb-16 sm:px-10 min-[900px]:grid-cols-[minmax(0,1fr)_11rem]">
                <div class="min-w-0 max-w-xl motion-safe:animate-fade-up">
                    <div class="flex flex-wrap items-center gap-2">
                        <x-badge tone="patch">Türkçe Yama</x-badge>
                        @if (! $game->isFree())
                            <x-badge tone="vip">{{ $game->effectivePrice() }} coin</x-badge>
                        @endif
                        @if ($game->translation_percent)
                            <x-badge tone="muted">%{{ (int) $game->translation_percent }} çevrildi</x-badge>
                        @endif
                    </div>
                    <h2 class="mt-5 line-clamp-2 break-words font-display text-3xl font-bold leading-[1.1] tracking-tight sm:text-5xl">{{ $game->title }}</h2>
                    @if ($game->developer)
                        <p class="mt-2 text-sm text-ink-muted">{{ $game->developer }}</p>
                    @endif
                    <p class="mt-4 line-clamp-2 max-w-lg leading-7 text-ink-muted">{{ $game->description }}</p>
                    <div class="mt-7 flex flex-wrap gap-3">
                        <x-button :href="route('games.show', $game)" size="lg">
                            <svg viewBox="0 0 20 20" class="h-4 w-4" fill="none" stroke="currentColor" stroke-width="2" aria-hidden="true"><path d="M10 3v10m0 0 4-4m-4 4-4-4M4 16h12" stroke-linecap="round" stroke-linejoin="round"/></svg>
                            İndir
                        </x-button>
                        <x-button :href="route('games.show', $game)" variant="ghost" size="lg">Detay</x-button>
                    </div>
                </div>
                <img src="{{ $cover }}" alt="{{ $game->title }} afişi" width="176" height="235" @if ($i > 0) loading="lazy" @endif decoding="async" class="hidden h-56 w-auto max-w-full shrink-0 justify-self-end self-center rounded-2xl object-cover shadow-2xl ring-1 ring-white/10 min-[900px]:block {{ $adult ? '' : 'blur-lg' }}">
            </div>
        </div>
    @empty
        <div class="relative min-h-[18rem] p-10">
            <div class="absolute inset-0 bg-accent opacity-10"></div>
            <div class="relative">
                <h2 class="font-display text-4xl font-semibold">Henüz öne çıkan oyun yok</h2>
                <p class="mt-3 text-ink-muted">Yeni çeviriler burada yer alacak.</p>
            </div>
        </div>
    @endforelse

    @if ($total > 1)
        <div class="absolute bottom-5 left-6 right-6 z-10 flex items-center justify-between gap-4 sm:left-10 sm:right-10">
            <div class="flex gap-2" role="tablist" aria-label="Slayt seç">
                @foreach ($games as $i => $game)
                    <button type="button" role="tab" data-hero-dot data-index="{{ $i }}"
                            class="relative h-1.5 overflow-hidden rounded-full bg-white/20 transition-all duration-500 {{ $i === 0 ? 'w-10' : 'w-4' }}"
                            aria-selected="{{ $i === 0 ? 'true' : 'false' }}"
                            aria-label="{{ $game->title }}">
                        <span data-hero-progress class="absolute inset-0 origin-left rounded-full bg-white {{ $i === 0 ? 'animate-progress' : 'hidden' }}"></span>
                    </button>
                @endforeach
            </div>
            <div class="hidden gap-1.5 sm:flex">
                <button type="button" data-hero-prev class="grid h-9 w-9 place-items-center rounded-full border border-white/15 bg-black/30 backdrop-blur transition hover:bg-black/50" aria-label="Önceki slayt">
                    <svg viewBox="0 0 20 20" class="h-4 w-4" fill="none" stroke="currentColor" stroke-width="2" aria-hidden="true"><path d="M12.5 15 7.5 10l5-5" stroke-linecap="round" stroke-linejoin="round"/></svg>
                </button>
                <button type="button" data-hero-next class="grid h-9 w-9 place-items-center rounded-full border border-white/15 bg-black/30 backdrop-blur transition hover:bg-black/50" aria-label="Sonraki slayt">
                    <svg viewBox="0 0 20 20" class="h-4 w-4" fill="none" stroke="currentColor" stroke-width="2" aria-hidden="true"><path d="m7.5 15 5-5-5-5" stroke-linecap="round" stroke-linejoin="round"/></svg>
                </button>
            </div>
        </div>
    @endif
</section>
