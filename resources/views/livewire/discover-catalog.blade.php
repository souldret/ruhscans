<div>
    <div class="mx-auto max-w-6xl px-4 py-8">
        <h1 class="font-display text-4xl">Keşfet</h1>
        <p class="mt-2 text-sm text-mist/70">Yazarken süzülür. Tür, durum ve seviye aynı anda daraltır.</p>

        <form class="glass mt-6 grid gap-3 rounded-3xl p-4 md:grid-cols-5" wire:submit.prevent>
            <label class="md:col-span-2">
                <span class="sr-only">Ara</span>
                <input id="kesfet-ara" wire:model.live.debounce.300ms="q" type="search" placeholder="Başlık veya alternatif ad" class="w-full rounded-2xl border-white/10 bg-ink text-mist" @if (request('odak') === 'ara') autofocus @endif>
            </label>
            <label>
                <span class="sr-only">Tür</span>
                <select wire:model.live="type" class="w-full rounded-2xl border-white/10 bg-ink text-mist">
                    <option value="">Tür</option>
                    @foreach ($types as $item)
                        <option value="{{ $item->value }}">{{ $item->label() }}</option>
                    @endforeach
                </select>
            </label>
            <label>
                <span class="sr-only">Durum</span>
                <select wire:model.live="status" class="w-full rounded-2xl border-white/10 bg-ink text-mist">
                    <option value="">Durum</option>
                    @foreach ($statuses as $item)
                        <option value="{{ $item->value }}">{{ $item->label() }}</option>
                    @endforeach
                </select>
            </label>
            <label>
                <span class="sr-only">Seviye</span>
                <select wire:model.live="level" class="w-full rounded-2xl border-white/10 bg-ink text-mist">
                    <option value="">Seviye</option>
                    @foreach ($levels as $item)
                        <option value="{{ $item->value }}">{{ $item->label() }}</option>
                    @endforeach
                </select>
            </label>
        </form>

        <div class="mt-4 flex flex-wrap items-center gap-2 text-sm">
            <span class="text-mist/60">Sıra</span>
            @foreach (['yeni' => 'Yeni', 'eski' => 'Eski', 'baslik' => 'Başlık', 'yil' => 'Yıl'] as $value => $label)
                <button type="button" wire:click="$set('sort', '{{ $value }}')" class="{{ $sort === $value ? 'btn-magenta' : 'btn-ghost' }} !py-1">{{ $label }}</button>
            @endforeach
        </div>

        <div wire:loading.delay class="mt-8 grid grid-cols-2 gap-4 sm:grid-cols-3 lg:grid-cols-6" aria-hidden="true">
            @foreach (range(1, 6) as $placeholder)
                <div class="skeleton cover-frame"></div>
            @endforeach
        </div>

        <div wire:loading.remove class="mt-8 flex flex-wrap gap-4">
            @forelse ($series as $item)
                <x-cover-card :series="$item" />
            @empty
                <x-empty-state class="w-full" title="Bu gece eşleşme yok" body="Filtreyi gevşet ya da başka bir ad dene. Kara listedeki etiketler zaten listede görünmez." />
            @endforelse
        </div>

        <div class="mt-8">{{ $series->links() }}</div>
    </div>
</div>
