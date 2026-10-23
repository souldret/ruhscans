@props(['games'])

<section
    x-data="{ index: 0, total: {{ $games->count() }}, timer: null, start() { if (this.total < 2 || window.matchMedia('(prefers-reduced-motion: reduce)').matches) return; this.timer = setInterval(() => { this.index = (this.index + 1) % this.total }, 6000) }, stop() { clearInterval(this.timer) } }"
    x-init="start()"
    @mouseenter="stop()"
    @mouseleave="start()"
    class="relative isolate overflow-hidden rounded-3xl border border-ink-line bg-ink-surface"
    aria-roledescription="slayt"
>
    @forelse ($games as $i => $game)
        @php $banner = $game->banner_path ? asset('storage/'.$game->banner_path) : ($game->cover_path ? asset('storage/'.$game->cover_path) : asset('images/banner.svg')); @endphp
        <div x-show="index === {{ $i }}" x-cloak class="relative min-h-[28rem]">
            <img src="{{ $banner }}" alt="" class="absolute inset-0 h-full w-full scale-110 object-cover opacity-40 blur-2xl" aria-hidden="true">
            <div class="absolute inset-0 bg-gradient-to-r from-ink via-ink/80 to-transparent"></div>

            <div class="relative grid items-end gap-8 p-6 sm:p-10 lg:grid-cols-[1fr_auto]">
                <div class="max-w-xl">
                    <x-badge tone="patch">Türkçe Yama</x-badge>
                    <h1 class="mt-4 font-display text-4xl font-semibold leading-tight tracking-tight sm:text-6xl">{{ $game->title }}</h1>
                    <p class="mt-4 line-clamp-3 text-ink-muted">{{ $game->description }}</p>
                    <div class="mt-6 flex flex-wrap gap-3">
                        <x-button :href="route('games.show', $game)">İndir</x-button>
                        <x-button :href="route('games.show', $game)" variant="ghost">Detay</x-button>
                    </div>
                </div>
                <img src="{{ $game->cover_path ? asset('storage/'.$game->cover_path) : asset('images/poster.svg') }}" alt="{{ $game->title }} afişi" width="600" height="800" class="hidden aspect-[3/4] w-52 rounded-2xl object-cover lg:block">
            </div>
        </div>
    @empty
        <div class="p-10">
            <h1 class="font-display text-4xl font-semibold">Henüz öne çıkan oyun yok</h1>
            <p class="mt-3 text-ink-muted">Yeni çeviriler burada yer alacak.</p>
        </div>
    @endforelse

    @if ($games->count() > 1)
        <div class="absolute bottom-4 right-4 flex gap-2" role="tablist" aria-label="Öne çıkan oyunlar">
            @foreach ($games as $i => $game)
                <button type="button" class="h-2.5 w-2.5 rounded-full bg-white/30 aria-selected:w-6 aria-selected:bg-white" :aria-selected="index === {{ $i }}" :class="index === {{ $i }} ? 'w-6 bg-white' : ''" @click="index = {{ $i }}" aria-label="{{ $game->title }}"></button>
            @endforeach
        </div>
    @endif
</section>
