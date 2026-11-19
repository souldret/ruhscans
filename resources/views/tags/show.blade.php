<x-night-layout :title="$tag->name.' · Ruhscans'">
    <div class="mx-auto max-w-6xl px-4 py-8">
        <p class="text-xs uppercase tracking-[0.25em] text-magenta">Etiket</p>
        <h1 class="mt-2 font-display text-4xl">{{ $tag->name }}</h1>
        <div class="mt-8 flex flex-wrap gap-4">
            @forelse ($series as $item)
                <x-cover-card :series="$item" />
            @empty
                <x-empty-state class="w-full" title="Bu etikette seri yok" body="Kara liste ya da gizleme bu rafı boş bırakmış olabilir." />
            @endforelse
        </div>
        <div class="mt-8">{{ $series->links() }}</div>
    </div>
</x-night-layout>
