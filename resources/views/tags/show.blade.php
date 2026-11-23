<x-night-layout :seo="$seo">
    <div class="mx-auto max-w-6xl px-4 pb-16 pt-8">
        <p class="text-sm font-semibold uppercase tracking-[0.25em] text-magenta">Etiket</p>
        <h1 class="mt-2 font-display text-4xl text-mist">{{ $tag->name }}</h1>

        @if ($series->isEmpty())
            <div class="mt-8">
                <x-empty-state title="Bu etikette seri yok" body="Kara liste ya da gizleme bu rafı boş bırakmış olabilir." />
            </div>
        @else
            <ul class="mt-8 grid grid-cols-2 gap-4 sm:grid-cols-3 lg:grid-cols-6">
                @foreach ($series as $item)
                    <li>
                        <x-cover-card :series="$item" fluid />
                    </li>
                @endforeach
            </ul>
            <div class="mt-8">{{ $series->links() }}</div>
        @endif
    </div>
</x-night-layout>
