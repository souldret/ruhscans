<x-night-layout :seo="$seo">
    <div class="mx-auto max-w-3xl px-4 py-8">
        <h1 class="font-display text-4xl">Okuma geçmişi</h1>
        <p class="mt-2 text-sm text-mist/70">Gizli mod kapalıyken bıraktığın sayfalar.</p>
        <ol class="mt-8 divide-y divide-white/10">
            @forelse ($entries as $entry)
                <li class="flex items-center justify-between gap-3 py-4">
                    <div class="min-w-0">
                        <p class="truncate font-medium">{{ $entry->series?->title }}</p>
                        <p class="text-xs text-mist/50">
                            Bölüm {{ $entry->lastChapter?->number }}
                            @if ($entry->last_page_order)
                                · sayfa {{ $entry->last_page_order }}
                            @endif
                            @if ($entry->completed)
                                · bitti
                            @endif
                        </p>
                    </div>
                    @if ($entry->series && $entry->lastChapter)
                        <a class="btn-ghost shrink-0" href="{{ route('reader.show', [$entry->series, $entry->lastChapter->number]) }}">Devam</a>
                    @endif
                </li>
            @empty
                <li>
                    <x-empty-state title="Geçmiş boş" body="Bir bölüm okuduğunda iz burada kalır. Gizli modda hiçbir şey yazılmaz." />
                </li>
            @endforelse
        </ol>
        <div class="mt-6">{{ $entries->links() }}</div>
    </div>
</x-night-layout>
