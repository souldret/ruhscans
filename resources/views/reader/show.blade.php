<x-night-layout :seo="$seo">
    @php
        $boot = [
            'mode' => $mode,
            'stealth' => $stealth,
            'locked' => $gate['locked'],
            'pagesUrl' => route('reader.pages', [$series, $chapter->number]),
            'progressUrl' => auth()->check() ? route('reader.progress', [$series, $chapter->number]) : null,
            'modeUrl' => auth()->check() ? route('reader.mode') : null,
            'startPage' => $progress?->last_page_order ?? 1,
            'nextPagesUrl' => isset($neighbors['next']) ? route('reader.pages', [$series, $neighbors['next']->number]) : null,
            'nextUrl' => isset($neighbors['next']) ? route('reader.show', [$series, $neighbors['next']->number]) : null,
            'previousUrl' => isset($neighbors['previous']) ? route('reader.show', [$series, $neighbors['previous']->number]) : null,
            'seriesUrl' => route('series.show', $series),
            'title' => 'Bölüm '.$chapter->number.($chapter->title ? ' · '.$chapter->title : ''),
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
        <header class="reader-chrome sticky top-16 z-20 flex items-center justify-between gap-3 px-4 py-3">
            <a class="text-sm text-mist/70 hover:text-magenta" href="{{ route('series.show', $series) }}">{{ $series->title }}</a>
            <p class="truncate text-sm" x-text="title"></p>
            <div class="flex items-center gap-2">
                <label class="sr-only" for="okuma-modu">Okuma modu</label>
                <select id="okuma-modu" class="rounded-full border border-white/10 bg-ink px-3 py-1 text-xs" x-model="mode" x-on:change="saveMode()">
                    <option value="webtoon">Dikey</option>
                    <option value="ltr">Sayfa LTR</option>
                    <option value="rtl">Sayfa RTL</option>
                </select>
                <button type="button" class="btn-ghost !px-3 !py-1.5 text-xs" x-on:click="focus = !focus" x-text="focus ? 'Arayüz' : 'Odak'"></button>
                <button type="button" class="btn-ghost !px-3 !py-1.5 text-xs" x-on:click="panic()" aria-label="Panik tuşu">Panik</button>
            </div>
        </header>

        @if ($gate['locked'])
            <section class="glass mx-4 mt-8 rounded-3xl p-8 text-center" aria-labelledby="kilit">
                <p class="text-xs uppercase tracking-[0.25em] text-gold">Kilitli bölüm</p>
                <h1 id="kilit" class="mt-3 font-display text-4xl">{{ $boot['title'] }}</h1>
                @if ($gate['reason'] === 'early')
                    <p class="mt-4 text-sm text-mist/75">Bu bölüm erken erişimde. VIP bitene kadar ücretsiz açılır.</p>
                @elseif ($gate['price'] > 0)
                    <p class="mt-4 text-sm text-mist/75">Bu bölüm <span class="text-gold">{{ $gate['price'] }} ruh taşı</span> ister.</p>
                @else
                    <p class="mt-4 text-sm text-mist/75">Bu bölüm henüz herkese açık değil.</p>
                @endif
                @if ($gate['vip_free'] || $gate['reason'] === 'early')
 <p class="mt-2 text-sm">VIP ile ücretsiz.</p>
 @elseif ($gate['reason'] === 'price')
 <p class="mt-2 text-sm text-mist/60">VIP seçeneği ruh taşı koduyla açılır.</p>
 @endif
                <div class="mt-6 flex flex-wrap justify-center gap-3">
 @auth
 @if ($gate['reason'] === 'price')
 <form method="POST" action="{{ route('reader.unlock', [$series, $chapter->number]) }}">
 @csrf
 <button type="submit" class="btn-magenta">{{ $gate['price'] }} ruh taşıyla aç</button>
 </form>
 @endif
 <a class="btn-ghost" href="{{ route('wallet.show') }}">Kod kullan</a>
 @else
 <a class="btn-magenta" href="{{ route('login') }}">Giriş yap</a>
 <a class="btn-ghost" href="{{ route('login') }}">Kod kullan</a>
 @endauth
 </div>
 @error('unlock')
 <p class="mt-4 text-sm text-magenta">{{ $message }}</p>
 @enderror
 @auth
 <p class="mt-4 text-xs text-mist/50">Bakiyen: {{ auth()->user()->ruh_tasi_bakiye }} ruh taşı. @if (auth()->user()->isVip())<span class="badge-vip">VIP</span>@endif</p>
 @endauth
            </section>
        @else
            <div x-show="mode === 'webtoon'" class="space-y-1 px-0 sm:px-4">
                <template x-for="page in pages" :key="page.order">
                    <figure class="reader-page" :data-order="page.order" x-intersect.once="seen(page.order)">
                        <img
                        :src="page.url"
                        :alt="(watermark ? watermark + ' · ' : '') + 'Sayfa ' + page.order"
                            :width="page.width || 800"
                            :height="page.height || 1200"
                            loading="lazy"
                            decoding="async"
                            class="mx-auto w-full max-w-3xl"
                            x-on:error="retry(page, $event)"
                        >
                    </figure>
                </template>
            </div>

            <div x-show="mode !== 'webtoon'" x-cloak class="flex min-h-[70vh] items-center justify-center px-4">
                <template x-if="current">
                    <img
                        :src="current.url"
                        :alt="'Sayfa ' + current.order"
                        class="max-h-[85vh] w-auto max-w-full"
                        x-on:error="retry(current, $event)"
                    >
                </template>
            </div>

            <p class="reader-chrome px-4 py-6 text-center text-xs text-mist/60" x-show="mode !== 'webtoon'" x-text="label()"></p>

            <section class="reader-chrome mx-4 mb-10 mt-6" x-show="atEnd" x-cloak>
                <a class="glass block rounded-3xl p-6" :href="nextUrl || seriesUrl">
                    <p class="text-xs uppercase tracking-[0.2em] text-magenta" x-text="nextUrl ? 'Sonraki bölüm' : 'Seriye dön'"></p>
                    <h2 class="mt-2 font-display text-3xl" x-text="nextUrl ? 'Devam et' : seriesTitle"></h2>
                </a>
            </section>
        @endif
    </div>
</x-night-layout>
