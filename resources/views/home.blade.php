<x-night-layout>
    <section
        class="relative overflow-hidden"
        x-data="{
            index: 0,
            covers: {{ \Illuminate\Support\Js::from($showcase->map(fn ($series) => $series->cover_path ? \Illuminate\Support\Facades\Storage::disk(config('content.image_disk', 'public'))->url($series->cover_path) : null)->values()) }},
        }"
    >
        <div
            class="cover-art pointer-events-none absolute inset-0 scale-110 bg-wine/50 blur-3xl"
            aria-hidden="true"
            :style="covers[index] ? `background-image:url('${covers[index]}');background-size:cover` : ''"
        ></div>
        <div class="relative mx-auto max-w-6xl px-4 pb-10 pt-8">
            <p class="text-xs uppercase tracking-[0.28em] text-magenta">Gece kütüphanesi</p>
            <h1 class="mt-3 max-w-xl font-display text-4xl leading-tight sm:text-6xl"><span class="sr-only">Ruhscans. </span>Bu gece rafta ne var?</h1>
            <p class="mt-4 max-w-lg text-sm text-mist/75">Tam genişlik vitrin. Izgara ve kenar çubuğu yok; kapaklar koridorda yan yana durur.</p>
        </div>
        @if ($showcase->isEmpty())
            <div class="mx-auto max-w-6xl px-4 pb-10">
                <x-empty-state title="Vitrin boş" body="Yayımlanmış seri henüz yok. Çeviri ekibi ilk rafı kurduğunda burada belirecek." />
            </div>
        @else
            <div class="strip flex gap-4 overflow-x-auto px-4 pb-12" role="list">
                @foreach ($showcase as $series)
                    <div class="w-[78vw] shrink-0 snap-start sm:w-[28rem]" role="listitem" x-intersect.margin.-40%="index = {{ $loop->index }}">
                        <a href="{{ route('series.show', $series) }}" class="glass relative block overflow-hidden rounded-3xl">
                            <div class="cover-art absolute inset-0 bg-gradient-to-br from-wine to-ink opacity-70"></div>
                            <div class="relative grid min-h-72 gap-4 p-6 sm:grid-cols-[9rem_1fr]">
                                <div class="cover-frame relative overflow-hidden rounded-2xl bg-surface">
                                    @if ($series->cover_path)
                                        <img src="{{ \Illuminate\Support\Facades\Storage::disk(config('content.image_disk', 'public'))->url($series->cover_path) }}" alt="" width="320" height="480" loading="lazy" decoding="async" class="cover-art h-full w-full object-cover">
                                    @else
                                        <div class="cover-art h-full w-full bg-gradient-to-br from-wine to-ink"></div>
                                    @endif
                                </div>
                                <div class="self-end">
                                    <p class="text-xs uppercase tracking-widest text-gold">{{ $series->type->label() }}</p>
                                    <h2 class="mt-2 font-display text-3xl">{{ $series->title }}</h2>
                                    <p class="mt-3 line-clamp-3 text-sm text-mist/75">{{ $series->description }}</p>
                                </div>
                            </div>
                        </a>
                    </div>
                @endforeach
            </div>
        @endif
    </section>

    <div class="mx-auto max-w-6xl space-y-12 px-4">
        @auth
            <section aria-labelledby="devam">
                <h2 id="devam" class="font-display text-3xl">Kaldığın yerden devam et</h2>
                @if ($continue->isEmpty())
                    <x-empty-state class="mt-4" title="Henüz iz bırakmadın" body="Bir bölüm açtığında seri burada seni bekler.">
                        <x-slot:action>
                            <a class="btn-magenta" href="{{ route('discover') }}">Keşfe çık</a>
                        </x-slot:action>
                    </x-empty-state>
                @else
                    <div class="strip mt-4 flex gap-4 overflow-x-auto pb-2">
                        @foreach ($continue as $entry)
                            <a class="glass w-72 shrink-0 rounded-2xl p-4" href="{{ $entry->lastChapter ? route('reader.show', [$entry->series, $entry->lastChapter->number]) : route('series.show', $entry->series) }}">
                                <p class="text-xs text-magenta">Bölüm {{ $entry->lastChapter?->number }}</p>
                                <h3 class="mt-2 font-display text-xl">{{ $entry->series->title }}</h3>
                            </a>
                        @endforeach
                    </div>
                @endif
            </section>
        @endauth

        <section aria-labelledby="trend">
            <h2 id="trend" class="font-display text-3xl">Bu gece trend</h2>
            <div class="strip mt-4 flex gap-4 overflow-x-auto pb-4">
                @forelse ($trending as $series)
                    <x-cover-card :series="$series" />
                @empty
                    <x-empty-state title="Trend yok" body="Bölümler geldikçe bu şerit dolacak." />
                @endforelse
            </div>
        </section>

        <section aria-labelledby="yeni">
            <h2 id="yeni" class="font-display text-3xl">Yeni bölümler</h2>
            <ul class="mt-4 divide-y divide-white/10">
                @forelse ($fresh as $chapter)
                    <li class="flex items-center justify-between gap-4 py-3">
                        <a class="min-w-0" href="{{ route('series.show', $chapter->series) }}">
                            <span class="block truncate font-medium">{{ $chapter->series->title }}</span>
                            <span class="text-xs text-mist/60">Bölüm {{ $chapter->number }}</span>
                        </a>
                        <span class="shrink-0 text-xs text-mist/50">{{ optional($chapter->published_at)->diffForHumans() }}</span>
                    </li>
                @empty
                    <li><x-empty-state title="Yeni bölüm yok" body="Bu gece henüz sayfa düşmedi." /></li>
                @endforelse
            </ul>
        </section>

        <section aria-labelledby="etiketler">
            <h2 id="etiketler" class="font-display text-3xl">Etiket bulutu</h2>
            <div class="mt-4 flex flex-wrap gap-2">
                @foreach ($tags as $tag)
                    <a class="btn-ghost" href="{{ route('tags.show', $tag) }}">{{ $tag->name }}</a>
                @endforeach
            </div>
        </section>

        <section class="glass overflow-hidden rounded-3xl bg-gradient-to-r from-wine to-surface p-6 sm:p-10" aria-labelledby="vip">
            <p class="text-xs uppercase tracking-[0.25em] text-gold">VIP</p>
            <h2 id="vip" class="mt-2 font-display text-4xl">Ruh taşıyla erken oku.</h2>
            <p class="mt-3 max-w-xl text-sm text-mist/80">Ödeme geçidi yok. VIP, ruh taşı koduyla açılır; kilitli bölümler altın rozetle durur.</p>
            <div class="mt-6 flex items-center gap-3 text-gold">
                <x-stone class="h-6 w-6" />
                <span class="font-display text-2xl">{{ auth()->user()?->ruh_tasi_bakiye ?? 0 }}</span>
            </div>
        </section>
    </div>
</x-night-layout>
