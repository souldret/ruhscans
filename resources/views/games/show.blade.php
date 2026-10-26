@extends('layouts.site')

@section('title', $game->title.' — '.config('app.name'))
@section('description', $game->title.' Türkçe yama. '.$game->developer)
@section('image', $game->cover_path ? asset('storage/'.$game->cover_path) : asset('images/poster.svg'))

@section('meta')
    <script type="application/ld+json">
        {!! json_encode([
            '@context' => 'https://schema.org',
            '@type' => 'VideoGame',
            'name' => $game->title,
            'description' => $game->description,
            'author' => ['@type' => 'Organization', 'name' => $game->developer],
            'gamePlatform' => $game->files->pluck('platform')->map->label()->unique()->values(),
            'inLanguage' => 'tr',
            'image' => $game->cover_path ? asset('storage/'.$game->cover_path) : asset('images/poster.svg'),
        ], JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES | JSON_HEX_TAG | JSON_HEX_AMP) !!}
    </script>
@endsection

@section('content')
    @php
        $cover = $game->cover_path ? asset('storage/'.$game->cover_path) : asset('images/poster.svg');
        $shots = collect($game->screenshots ?: [])->map(fn ($path) => asset('storage/'.$path));
        $totalReviews = $game->reviews->count();
        $positive = $game->reviews->where('positive', true)->count();
        $negative = $totalReviews - $positive;
        $positivePercent = $totalReviews > 0 ? (int) round(($positive / $totalReviews) * 100) : null;

        $pc = $game->files->first(fn ($file) => $file->platform->value !== 'android');
        $apk = $game->files->first(fn ($file) => $file->platform->value === 'android');
        $pcMb = $pc ? (int) round($pc->size_bytes / 1048576) : (int) $game->size_mb;
        $apkMb = $apk ? (int) round($apk->size_bytes / 1048576) : (int) $game->apk_size_mb;
        $viewer = auth()->user();

        $rows = [
            ['Geliştirici', $game->developer ?: '—'],
            ['Versiyon', $game->game_version ? 'v'.$game->game_version : '—'],
            ['İncelemeler', $positive.' olumlu · '.$negative.' olumsuz'],
            ['Çıkış tarihi', $game->released_on?->timezone(config('app.display_timezone'))->translatedFormat('d.m.Y') ?: '—'],
            ['Son güncelleme', $game->updated_at?->timezone(config('app.display_timezone'))->translatedFormat('d.m.Y') ?: '—'],
            ['Yaklaşık oynanış', $game->playtime_hours ? $game->playtime_hours.' saat' : '—'],
            ['Boyut', trim(($pcMb ? number_format($pcMb / 1024, 2, ',', '.').' GB PC' : '').($apkMb ? ' · '.$apkMb.' MB APK' : '')) ?: '—'],
        ];
    @endphp

    {{-- Breadcrumb --}}
    <nav aria-label="Gezinti" class="mb-6 flex items-center gap-2 text-sm text-ink-muted">
        <a href="{{ route('home') }}" class="transition hover:text-ink-text">Ana sayfa</a>
        <span class="text-ink-faint">/</span>
        <a href="{{ route('games.index') }}" class="transition hover:text-ink-text">← Oyunlar</a>
        <span class="text-ink-faint">/</span>
        <span class="truncate text-ink-text" aria-current="page">{{ $game->title }}</span>
    </nav>

    {{-- Ana Başlık ve İndirme Bölümü --}}
    <div class="grid items-start gap-8 lg:grid-cols-[minmax(0,1fr)_22rem]">
        <div class="card overflow-hidden p-6 sm:p-8">
            <div class="flex flex-col gap-6 sm:flex-row sm:items-start sm:gap-8">
                <div class="relative mx-auto w-40 shrink-0 overflow-hidden rounded-2xl bg-ink-raised shadow-xl ring-1 ring-white/10 sm:mx-0 sm:w-48" style="aspect-ratio:3/4">
                    <img src="{{ $cover }}" alt="{{ $game->title }} afişi" width="200" height="266" class="h-full w-full object-cover {{ $adult ? '' : 'blur-lg' }}">
                </div>
                <div class="min-w-0 flex-1 py-1">
                    <div class="flex flex-wrap items-center gap-2">
                        <x-badge tone="patch">Türkçe Yama</x-badge>
                        @if (! $game->isFree())
 <x-badge tone="vip">{{ $game->effectivePrice() }} coin</x-badge>
                        @else
                            <x-badge tone="free">Ücretsiz</x-badge>
                        @endif
                        @if ($game->translation_percent)
                            <x-badge tone="muted">%{{ (int) $game->translation_percent }} çevrildi</x-badge>
                        @endif
                    </div>
                    <h1 class="mt-4 break-words font-display text-3xl font-bold leading-tight tracking-tight sm:text-4xl">{{ $game->title }}</h1>
                    @if ($game->original_title)
                        <p class="mt-2 text-sm font-medium text-ink-muted">{{ $game->original_title }}</p>
                    @endif
                    @if ($game->developer)
                        <p class="mt-1 text-xs text-ink-faint">Geliştirici: <span class="text-ink-text">{{ $game->developer }}</span></p>
                    @endif

                    @if ($totalReviews > 0)
                        <div class="mt-4 flex items-center gap-3 text-xs">
                            <span class="font-semibold {{ $positivePercent >= 70 ? 'text-ok' : ($positivePercent >= 40 ? 'text-brand-gold' : 'text-danger') }}">
                                %{{ $positivePercent }} Olumlu
                            </span>
                            <span class="text-ink-faint">({{ $totalReviews }} değerlendirme)</span>
                        </div>
                    @endif
                </div>
            </div>

            {{-- Detay Tablosu --}}
            <div class="mt-8 divide-y divide-ink-line rounded-2xl border border-ink-line bg-ink-deep/40 text-sm">
                @foreach ($rows as [$label, $value])
                    <div class="flex flex-col gap-1 px-5 py-3.5 sm:flex-row sm:items-center sm:gap-4">
                        <span class="w-40 shrink-0 text-xs font-medium uppercase tracking-wider text-ink-muted sm:text-sm sm:normal-case sm:tracking-normal">{{ $label }}</span>
                        <span class="text-ink-text">{{ $value }}</span>
                    </div>
                @endforeach
                <div class="flex flex-col gap-2 px-5 py-3.5 sm:flex-row sm:items-center sm:gap-4">
                    <span class="w-40 shrink-0 text-xs font-medium uppercase tracking-wider text-ink-muted sm:text-sm sm:normal-case sm:tracking-normal">Etiketler</span>
                    <div class="flex flex-wrap gap-1.5">
                        @forelse ($game->tags as $tag)
                            <a href="{{ route('games.index', ['etiket' => [$tag->slug]]) }}" class="rounded-full border border-ink-line bg-white/[0.03] px-3 py-1 text-xs text-ink-muted transition hover:border-ink-line-strong hover:text-ink-text">{{ $tag->name }}</a>
                        @empty
                            <span class="text-ink-muted">—</span>
                        @endforelse
                    </div>
                </div>
            </div>
        </div>

        {{-- Sağ Taraf: İndirme Paneli --}}
        <x-download-panel :game="$game" />
    </div>

    {{-- Açıklama Bölümü --}}
    <section class="card mt-10 p-6 sm:p-8">
        <h2 class="font-display text-2xl font-bold tracking-tight">Açıklama</h2>
        <div class="mt-4 max-w-none text-sm leading-7 text-ink-muted sm:text-base">
            <p class="whitespace-pre-line">{{ $game->description }}</p>
        </div>
    </section>

    {{-- Ekran Görüntüleri Galerisi --}}
    @if ($shots->isNotEmpty())
        <section class="card mt-10 p-6 sm:p-8" x-data="lightbox" data-shots="{{ e(json_encode($shots->values())) }}">
            <div class="flex items-center justify-between">
                <h2 class="font-display text-2xl font-bold tracking-tight">Ekran görüntüleri</h2>
                <span class="text-xs text-ink-faint">{{ $shots->count() }} görsel</span>
            </div>
            <div class="no-scrollbar mt-5 flex gap-4 overflow-x-auto pb-2">
                @foreach ($shots as $shot)
                    <button type="button" @click="show({{ $loop->index }})" class="group relative shrink-0 overflow-hidden rounded-2xl border border-ink-line transition hover:border-brand-pink/50">
                        <img src="{{ $shot }}" alt="{{ $game->title }} ekran görüntüsü" width="320" height="180" loading="lazy" class="h-36 w-64 object-cover transition duration-300 group-hover:scale-105 sm:h-44 sm:w-80 {{ $adult ? '' : 'blur-md' }}" style="aspect-ratio:16/9">
                        <div class="pointer-events-none absolute inset-0 bg-black/20 opacity-0 transition-opacity group-hover:opacity-100"></div>
                        <span class="pointer-events-none absolute bottom-2 right-2 rounded-full bg-black/60 p-1.5 text-white backdrop-blur">
                            <svg viewBox="0 0 20 20" class="h-3.5 w-3.5" fill="none" stroke="currentColor" stroke-width="2" aria-hidden="true"><path d="M4 8V4h4m4 0h4v4m0 4v4h-4m-4 0H4v-4" stroke-linecap="round" stroke-linejoin="round"/></svg>
                        </span>
                    </button>
                @endforeach
            </div>

        <template x-teleport="body">
            <div x-show="open" x-cloak @click.self="close()" @keydown.escape.window="close()" @keydown.right.window="next()" @keydown.left.window="previous()"
                 x-transition:enter="transition duration-200 ease-out" x-transition:enter-start="opacity-0" x-transition:enter-end="opacity-100"
                 x-transition:leave="transition duration-150 ease-in" x-transition:leave-start="opacity-100" x-transition:leave-end="opacity-0"
                 class="fixed inset-0 z-[90] grid place-items-center bg-black/90 p-4 backdrop-blur-md">
                <div class="relative max-h-[90vh] max-w-5xl">
            <button type="button" @click="previous()" x-show="shots.length > 1" class="absolute left-3 top-1/2 grid h-10 w-10 -translate-y-1/2 place-items-center rounded-full bg-ink-surface text-2xl text-ink-text shadow-lg ring-1 ring-white/20" aria-label="Önceki">‹</button>
                    <img :src="src" alt="" class="max-h-[85vh] w-auto rounded-2xl shadow-2xl ring-1 ring-white/10">
            <button type="button" @click="next()" x-show="shots.length > 1" class="absolute right-3 top-1/2 grid h-10 w-10 -translate-y-1/2 place-items-center rounded-full bg-ink-surface text-2xl text-ink-text shadow-lg ring-1 ring-white/20" aria-label="Sonraki">›</button>
                    <button type="button" @click="close()" class="absolute -right-3 -top-3 grid h-9 w-9 place-items-center rounded-full bg-ink-surface text-ink-text shadow-lg ring-1 ring-white/20 transition hover:bg-white hover:text-ink" aria-label="Kapat">
                        <svg viewBox="0 0 20 20" class="h-5 w-5" fill="none" stroke="currentColor" stroke-width="2"><path d="M5 5l10 10M15 5L5 15" stroke-linecap="round"/></svg>
                    </button>
                </div>
            </div>
        </template>
        </section>
    @endif

    {{-- Yorumlar Bölümü --}}
    <section class="card mt-10 p-6 sm:p-8">
        <div class="flex flex-wrap items-center justify-between gap-4 border-b border-ink-line pb-6">
            <div>
                <h2 class="font-display text-2xl font-bold tracking-tight">Yorumlar</h2>
                <p class="mt-1 text-sm text-ink-muted">{{ $positive }} olumlu, {{ $negative }} olumsuz.</p>
            </div>
            @if ($totalReviews > 0)
                <div class="flex items-center gap-2 text-xs">
                    <span class="rounded-full bg-ok/10 px-3 py-1 font-semibold text-ok">{{ $positive }} Olumlu</span>
                    <span class="rounded-full bg-danger/10 px-3 py-1 font-semibold text-danger">{{ $negative }} Olumsuz</span>
                </div>
            @endif
        </div>

        @if (session('review'))
            <p class="alert-ok mt-6" role="status">{{ session('review') }}</p>
        @endif

        @auth
            <form method="POST" action="{{ route('games.reviews.store', $game) }}" class="mt-6 rounded-2xl border border-ink-line bg-ink-deep/40 p-5 space-y-4">
                @csrf
                <p class="text-sm font-semibold text-ink-text">Deneyimini paylaş</p>
                <fieldset class="flex flex-wrap gap-4 text-sm">
                    <legend class="sr-only">Değerlendirme</legend>
                    <label class="inline-flex cursor-pointer items-center gap-2">
                        <input type="radio" name="positive" value="1" @checked(old('positive', '1') == '1') class="text-ok focus:ring-ok">
                        <span class="text-ink-text">Olumlu</span>
                    </label>
                    <label class="inline-flex cursor-pointer items-center gap-2">
                        <input type="radio" name="positive" value="0" @checked(old('positive') === '0') class="text-danger focus:ring-danger">
                        <span class="text-ink-text">Olumsuz</span>
                    </label>
 </fieldset>
                @error('positive')
 <p class="field-error">{{ $message }}</p>
 @enderror
                <div>
                    <label class="field-label" for="body">Yorumun</label>
                    <textarea id="body" name="body" rows="4" required minlength="8" maxlength="1000" placeholder="Oyun ve yama hakkındaki düşüncelerini yaz..." class="field">{{ old('body') }}</textarea>
 <p class="mt-1.5 text-xs text-ink-faint">En az 8, en fazla 1000 karakter.</p>
                    @error('body')
                        <p class="field-error">{{ $message }}</p>
                    @enderror
                </div>
                <x-button type="submit">Yorumu kaydet</x-button>
            </form>
        @else
            <div class="mt-6 rounded-2xl border border-ink-line bg-white/[0.02] p-5 text-sm text-ink-muted">
                Yorum yazmak için <a href="{{ route('login') }}" class="link">giriş yap</a>.
            </div>
        @endauth

        <ul class="mt-8 space-y-4">
            @forelse ($game->reviews as $review)
                <li class="rounded-2xl border border-ink-line bg-ink-deep/30 p-5 transition hover:border-ink-line-strong">
                    <div class="flex items-center justify-between gap-3 text-sm">
                        <div class="flex items-center gap-2.5">
                            <span class="grid h-8 w-8 place-items-center rounded-full bg-accent text-xs font-bold text-white">
                                {{ mb_strtoupper(mb_substr($review->user?->name ?? 'Ü', 0, 1)) }}
                            </span>
                            <div>
                                <span class="font-semibold text-ink-text">{{ $review->user?->name ?? 'Üye' }}</span>
                                <span class="block text-[11px] text-ink-faint">{{ $review->created_at?->timezone(config('app.display_timezone'))->translatedFormat('d.m.Y') }}</span>
                            </div>
                        </div>
                        <span class="inline-flex items-center gap-1 rounded-full px-2.5 py-1 text-xs font-semibold {{ $review->positive ? 'bg-ok/15 text-ok' : 'bg-danger/15 text-danger' }}">
                            {{ $review->positive ? 'Olumlu' : 'Olumsuz' }}
                        </span>
                    </div>
                    <p class="mt-3 whitespace-pre-line text-sm leading-6 text-ink-muted">{{ $review->body }}</p>
                    @if ($viewer instanceof \App\Models\User && ($viewer->isAdmin() || $viewer->is($review->user)))
                        <div class="mt-3 flex justify-end">
                            <form method="POST" action="{{ route('games.reviews.destroy', $review) }}">
                                @csrf
                                @method('delete')
                                <button type="submit" class="text-xs text-danger opacity-70 transition hover:opacity-100 hover:underline">Sil</button>
                            </form>
                        </div>
                    @endif
                </li>
            @empty
                <li class="rounded-2xl border border-ink-line bg-white/[0.02] px-5 py-8 text-center text-sm text-ink-muted">
                    Henüz yorum yok. İlk değerlendirmeyi sen yap!
                </li>
            @endforelse
        </ul>
    </section>

    {{-- Benzer Oyunlar --}}
    <x-section-row title="Benzer oyunlar" eyebrow="Bunu sevenler bunları da oynadı" :games="$related" />
@endsection
