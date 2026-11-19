<x-night-layout :seo="$seo">
    <section class="relative overflow-hidden">
        <div class="absolute inset-0 bg-gradient-to-b from-wine/80 via-ink to-ink" aria-hidden="true"></div>
        <div class="relative mx-auto grid max-w-6xl gap-8 px-4 py-10 md:grid-cols-[16rem_1fr]">
            <x-cover-card :series="$series" compact />
            <div>
                <p class="text-xs uppercase tracking-[0.25em] text-gold">{{ $series->type->label() }} · {{ $series->status->label() }}</p>
                <h1 class="mt-2 font-display text-4xl sm:text-5xl">{{ $series->title }}</h1>
                <p class="mt-4 max-w-2xl text-sm leading-6 text-mist/80">{{ $series->description }}</p>
                <dl class="mt-6 grid grid-cols-2 gap-3 text-sm sm:grid-cols-4">
                    <div><dt class="text-mist/50">Yazar</dt><dd>{{ $series->author ?: '—' }}</dd></div>
                    <div><dt class="text-mist/50">Çizer</dt><dd>{{ $series->artist ?: '—' }}</dd></div>
                    <div><dt class="text-mist/50">Yıl</dt><dd>{{ $series->year ?: '—' }}</dd></div>
                    <div><dt class="text-mist/50">Seviye</dt><dd>{{ $series->content_level->label() }}</dd></div>
                </dl>
                <div class="mt-5 flex flex-wrap gap-2">
                    @foreach ($series->tags as $tag)
                        <a class="btn-ghost !py-1" href="{{ route('tags.show', $tag) }}">{{ $tag->name }}</a>
                    @endforeach
                </div>
                <div class="mt-6">
                    <livewire:series-shelf :series="$series" />
                </div>
            </div>
        </div>
    </section>

    <section class="mx-auto max-w-6xl px-4 pb-16" aria-labelledby="bolumler">
        <h2 id="bolumler" class="font-display text-3xl">Bölümler</h2>
        <ol class="mt-4 divide-y divide-white/10">
            @forelse ($series->chapters as $chapter)
                @php
                    $locked = $chapter->ruh_tasi_fiyat > 0;
                    $early = $chapter->erken_erisim_bitis && $chapter->erken_erisim_bitis->isFuture();
                @endphp
                <li class="flex items-center justify-between gap-3 py-3">
                    <a class="min-w-0 hover:text-magenta" href="{{ route('reader.show', [$series, $chapter->number]) }}">
                        <p class="truncate font-medium">Bölüm {{ $chapter->number }} @if($chapter->title)<span class="text-mist/70">· {{ $chapter->title }}</span>@endif</p>
                        <p class="text-xs text-mist/50">{{ optional($chapter->published_at)->translatedFormat('d M Y') }}</p>
                    </a>
                    <div class="flex shrink-0 items-center gap-2">
                        @if ($chapter->vip_ucretsiz)
                            <span class="badge-vip">VIP</span>
                        @endif
                        @if ($locked)
                            <span class="badge-lock">{{ $chapter->ruh_tasi_fiyat }} taş</span>
                        @endif
                        @if ($early)
                            <span class="badge-new">Erken</span>
                        @endif
                    </div>
                </li>
            @empty
                <li><x-empty-state title="Bölüm yok" body="İlk sayfalar yüklendiğinde liste burada açılır." /></li>
            @endforelse
        </ol>
    </section>
</x-night-layout>
