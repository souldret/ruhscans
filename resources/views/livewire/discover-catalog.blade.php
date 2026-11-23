<div x-data="{ odak: @js(request('odak') === 'ara') }">
    {{-- Yapışkan arama çubuğu --}}
    <div class="glass sticky top-14 z-20 border-b border-white/5 md:top-16">
        <div class="mx-auto max-w-6xl px-4 py-3">
            <div class="flex items-center gap-2">
                <label class="relative min-w-0 flex-1">
                    <span class="sr-only">Başlık veya alternatif ad ara</span>
                    <svg class="pointer-events-none absolute left-4 top-1/2 h-5 w-5 -translate-y-1/2 text-mist/50" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8" aria-hidden="true"><circle cx="11" cy="11" r="7"/><path stroke-linecap="round" d="m20 20-3.5-3.5"/></svg>
                    <input
                        id="kesfet-ara"
                        wire:model.live.debounce.300ms="q"
                        x-ref="ara"
                        type="search"
                        placeholder="Başlık veya alternatif ad"
                        class="h-12 w-full rounded-2xl border-white/10 bg-ink pl-12 pr-4 text-base text-mist placeholder:text-mist/45 focus:border-magenta focus:ring-magenta"
                        @if (request('odak') === 'ara') autofocus @endif
                    >
                </label>
                @php
                    $activeFilterCount = collect([$type, $status, $level])->filter()->count();
                @endphp
                <button
                    type="button"
                    class="tap-target relative inline-flex shrink-0 items-center gap-2 rounded-2xl border px-4 text-base transition-colors {{ $activeFilterCount > 0 ? 'border-magenta bg-wine/40 text-magenta' : 'border-white/10 text-mist hover:border-magenta/50' }}"
                    @click="$wire.filtersOpen = true"
                    aria-label="Filtreleri aç"
                >
                    <svg class="h-5 w-5" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8" aria-hidden="true"><path stroke-linecap="round" d="M4 6h16M7 12h10M10 18h4"/></svg>
                    <span class="hidden sm:inline">Filtre</span>
                    @if ($activeFilterCount > 0)
                        <span class="inline-flex h-5 min-w-5 items-center justify-center rounded-full bg-magenta px-1.5 text-xs font-bold text-ink">{{ $activeFilterCount }}</span>
                    @endif
                </button>
            </div>
            <div class="no-scrollbar mt-3 flex gap-2 overflow-x-auto pb-1 md:hidden" aria-label="Sıralama">
                @foreach (['yeni' => 'Yeni', 'eski' => 'Eski', 'baslik' => 'Başlık', 'yil' => 'Yıl'] as $value => $label)
                    <button type="button" wire:click="$set('sort', '{{ $value }}')" class="tap-target shrink-0 rounded-full px-4 text-sm font-medium {{ $sort === $value ? 'bg-magenta text-ink' : 'border border-white/10 text-mist/80' }}">{{ $label }}</button>
                @endforeach
            </div>
        </div>
    </div>

    <div class="mx-auto max-w-6xl px-4 pb-10 pt-6">
        <h1 class="font-display text-4xl">Keşfet</h1>
        <p class="mt-2 text-base text-mist/70">Yazarken süzülür. Filtreler alttan açılır.</p>

        {{-- Masaüstü filtre çubuğu --}}
        <div class="mt-6 hidden items-center gap-2 md:flex">
            <span class="text-base text-mist/60">Sıra</span>
            @foreach (['yeni' => 'Yeni', 'eski' => 'Eski', 'baslik' => 'Başlık', 'yil' => 'Yıl'] as $value => $label)
                <button type="button" wire:click="$set('sort', '{{ $value }}')" class="tap-target rounded-full px-4 text-base {{ $sort === $value ? 'bg-magenta text-ink' : 'border border-white/10 text-mist/80' }}">{{ $label }}</button>
            @endforeach
            @if ($q !== '' || $type !== '' || $status !== '' || $level !== '')
                <button type="button" wire:click="resetFilters" class="tap-target ml-auto text-base text-gold">Filtreleri temizle</button>
            @endif
        </div>

        <p class="mt-4 text-base text-mist/60" aria-live="polite" wire:loading.remove>
            {{ $series->total() }} seri bulundu
        </p>

        {{-- Skeleton --}}
        <div wire:loading.delay class="mt-4 grid grid-cols-2 gap-4 sm:grid-cols-3 lg:grid-cols-6" aria-hidden="true">
            @foreach (range(1, 6) as $placeholder)
                <div>
                    <div class="skeleton cover-frame"></div>
                    <div class="skeleton mt-2 h-4 w-3/4"></div>
                </div>
            @endforeach
        </div>

        {{-- Sonuçlar --}}
        <div wire:loading.remove class="mt-4 grid grid-cols-2 gap-4 sm:grid-cols-3 lg:grid-cols-6">
            @forelse ($series as $item)
                <x-cover-card :series="$item" fluid />
            @empty
                <div class="col-span-full">
                    <x-empty-state title="Bu gece eşleşme yok" body="Filtreyi gevşet ya da başka bir ad dene. Kara listedeki etiketler zaten listede görünmez.">
                        <x-slot:action>
                            <button type="button" wire:click="resetFilters" class="btn-magenta tap-target">Filtreleri temizle</button>
                        </x-slot:action>
                    </x-empty-state>
                </div>
            @endforelse
        </div>

        {{-- Daha fazla / sonsuz kaydırma --}}
        @if ($series->hasMorePages())
            <div class="mt-8 flex justify-center" x-intersect="$wire.loadMore()">
                <button type="button" wire:click="loadMore" wire:loading.attr="disabled" class="btn-ghost tap-target px-8 text-base">
                    <span wire:loading.remove>Daha fazla göster</span>
                    <span wire:loading>Yükleniyor…</span>
                </button>
            </div>
        @endif
    </div>

    {{-- Alttan açılan filtre sheet'i (mobilde bottom sheet, masaüstünde ortalanmış panel) --}}
    <div
        x-cloak
        x-show="$wire.filtersOpen"
        x-transition.opacity.duration.200ms
        class="fixed inset-0 z-40 flex items-end justify-center bg-black/75 md:items-center md:p-4"
        @click.self="$wire.filtersOpen = false"
        @keydown.escape.window="if ($wire.filtersOpen) $wire.filtersOpen = false"
    >
        <div
            class="glass safe-bottom w-full max-h-[85dvh] overflow-y-auto rounded-t-3xl border-t border-white/10 p-5 md:max-w-lg md:rounded-3xl md:border"
            x-transition:enter="transition-transform duration-200"
            x-transition:enter-start="translate-y-full md:translate-y-4"
            x-transition:enter-end="translate-y-0"
            role="dialog"
            aria-modal="true"
            aria-label="Filtreler"
        >
            <div class="sheet-handle md:hidden" aria-hidden="true"></div>
            <div class="flex items-center justify-between">
                <h2 class="font-display text-2xl">Filtreler</h2>
                <button type="button" class="tap-target rounded-full px-4 text-2xl text-mist/70" @click="$wire.filtersOpen = false" aria-label="Filtreleri kapat">×</button>
            </div>

            <div class="mt-5 space-y-4">
                <label class="block">
                    <span class="mb-2 block text-base text-mist/80">Tür</span>
                    <select wire:model.live="type" class="h-12 w-full rounded-2xl border-white/10 bg-ink text-base text-mist">
                        <option value="">Tümü</option>
                        @foreach ($types as $item)
                            <option value="{{ $item->value }}">{{ $item->label() }}</option>
                        @endforeach
                    </select>
                </label>
                <label class="block">
                    <span class="mb-2 block text-base text-mist/80">Durum</span>
                    <select wire:model.live="status" class="h-12 w-full rounded-2xl border-white/10 bg-ink text-base text-mist">
                        <option value="">Tümü</option>
                        @foreach ($statuses as $item)
                            <option value="{{ $item->value }}">{{ $item->label() }}</option>
                        @endforeach
                    </select>
                </label>
                <label class="block">
                    <span class="mb-2 block text-base text-mist/80">Seviye</span>
                    <select wire:model.live="level" class="h-12 w-full rounded-2xl border-white/10 bg-ink text-base text-mist">
                        <option value="">Tümü</option>
                        @foreach ($levels as $item)
                            <option value="{{ $item->value }}">{{ $item->label() }}</option>
                        @endforeach
                    </select>
                </label>
            </div>

            <div class="mt-6 flex gap-3">
                <button type="button" wire:click="resetFilters" class="btn-ghost tap-target flex-1">Temizle</button>
                <button type="button" class="btn-magenta tap-target flex-1" @click="$wire.filtersOpen = false">Sonuçları gör</button>
            </div>
        </div>
    </div>
</div>
