<x-guest-layout>
    <article class="space-y-4 text-sm leading-6 text-gray-700">
        <h1 class="text-2xl font-semibold text-gray-900">{{ $title }}</h1>
        <p class="text-xs uppercase tracking-wide text-gray-400">Taslak metin · hukuki inceleme bekliyor</p>
        @foreach ($paragraphs as $paragraph)
            <p>{{ $paragraph }}</p>
        @endforeach
    </article>
</x-guest-layout>
