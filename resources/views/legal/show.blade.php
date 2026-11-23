<x-night-layout :seo="$seo">
    <article class="mx-auto max-w-2xl space-y-5 px-4 pb-16 pt-8">
        <p class="text-sm font-semibold uppercase tracking-[0.2em] text-gold">Ruhscans · 18+</p>
        <h1 class="font-display text-4xl leading-tight text-mist">{{ $title }}</h1>
        <p class="rounded-2xl border border-gold/30 bg-gold/10 px-4 py-3 text-base text-gold">Taslak metin · hukuki inceleme bekliyor · 18+</p>
        <div class="glass space-y-5 rounded-3xl p-6 text-base leading-7 text-mist/80 sm:p-8">
            @foreach ($paragraphs as $paragraph)
                <p>{{ $paragraph }}</p>
            @endforeach
        </div>
    </article>
</x-night-layout>
