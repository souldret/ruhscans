<x-night-layout :seo="$seo">
    <div class="mx-auto max-w-3xl px-4 pb-16 pt-8">
        <p class="text-sm font-semibold uppercase tracking-[0.2em] text-gold">İzler</p>
        <h1 class="mt-2 font-display text-4xl text-mist">Okuma geçmişi</h1>
        <p class="mt-2 text-base text-mist/70">Gizli mod kapalıyken bıraktığın sayfalar.</p>
        <ol class="mt-8 divide-y divide-white/10">
            @forelse ($entries as $entry)
                <li class="flex items-center justify-between gap-3 py-3">
                    <div class="min-w-0">
                        <p class="truncate text-base font-medium text-mist">{{ $entry->series?->title }}</p>
                        <p class="mt-0.5 text-sm text-mist/60">
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
                        <a class="btn-ghost tap-target shrink-0 px-4 text-sm" href="{{ route('reader.show', [$entry->series, $entry->lastChapter->number]) }}">Devam</a>
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
