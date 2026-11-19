<x-night-layout title="Kütüphane · Ruhscans">
    <div class="mx-auto max-w-6xl px-4 py-8">
        <h1 class="font-display text-4xl">Kütüphane</h1>
        <p class="mt-2 text-sm text-mist/70">Kaydettiğin seriler. Puanın ve son bölümün yanında durur.</p>
        <div class="mt-8 flex flex-wrap gap-4">
            @forelse ($entries as $entry)
                <div>
                    <x-cover-card :series="$entry->series" />
                    @if ($entry->rating)
                        <p class="mt-1 text-xs text-gold">{{ $entry->rating }}/5</p>
                    @endif
                </div>
            @empty
                <x-empty-state class="w-full" title="Rafın boş" body="Bir seriyi kütüphaneye eklediğinde burada kalır.">
                    <x-slot:action>
                        <a class="btn-magenta" href="{{ route('discover') }}">Keşfet</a>
                    </x-slot:action>
                </x-empty-state>
            @endforelse
        </div>
        <div class="mt-8">{{ $entries->links() }}</div>
    </div>
</x-night-layout>
