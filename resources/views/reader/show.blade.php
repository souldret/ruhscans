<x-night-layout :seo="$seo">
    @php
        $viewer = auth()->user();
        $boot = [
            'mode' => $mode,
            'stealth' => $stealth,
            'locked' => $gate['locked'],
            'pagesUrl' => route('reader.pages', [$series, $chapter->number]),
            'progressUrl' => $viewer ? route('reader.progress', [$series, $chapter->number]) : null,
            'modeUrl' => $viewer ? route('reader.mode') : null,
            'startPage' => $progress?->last_page_order ?? 1,
            'nextPagesUrl' => isset($neighbors['next']) ? route('reader.pages', [$series, $neighbors['next']->number]) : null,
            'nextUrl' => isset($neighbors['next']) ? route('reader.show', [$series, $neighbors['next']->number]) : null,
            'previousUrl' => isset($neighbors['previous']) ? route('reader.show', [$series, $neighbors['previous']->number]) : null,
            'seriesUrl' => route('series.show', $series),
            'panicUrl' => $viewer->panik_url ?? app(\App\Services\Ops\SiteOperations::class)->panicUrl(),
            'title' => 'Bölüm '.$chapter->display_number.($chapter->title && mb_strtolower(trim($chapter->title)) !== mb_strtolower('Bölüm '.$chapter->display_number) ? ' · '.$chapter->title : ''),
            'seriesTitle' => $series->title,
        ];
    @endphp
    <div
        class="reader-root mx-auto max-w-3xl"
        x-data="reader({{ \Illuminate\Support\Js::from($boot) }})"
        x-on:keydown.window="onKey($event)"
        x-on:touchstart.passive="touchStart($event)"
        x-on:touchend.passive="touchEnd($event)"
        :class="focus ? 'reader-focus' : ''"
    >
        <header
            class="reader-chrome glass safe-top fixed inset-x-0 top-0 z-30 border-b border-white/5 transition-opacity duration-200"
            :class="chrome ? 'opacity-100' : 'pointer-events-none opacity-0'"
        >
            <div class="mx-auto flex h-14 max-w-3xl items-center justify-between gap-2 px-4 md:h-16">
                <a class="tap-target inline-flex min-w-0 items-center gap-1.5 text-base text-mist/85 hover:text-magenta" href="{{ route('series.show', $series) }}">
                    <span aria-hidden="true">←</span><span class="truncate">{{ $series->title }}</span>
                </a>
                <p class="hidden truncate text-sm text-mist/65 sm:block" x-text="title"></p>
                <button type="button" class="tap-target shrink-0 rounded-full border border-white/15 bg-white/5 px-4 text-sm font-medium text-mist hover:border-magenta/60 hover:text-magenta" x-on:click="toggleFocus()" x-text="focus ? 'Arayüz' : 'Odak'"></button>
            </div>
        </header>

        @if ($gate['locked'])
            <section class="glass mx-4 mt-24 rounded-3xl p-6 text-center sm:p-8" aria-labelledby="kilit">
                <p class="text-sm font-semibold uppercase tracking-[0.25em] text-gold">Kilitli bölüm</p>
                <h1 id="kilit" class="mt-3 font-display text-4xl text-mist">{{ $boot['title'] }}</h1>
                @if ($gate['reason'] === 'early')
                    <p class="mt-4 text-base leading-7 text-mist/80">Bu bölüm erken erişimde. VIP bitene kadar ücretsiz açılır.</p>
                @elseif ($gate['price'] > 0)
                    <p class="mt-4 text-base leading-7 text-mist/80">Bu bölüm <span class="font-semibold text-gold">{{ $gate['price'] }} ruh taşı</span> ister.</p>
                @else
                    <p class="mt-4 text-base leading-7 text-mist/80">Bu bölüm henüz herkese açık değil.</p>
                @endif
                @if ($gate['vip_free'] || $gate['reason'] === 'early')
                    <p class="mt-2 text-base text-gold">VIP ile ücretsiz.</p>
                @elseif ($gate['reason'] === 'price')
                    <p class="mt-2 text-base text-mist/60">VIP seçeneği ruh taşı koduyla açılır.</p>
                @endif
                <div class="mt-6 flex flex-col justify-center gap-3 sm:flex-row">
                    @auth
                        @if ($gate['reason'] === 'price')
                            <form method="POST" action="{{ route('reader.unlock', [$series, $chapter->number]) }}">
                                @csrf
                                <button type="submit" class="btn-magenta h-14 w-full px-6 text-base">{{ $gate['price'] }} ruh taşıyla aç</button>
                            </form>
                        @endif
                        <a class="btn-ghost h-14 justify-center px-6 text-base" href="{{ route('wallet.show') }}">Kod kullan</a>
                    @else
                        <a class="btn-magenta h-14 justify-center px-6 text-base" href="{{ route('login') }}">Giriş yap</a>
                        <a class="btn-ghost h-14 justify-center px-6 text-base" href="{{ route('login') }}">Kod kullan</a>
                    @endauth
                </div>
                @error('unlock')
                    <p class="mt-4 text-base text-[#FF9CC4]">{{ $message }}</p>
                @enderror
                @auth
                    <p class="mt-5 text-base text-mist/60">Bakiyen: {{ auth()->user()->ruh_tasi_bakiye }} ruh taşı. @if (auth()->user()->isVip())<span class="badge-vip">VIP</span>@endif</p>
                @endauth
            </section>
        @else
            <div class="px-0 pb-32 pt-20 sm:px-4">
                <div x-show="loading" class="mx-auto flex min-h-[60dvh] max-w-md flex-col items-center justify-center gap-4 px-6 text-center">
                    <x-stone class="h-8 w-8" />
                    <p class="text-base text-mist/70">Sayfalar hazırlanıyor…</p>
                </div>

                <div x-show="!loading && loadError" x-cloak class="glass mx-4 my-12 rounded-3xl p-6 text-center">
                    <p class="font-display text-2xl text-mist">Sayfalar yüklenemedi</p>
                    <p class="mt-2 text-base text-mist/70">Bağlantı geçici olarak kesilmiş olabilir.</p>
                    <button type="button" @click="reloadPages()" class="btn-magenta tap-target mt-5 px-6 text-base font-semibold">Yeniden dene</button>
                </div>

                <div x-show="!loading && !loadError && mode === 'webtoon'" x-cloak class="space-y-0.5">
                    <template x-for="page in pages" :key="page.order">
                        <figure
                            class="reader-page mx-auto w-full max-w-3xl bg-surface/40"
                            :data-order="page.order"
                            :style="page.width && page.height ? `aspect-ratio: ${page.width} / ${page.height}` : ''"
                            x-intersect.once="seen(page.order)"
                        >
                            <img
                                :src="page.url"
                                :alt="(watermark ? watermark + ' · ' : '') + 'Sayfa ' + page.order"
                                :width="page.width || 800"
                                :height="page.height || 1200"
                                :fetchpriority="page.order === 1 ? 'high' : 'auto'"
                                :loading="page.order <= 2 ? 'eager' : 'lazy'"
                                decoding="async"
                                class="mx-auto block h-auto w-full cursor-pointer select-none"
                                x-on:click="toggleChrome($event)"
                                x-on:error="retry(page, $event)"
                                draggable="false"
                            >
                        </figure>
                    </template>
                </div>

                <div x-show="mode !== 'webtoon'" x-cloak class="relative mx-auto flex min-h-[70dvh] items-center justify-center">
                    <template x-if="current">
                        <div class="relative w-full">
                            <img
                                :src="current.url"
                                :alt="'Sayfa ' + current.order"
                                class="mx-auto max-h-[78dvh] w-auto max-w-full select-none"
                                x-on:click="toggleChrome($event)"
                                x-on:error="retry(current, $event)"
                                draggable="false"
                            >
                            <button type="button" class="absolute inset-y-0 left-0 w-[32%]" @click="zone(-1)" aria-label="Önceki sayfa"></button>
                            <button type="button" class="absolute inset-y-0 left-[32%] w-[36%]" @click="chrome = !chrome" aria-label="Araç çubuğunu aç veya kapat"></button>
                            <button type="button" class="absolute inset-y-0 right-0 w-[32%]" @click="zone(1)" aria-label="Sonraki sayfa"></button>
                        </div>
                    </template>
                </div>

                <p class="reader-chrome px-4 py-5 text-center text-base text-mist/70" x-show="mode !== 'webtoon'" x-text="label()"></p>

                <section class="reader-chrome mx-4 mb-10 mt-6" x-show="atEnd" x-cloak>
                    <a class="glass block rounded-3xl p-6" :href="nextUrl || seriesUrl">
                        <p class="text-sm font-semibold uppercase tracking-[0.2em] text-magenta" x-text="nextUrl ? 'Sonraki bölüm' : 'Seriye dön'"></p>
                        <h2 class="mt-2 font-display text-3xl text-mist" x-text="nextUrl ? 'Devam et' : seriesTitle"></h2>
                        <p class="mt-2 text-base text-mist/60" x-show="nextReady">Sonraki bölüm önyüklendi.</p>
                    </a>
                </section>
            </div>

            <div
                class="reader-chrome glass safe-bottom fixed inset-x-0 bottom-0 z-30 border-t border-white/10 transition-opacity duration-200"
                :class="chrome ? 'opacity-100' : 'pointer-events-none opacity-0'"
            >
                <div class="mx-auto flex max-w-3xl items-center gap-2 px-3 py-2">
                    <button type="button" class="tap-target shrink-0 rounded-full border border-white/15 bg-white/5 px-3 text-base font-medium text-mist hover:border-magenta/60 disabled:opacity-35" :disabled="!previousUrl" @click="goChapter(previousUrl)" aria-label="Önceki bölüm">←</button>
                    <div class="min-w-0 flex-1">
                        <div class="flex items-center justify-between gap-2 text-sm text-mist/80">
                            <span class="truncate" x-text="label()"></span>
                            <span x-text="progressPercent + '%'"></span>
                        </div>
                        <div class="mt-1 h-1.5 overflow-hidden rounded-full bg-white/15" role="progressbar" :aria-valuenow="progressPercent" aria-valuemin="0" aria-valuemax="100">
                            <div class="h-full bg-magenta transition-[width] duration-200" :style="`width: ${progressPercent}%`"></div>
                        </div>
                    </div>
                    <button type="button" class="tap-target shrink-0 rounded-full border border-white/15 bg-white/5 px-4 text-sm font-medium text-mist hover:border-magenta/60" @click="settingsOpen = !settingsOpen" aria-label="Okuma ayarları">Ayarlar</button>
                    <button type="button" class="tap-target shrink-0 rounded-full bg-magenta px-4 text-base font-semibold text-ink disabled:opacity-35" :disabled="!nextUrl" @click="goChapter(nextUrl)" aria-label="Sonraki bölüm">→</button>
                </div>

                <div x-cloak x-show="settingsOpen" class="safe-bottom border-t border-white/10 p-4" role="dialog" aria-label="Okuma ayarları">
                    <div class="mx-auto max-w-3xl space-y-4">
                        <div>
                            <label class="mb-2 block text-base font-medium text-mist" for="okuma-modu">Okuma modu</label>
                            <select id="okuma-modu" class="h-14 w-full rounded-2xl border border-white/10 bg-ink px-4 text-base text-mist focus:border-magenta focus:ring-magenta" x-model="mode" x-on:change="saveMode()">
                                <option value="webtoon">Dikey kaydırma</option>
                                <option value="ltr">Sayfa · soldan sağa</option>
                                <option value="rtl">Sayfa · sağdan sola</option>
                            </select>
                        </div>
                        <p class="text-base text-mist/60">Ekrana dokunarak araç çubuğunu açıp kapatabilirsin. Sayfa modunda sol ve sağ bölgeler sayfa çevirir.</p>
                        <button type="button" class="tap-target inline-flex w-full items-center justify-center rounded-full border border-white/10 text-base text-mist/70 hover:border-magenta/60 hover:text-magenta" @click="panic()">
                            Panik · nötr sayfaya git
                        </button>
                    </div>
                </div>
            </div>
        @endif
    </div>
</x-night-layout>
