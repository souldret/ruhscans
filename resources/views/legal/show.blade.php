<x-night-layout :seo="$seo">
    <article class="mx-auto max-w-2xl space-y-4 px-4 py-10 text-sm leading-6 text-mist/80">
 <h1 class="font-display text-4xl text-mist">{{ $title }}</h1>
 <p class="text-xs uppercase tracking-wide text-gold">Taslak metin · hukuki inceleme bekliyor · 18+</p>
        @foreach ($paragraphs as $paragraph)
            <p>{{ $paragraph }}</p>
        @endforeach
    </article>
</x-night-layout>
