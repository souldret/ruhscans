<x-night-layout :seo="$seo">
    <div class="mx-auto max-w-6xl px-4 pb-16 pt-8">
        <p class="text-sm font-semibold uppercase tracking-[0.2em] text-gold">Rafın</p>
        <h1 class="mt-2 font-display text-4xl text-mist">Kütüphane</h1>
        <p class="mt-2 text-base text-mist/70">Kaydettiğin seriler. Puanın ve son bölümün yanında durur.</p>

        @if ($entries->isEmpty())
            <div class="mt-8">
                <x-empty-state title="Rafın boş" body="Bir seriyi kütüphaneye eklediğinde burada kalır.">
                    <x-slot:action>
                        <a class="btn-magenta tap-target px-6" href="{{ route('discover') }}">Keşfet</a>
                    </x-slot:action>
                </x-empty-state>
            </div>
        @else
            <ul class="mt-8 grid grid-cols-2 gap-4 sm:grid-cols-3 lg:grid-cols-6">
                @foreach ($entries as $entry)
                    <li>
                        <x-cover-card
                            :series="$entry->series"
                            fluid
                            :subtitle="$entry->rating ? '★ '.$entry->rating.'/5' : ($entry->lastChapter ? 'Bölüm '.$entry->lastChapter->number : null)"
                        />
                    </li>
                @endforeach
            </ul>
            <div class="mt-8">{{ $entries->links() }}</div>
        @endif
    </div>
</x-night-layout>
