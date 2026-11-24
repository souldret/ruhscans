<x-night-layout :seo="$seo">
    @php
        $firstChapter = $series->chapters->last();
        $resumeChapter = $entry?->lastChapter ?? $firstChapter;
        $readUrl = $resumeChapter ? route('reader.show', [$series, $resumeChapter->number]) : null;
    @endphp

    <section class="relative overflow-hidden">
        <div class="absolute inset-0 bg-gradient-to-b from-wine/80 via-ink to-ink" aria-hidden="true"></div>
        <div class="relative mx-auto grid max-w-6xl gap-6 px-4 py-8 md:grid-cols-[16rem_1fr] md:py-10">
            <div class="mx-auto w-40 sm:w-48 md:mx-0 md:w-full">
                <x-cover-card :series="$series" compact fluid />
            </div>
            <div>
                <p class="text-sm uppercase tracking-[0.25em] text-gold">{{ $series->type->label() }} · {{ $series->status->label() }}</p>
                <h1 class="mt-2 font-display text-4xl sm:text-5xl">{{ $series->title }}</h1>

                <div class="mt-4" x-data="{ acik: false }">
                    <p class="max-w-2xl text-base leading-7 text-mist/80" :class="acik ? '' : 'line-clamp-3'">{{ $series->description }}</p>
                    @if (mb_strlen($series->description ?? '') > 160)
                        <button type="button" class="tap-target mt-1 text-base text-magenta" @click="acik = !acik" x-text="acik ? 'Daha az göster' : 'Devamını oku'"></button>
                    @endif
                </div>

                <dl class="mt-5 grid grid-cols-2 gap-3 text-base sm:grid-cols-4">
                    <div><dt class="text-mist/50">Yazar</dt><dd>{{ $series->author ?: '—' }}</dd></div>
                    <div><dt class="text-mist/50">Çizer</dt><dd>{{ $series->artist ?: '—' }}</dd></div>
                    <div><dt class="text-mist/50">Yıl</dt><dd>{{ $series->year ?: '—' }}</dd></div>
                    <div><dt class="text-mist/50">Seviye</dt><dd>{{ $series->content_level->label() }}</dd></div>
                </dl>
                <div class="mt-5 flex flex-wrap gap-2">
                    @foreach ($series->tags as $tag)
                        <a class="btn-ghost tap-target" href="{{ route('tags.show', $tag) }}">{{ $tag->name }}</a>
                    @endforeach
                </div>
                <div class="mt-6 flex flex-wrap items-center gap-3">
                    @if ($readUrl)
                        <a href="{{ $readUrl }}" class="btn-magenta tap-target hidden px-7 py-3 text-base font-semibold md:inline-flex">
                            {{ $entry?->lastChapter ? 'Kaldığın yerden devam et · Bölüm '.$entry->lastChapter->display_number : 'Okumaya başla' }}
                        </a>
                    @endif
                    <livewire:series-shelf :series="$series" />
                </div>
            </div>
        </div>
    </section>

    {{-- Büyük sabit okuma butonu (mobilde bottom-nav üstünde) --}}
    @if ($readUrl)
        <div class="glass series-cta-bar fixed inset-x-0 z-20 border-t border-white/10 shadow-[0_-8px_24px_rgba(0,0,0,0.5)] md:hidden">
            <div class="mx-auto max-w-6xl px-4 py-2">
                <a href="{{ $readUrl }}" class="btn-magenta tap-target h-12 w-full text-base font-semibold">
                    {{ $entry?->lastChapter ? 'Devam et · Bölüm '.$entry->lastChapter->display_number : 'Okumaya başla' }}
                </a>
            </div>
        </div>
    @endif

    <section class="mx-auto max-w-6xl px-4 pb-40 pt-10 md:pb-16" aria-labelledby="bolumler">
        <div class="flex items-end justify-between gap-4">
            <h2 id="bolumler" class="font-display text-3xl">Bölümler</h2>
            <span class="text-base text-mist/60">{{ $series->chapters->count() }} bölüm</span>
        </div>
        <ol class="mt-4 divide-y divide-white/10">
            @forelse ($series->chapters as $chapter)
                @php
                    $locked = $chapter->ruh_tasi_fiyat > 0;
                    $early = $chapter->erken_erisim_bitis && $chapter->erken_erisim_bitis->isFuture();
                    $chapterLabel = 'Bölüm '.$chapter->display_number;
                    $hasDistinctTitle = filled($chapter->title) && mb_strtolower(trim($chapter->title)) !== mb_strtolower($chapterLabel);
                @endphp
                <li class="-mx-3 flex items-center justify-between gap-3 rounded-2xl px-3 py-1.5 transition hover:bg-white/[0.03]">
                    <a class="tap-target flex min-w-0 flex-1 !justify-start py-2 text-left" href="{{ route('reader.show', [$series, $chapter->number]) }}">
                        <span class="min-w-0">
                            <span class="block truncate text-base font-medium text-mist">
                                {{ $chapterLabel }}@if($hasDistinctTitle)<span class="font-normal text-mist/70"> · {{ $chapter->title }}</span>@endif
                            </span>
                            <span class="block text-sm text-mist/55">{{ optional($chapter->published_at)->translatedFormat('d M Y') }}</span>
                        </span>
                    </a>
                    <div class="chapter-badges">
                        @if ($chapter->vip_ucretsiz)
                            <span class="badge-vip">VIP</span>
                        @endif
                        @if ($locked)
                            <span class="badge-lock"><x-stone class="h-3 w-3" /> {{ $chapter->ruh_tasi_fiyat }} taş</span>
                        @endif
                        @if ($early)
                            <span class="badge-new">Erken</span>
                        @endif
                        <span class="ml-1 text-mist/35" aria-hidden="true">›</span>
                    </div>
                </li>
            @empty
                <li><x-empty-state title="Bölüm yok" body="İlk sayfalar yüklendiğinde liste burada açılır." /></li>
            @endforelse
        </ol>
    </section>
</x-night-layout>
