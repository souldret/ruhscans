<section class="glass rounded-3xl p-5 sm:p-7" aria-labelledby="gecmis-baslik">
    <div class="flex flex-wrap items-start justify-between gap-3">
        <div>
            <h2 id="gecmis-baslik" class="font-display text-2xl text-mist">Okuma geçmişi</h2>
            <p class="mt-1 text-base text-mist/70">Gizli mod kapalıyken okuduğun bölümler. Kütüphane kaydını silmeden izleri temizleyebilirsin.</p>
        </div>
        @if ($entries->isNotEmpty())
            <button
                type="button"
                wire:click="clearAll"
                wire:confirm="Tüm okuma geçmişini temizlemek istediğine emin misin?"
                class="btn-ghost tap-target px-4 text-sm text-[#FF9CC4] hover:border-[#FF9CC4]/60"
            >
                Geçmişi temizle
            </button>
        @endif
    </div>

    @if ($statusMessage !== '')
        <p class="mt-4 rounded-2xl border border-gold/30 bg-gold/10 px-4 py-2.5 text-sm text-gold" aria-live="polite">
            {{ $statusMessage }}
        </p>
    @endif

    <ol class="mt-5 divide-y divide-white/10">
        @forelse ($entries as $entry)
            <li class="flex flex-wrap items-center justify-between gap-3 py-3.5">
                <div class="min-w-0 flex-1">
                    <p class="truncate text-base font-medium text-mist">{{ $entry->series?->title ?? 'Seri' }}</p>
                    <p class="mt-0.5 text-sm text-mist/60">
                        Bölüm {{ $entry->lastChapter?->number }}
                        @if ($entry->last_page_order)
                            · sayfa {{ $entry->last_page_order }}
                        @endif
                        @if ($entry->completed)
                            · tamamlandı
                        @endif
                    </p>
                </div>
                <div class="flex shrink-0 items-center gap-2">
                    @if ($entry->series && $entry->lastChapter)
                        <a href="{{ route('reader.show', [$entry->series, $entry->lastChapter->number]) }}" class="btn-ghost tap-target px-4 text-sm">
                            Devam
                        </a>
                    @endif
                    <button
                        type="button"
                        wire:click="removeEntry({{ $entry->id }})"
                        class="tap-target rounded-full border border-white/10 px-3 text-sm text-mist/65 hover:border-[#FF9CC4]/60 hover:text-[#FF9CC4]"
                        aria-label="{{ $entry->series?->title }} kaydını geçmişten sil"
                    >
                        Sil
                    </button>
                </div>
            </li>
        @empty
            <li class="py-6 text-center text-base text-mist/60">Okuma geçmişin boş.</li>
        @endforelse
    </ol>
</section>
