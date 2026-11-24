<x-night-layout :seo="$seo">
    {{-- Vitrin: mobilde tek kart, swipe + noktalar; tablet/masaüstünde 3 kart --}}
    <section
        class="relative overflow-hidden"
        x-data="{
            index: 0,
            covers: {{ \Illuminate\Support\Js::from($showcase->map(fn ($series) => $series->cover_path ? $series->coverUrl : null)->values()) }},
            go(i) {
                this.index = i;
                const reduceMotion = window.matchMedia('(prefers-reduced-motion: reduce)').matches;
                this.$refs.strip?.children[i]?.scrollIntoView({ behavior: reduceMotion ? 'auto' : 'smooth', inline: 'start', block: 'nearest' });
            },
        }"
    >
        <div
            class="cover-art pointer-events-none absolute inset-0 scale-110 bg-wine/50 blur-3xl"
            aria-hidden="true"
            :style="covers[index] ? `background-image:url('${covers[index]}');background-size:cover` : ''"
        ></div>
        <div class="relative mx-auto max-w-6xl px-4 pb-5 pt-6 sm:pb-6 sm:pt-10">
            <p class="text-xs font-semibold uppercase tracking-[0.28em] text-magenta">Gece kütüphanesi</p>
            <h1 class="mt-2 max-w-xl font-display text-3xl leading-tight sm:text-5xl"><span class="sr-only">Ruhscans. </span>Bu gece rafta ne var?</h1>
            <p class="mt-2.5 max-w-lg text-base text-mist/75">Yeni bölümler, trend seriler ve kaldığın yer — hepsi tek rafta.</p>
        </div>

        @if ($showcase->isEmpty())
            <div class="mx-auto max-w-6xl px-4 pb-10">
                <x-empty-state title="Vitrin boş" body="Yayımlanmış seri henüz yok. Çeviri ekibi ilk rafı kurduğunda burada belirecek." />
            </div>
        @else
            <div
                x-ref="strip"
                class="strip no-scrollbar flex snap-x snap-mandatory gap-4 overflow-x-auto px-4 pb-4 sm:grid sm:grid-cols-2 sm:overflow-visible lg:grid-cols-3"
                role="list"
                @scroll.throttle="index = Math.round($event.target.scrollLeft / ($event.target.clientWidth || 1))"
            >
                @foreach ($showcase as $series)
                    <div class="w-full shrink-0 snap-start sm:w-auto" role="listitem">
                        <a href="{{ route('series.show', $series) }}" class="glass relative block overflow-hidden rounded-3xl">
                            <div class="cover-art absolute inset-0 bg-gradient-to-br from-wine to-ink opacity-70"></div>
                            <div class="relative grid min-h-72 gap-4 p-6 sm:grid-cols-[9rem_1fr]">
                                <div class="cover-frame relative mx-auto w-32 overflow-hidden rounded-2xl bg-surface sm:mx-0 sm:w-full">
                                    @if ($series->cover_path)
                                        <img src="{{ $series->coverPlaceholderUrl }}" alt="" aria-hidden="true" width="20" height="30" class="cover-art pointer-events-none absolute inset-0 h-full w-full scale-110 object-cover blur-xl" onerror="this.style.display='none'">
                                        <img src="{{ $series->coverSmallUrl }}" alt="" width="300" height="450" loading="lazy" decoding="async" class="cover-art h-full w-full object-cover" onerror="this.style.display='none'">
                                        <img src="{{ $series->coverUrl }}" alt="" width="600" height="900" loading="lazy" decoding="async" class="cover-art absolute inset-0 h-full w-full object-cover" onerror="this.style.display='none'">
                                    @else
                                        <div class="cover-art flex h-full w-full items-center justify-center bg-gradient-to-br from-wine via-surface to-ink">
                                            <x-stone class="h-8 w-8 opacity-60" />
                                        </div>
                                    @endif
                                </div>
                                <div class="self-end">
                                    <p class="text-xs font-semibold uppercase tracking-widest text-gold">{{ $series->type->label() }}</p>
                                    <h2 class="mt-2 font-display text-3xl leading-tight">{{ $series->title }}</h2>
                                    <p class="mt-3 line-clamp-3 text-base text-mist/80">{{ $series->description }}</p>
                                </div>
                            </div>
                        </a>
                    </div>
                @endforeach
            </div>
            @if ($showcase->count() > 1)
                <div class="mx-auto mt-1 flex max-w-6xl justify-center gap-1 px-4 sm:hidden" role="tablist" aria-label="Vitrin sayfaları">
                    @foreach ($showcase as $i => $series)
                        <button
                            type="button"
                            class="tap-target inline-flex items-center justify-center"
                            :aria-selected="index === {{ $i }}"
                            aria-label="{{ $i + 1 }}. vitrin kartı"
                            @click="go({{ $i }})"
                        >
                            <span class="showcase-dot" :class="index === {{ $i }} ? 'active' : 'bg-white/25'"></span>
                        </button>
                    @endforeach
                </div>
            @endif
        @endif
    </section>

    <div class="mx-auto max-w-6xl space-y-12 px-4 pb-8 pt-10">
        @auth
            <section aria-labelledby="devam">
                <h2 id="devam" class="font-display text-3xl">Kaldığın yerden devam et</h2>
                @if ($continue->isEmpty())
                    <x-empty-state class="mt-4" title="Henüz iz bırakmadın" body="Bir bölüm açtığında seri burada seni bekler.">
                        <x-slot:action>
                            <a class="btn-magenta tap-target" href="{{ route('discover') }}">Keşfe çık</a>
                        </x-slot:action>
                    </x-empty-state>
                @else
                    <div class="strip no-scrollbar mt-4 flex gap-3.5 overflow-x-auto pb-2">
                        @foreach ($continue as $entry)
                            <a class="glass group flex w-72 shrink-0 items-center gap-3.5 rounded-2xl p-3 transition hover:border-magenta/40 sm:w-80" href="{{ $entry->lastChapter ? route('reader.show', [$entry->series, $entry->lastChapter->number]) : route('series.show', $entry->series) }}">
                                <div class="cover-frame relative w-14 shrink-0 overflow-hidden rounded-xl border border-white/10 bg-surface">
                                    @if ($entry->series->cover_path)
                                        <img src="{{ $entry->series->coverSmallUrl }}" alt="" width="56" height="84" loading="lazy" class="cover-art h-full w-full object-cover">
                                    @else
                                        <div class="cover-art flex h-full w-full items-center justify-center bg-gradient-to-br from-wine via-surface to-ink">
                                            <x-stone class="h-4 w-4 opacity-70" />
                                        </div>
                                    @endif
                                </div>
                                <div class="min-w-0 flex-1">
                                    <p class="text-xs font-semibold uppercase tracking-wider text-magenta">
                                        {{ $entry->lastChapter ? 'Bölüm '.$entry->lastChapter->display_number : 'Kütüphanede' }}
                                    </p>
                                    <h3 class="mt-1 truncate font-display text-lg text-mist group-hover:text-magenta">{{ $entry->series->title }}</h3>
                                    <p class="mt-1 text-xs text-mist/60">Devam et →</p>
                                </div>
                            </a>
                        @endforeach
                    </div>
                @endif
            </section>
        @endauth

        <section aria-labelledby="yeni">
            <div class="flex items-end justify-between gap-4">
                <h2 id="yeni" class="font-display text-3xl">Yeni bölümler</h2>
                <a class="tap-target inline-flex items-center text-base text-magenta" href="{{ route('discover') }}?sira=yeni">Tümü →</a>
            </div>
            @if ($fresh->isEmpty())
                <x-empty-state class="mt-4" title="Yeni bölüm yok" body="Bu gece henüz sayfa düşmedi." />
            @else
                <ul class="mt-4 grid grid-cols-2 gap-4 sm:grid-cols-3 lg:grid-cols-6">
                    @foreach ($fresh as $chapter)
                        <li>
                            <x-cover-card :series="$chapter->series" fluid :subtitle="'Bölüm ' . $chapter->display_number" />
                        </li>
                    @endforeach
                </ul>
            @endif
        </section>

        <section aria-labelledby="trend">
            <h2 id="trend" class="font-display text-3xl">Bu gece trend</h2>
            <div class="strip no-scrollbar mt-4 flex gap-4 overflow-x-auto pb-4">
                @forelse ($trending as $series)
                    <x-cover-card :series="$series" />
                @empty
                    <x-empty-state title="Trend yok" body="Bölümler geldikçe bu şerit dolacak." />
                @endforelse
            </div>
        </section>

        <section aria-labelledby="etiketler">
            <h2 id="etiketler" class="font-display text-3xl">Türler</h2>
            <div class="no-scrollbar mt-4 flex gap-2 overflow-x-auto pb-2 sm:flex-wrap">
                @forelse ($tags as $tag)
                    <a class="btn-ghost tap-target shrink-0" href="{{ route('tags.show', $tag) }}">{{ $tag->name }}</a>
                @empty
                    <p class="text-base text-mist/60">Henüz tür eklenmedi.</p>
                @endforelse
            </div>
        </section>

        <section class="glass overflow-hidden rounded-3xl bg-gradient-to-r from-wine to-surface p-6 sm:p-10" aria-labelledby="vip">
            <p class="text-sm uppercase tracking-[0.25em] text-gold">VIP</p>
            <h2 id="vip" class="mt-2 font-display text-4xl">Ruh taşıyla erken oku.</h2>
            <p class="mt-3 max-w-xl text-base text-mist/80">Ödeme geçidi yok. VIP, ruh taşı koduyla açılır; kilitli bölümler altın rozetle durur.</p>
            <div class="mt-6 flex items-center gap-3 text-gold">
                <x-stone class="h-6 w-6" />
                <span class="font-display text-2xl">{{ auth()->user()?->ruh_tasi_bakiye ?? 0 }}</span>
                @auth
                    <a class="btn-ghost tap-target ml-auto" href="{{ route('wallet.show') }}">Cüzdan</a>
                @else
                    <a class="btn-ghost tap-target ml-auto" href="{{ route('login') }}">Giriş yap</a>
                @endauth
            </div>
        </section>
    </div>
</x-night-layout>
