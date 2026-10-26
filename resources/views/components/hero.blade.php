@props(['games'])

@php $total = $games->count(); @endphp

<section
    x-data="heroSlider({{ $total }})"
 x-init="start()"
 @mouseenter="stop()"
 @mouseleave="start()"
 @focusin="stop()"
 @keydown.arrow-right="forward()"
 @keydown.arrow-left="previous()"
 class="relative isolate h-[24rem] overflow-hidden rounded-[2rem] border border-ink-line bg-ink-surface shadow-card sm:h-[28rem]"
    aria-roledescription="slayt gösterisi"
    aria-label="Öne çıkan oyunlar"
>
    @forelse ($games as $i => $game)
        @php
            $cover = $game->cover_path ? asset('storage/'.$game->cover_path) : asset('images/poster.svg');
            $banner = $game->banner_path ? asset('storage/'.$game->banner_path) : null;
        @endphp
        <div
            x-show="showing({{ $i }})"
            @if ($i > 0) x-cloak @endif
            x-transition:enter="transition duration-700 ease-out"
            x-transition:enter-start="opacity-0"
            x-transition:enter-end="opacity-100"
 x-transition:leave="transition duration-500 ease-in"
            x-transition:leave-start="opacity-100"
            x-transition:leave-end="opacity-0"
 class="absolute inset-0"
 :class="slideClass({{ $i }})"
            role="group"
            aria-roledescription="slayt"
            aria-label="{{ $i + 1 }} / {{ $total }}"
        >
            @if ($banner)
                <img src="{{ $banner }}" alt="" aria-hidden="true" @if ($i > 0) loading="lazy" @endif class="absolute inset-0 h-full w-full object-cover opacity-60 motion-safe:animate-ken-burns {{ $adult ? '' : 'blur-2xl' }}">
            @else
                <img src="{{ $cover }}" alt="" aria-hidden="true" @if ($i > 0) loading="lazy" @endif class="absolute inset-0 h-full w-full scale-110 object-cover opacity-40 blur-2xl">
            @endif
            <div class="absolute inset-0 bg-gradient-to-r from-ink via-ink/85 to-ink/10"></div>
            <div class="absolute inset-0 bg-gradient-to-t from-ink via-transparent to-transparent"></div>

            <div class="relative grid h-full min-h-[24rem] items-end gap-8 p-6 pb-14 sm:min-h-[28rem] sm:p-10 sm:pb-16 lg:grid-cols-[1fr_auto] lg:items-center">
                <div class="max-w-xl motion-safe:animate-fade-up">
                    <div class="flex flex-wrap items-center gap-2">
                        <x-badge tone="patch">Türkçe Yama</x-badge>
                        @if (! $game->isFree())
 <x-badge tone="vip">{{ $game->effectivePrice() }} coin</x-badge>
                        @endif
                        @if ($game->translation_percent)
                            <x-badge tone="muted">%{{ (int) $game->translation_percent }} çevrildi</x-badge>
                        @endif
                    </div>
                    <h2 class="mt-5 max-w-3xl break-words font-display text-3xl font-bold leading-[1.1] tracking-tight sm:text-5xl">{{ $game->title }}</h2>
                    @if ($game->developer)
                        <p class="mt-2 text-sm text-ink-muted">{{ $game->developer }}</p>
                    @endif
                    <p class="mt-4 line-clamp-3 max-w-lg leading-7 text-ink-muted">{{ $game->description }}</p>
                    <div class="mt-7 flex flex-wrap gap-3">
                        <x-button :href="route('games.show', $game)" size="lg">
                            <svg viewBox="0 0 20 20" class="h-4 w-4" fill="none" stroke="currentColor" stroke-width="2" aria-hidden="true"><path d="M10 3v10m0 0 4-4m-4 4-4-4M4 16h12" stroke-linecap="round" stroke-linejoin="round"/></svg>
                            İndir
                        </x-button>
                        <x-button :href="route('games.show', $game)" variant="ghost" size="lg">Detay</x-button>
                    </div>
                </div>
                <x-cover :game="$game" :width="176" :height="235" sizes="176px" :lazy="$i > 0" class="hidden w-44 rotate-2 rounded-2xl object-cover shadow-2xl ring-1 ring-white/10 transition duration-500 hover:rotate-0 lg:block {{ $adult ? '' : 'blur-lg' }}" style="aspect-ratio:3/4" />
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
                    <button type="button" role="tab"
                        class="relative h-1.5 overflow-hidden rounded-full bg-white/20 transition-all duration-500"
                        :class="dotClass({{ $i }})"
                        :aria-selected="showing({{ $i }})"
                        @click="go({{ $i }})"
                        aria-label="{{ $game->title }}">
                        <span x-show="showing({{ $i }})" class="absolute inset-0 origin-left rounded-full bg-white" :class="progressClass()"></span>
                    </button>
                @endforeach
            </div>
            <div class="hidden gap-1.5 sm:flex">
                <button type="button" @click="previous()" class="grid h-9 w-9 place-items-center rounded-full border border-white/15 bg-black/30 backdrop-blur transition hover:bg-black/50" aria-label="Önceki slayt">
                    <svg viewBox="0 0 20 20" class="h-4 w-4" fill="none" stroke="currentColor" stroke-width="2" aria-hidden="true"><path d="M12.5 15 7.5 10l5-5" stroke-linecap="round" stroke-linejoin="round"/></svg>
                </button>
                <button type="button" @click="forward()" class="grid h-9 w-9 place-items-center rounded-full border border-white/15 bg-black/30 backdrop-blur transition hover:bg-black/50" aria-label="Sonraki slayt">
                    <svg viewBox="0 0 20 20" class="h-4 w-4" fill="none" stroke="currentColor" stroke-width="2" aria-hidden="true"><path d="m7.5 15 5-5-5-5" stroke-linecap="round" stroke-linejoin="round"/></svg>
                </button>
            </div>
        </div>
    @endif
</section>
