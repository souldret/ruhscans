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
        $shots = collect($game->screenshots ?: [])->map(function ($path) {
 $url = asset('storage/'.$path);
 $kind = preg_match('/\.(mp4|m4v|webm|mov|ogg|ogv)$/i', $path) ? 'video' : 'image';

 return ['url' => $url, 'kind' => $kind];
 });
        $totalReviews = $game->reviews->count();
        $positive = $game->reviews->where('positive', true)->count();
        $negative = $totalReviews - $positive;
        $positivePercent = $totalReviews > 0 ? (int) round(($positive / $totalReviews) * 100) : null;

 $pc = $game->files->first(fn ($file) => $file->platform->value !== 'android');
 $apk = $game->files->first(fn ($file) => $file->platform->value === 'android');
 $pcMb = $pc && $pc->size_bytes > 0 ? (int) round($pc->size_bytes / 1048576) : (int) $game->size_mb;
 $apkMb = $apk && $apk->size_bytes > 0 ? (int) round($apk->size_bytes / 1048576) : (int) $game->apk_size_mb;
 $size = function (int $mb, string $suffix): ?string {
 if ($mb <= 0) {
 return null;
 }

 return $mb >= 1024
 ? number_format($mb / 1024, 2, ',', '.').' GB '.$suffix
 : number_format($mb, 0, ',', '.').' MB '.$suffix;
 };
 $sizeLabel = collect([$size($pcMb, 'PC'), $size($apkMb, 'APK')])->filter()->implode(' · ') ?: '—';
 $viewer = auth()->user();
 $favorited = $viewer instanceof \App\Models\User && $game->favoritedBy()->where('user_id', $viewer->id)->exists();


        $rows = [
            ['Geliştirici', $game->developer ?: '—'],
            ['Versiyon', $game->game_version ? 'v'.$game->game_version : '—'],
            ['İncelemeler', $positive.' olumlu · '.$negative.' olumsuz'],
            ['Çıkış tarihi', $game->released_on?->timezone(config('app.display_timezone'))->translatedFormat('d.m.Y') ?: '—'],
            ['Son güncelleme', $game->updated_at?->timezone(config('app.display_timezone'))->translatedFormat('d.m.Y') ?: '—'],
            ['Yaklaşık oynanış', $game->playtime_hours ? $game->playtime_hours.' saat' : '—'],
 ['Boyut', $sizeLabel],
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
 <x-cover :game="$game" :lazy="false" width="200" height="266" sizes="(min-width: 640px) 192px, 160px" class="{{ $adult ? '' : 'blur-lg' }}" />
 @unless ($adult)
 <span class="pointer-events-none absolute inset-x-2 bottom-2 rounded-full bg-black/70 px-2 py-1 text-center text-[10px] font-semibold uppercase tracking-wide text-white">18+</span>
 @endunless
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

                    @auth
                        @if ($game->is_published)
                            <form method="POST" action="{{ route('games.favorite', $game) }}" class="mt-4">
                                @csrf
                                <button type="submit" class="inline-flex items-center gap-2 rounded-full border px-3 py-1.5 text-xs font-semibold transition {{ $favorited ? 'border-brand-pink/50 bg-brand-pink/15 text-brand-pink' : 'border-ink-line text-ink-muted hover:text-ink-text' }}" aria-pressed="{{ $favorited ? 'true' : 'false' }}">
                                    <svg viewBox="0 0 24 24" class="h-4 w-4" fill="{{ $favorited ? 'currentColor' : 'none' }}" stroke="currentColor" stroke-width="1.8" aria-hidden="true"><path d="M12 20s-7-4.4-7-9a4 4 0 0 1 7-2 4 4 0 0 1 7 2c0 4.6-7 9-7 9Z" stroke-linejoin="round"/></svg>
                                    {{ $favorited ? 'Favorilerde' : 'Favorilere ekle' }}
                                </button>
                            </form>
                            @if (session('favorite'))
                                <p class="mt-2 text-xs text-ink-muted" role="status">{{ session('favorite') }}</p>
                            @endif
                        @endif
                    @endauth

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
 <section class="card mt-10 p-6 sm:p-8" id="shot-gallery" data-shots='@json($shots->values())'>
 <div class="flex items-center justify-between">
 <h2 class="font-display text-2xl font-bold tracking-tight">Ekran görüntüleri</h2>
 <span class="text-xs text-ink-faint">{{ $shots->count() }} medya</span>
 </div>
 <div class="mt-5 flex gap-4 overflow-x-auto pb-2 [scrollbar-width:none] [&::-webkit-scrollbar]:hidden">
                @foreach ($shots as $shot)
                    <button type="button" data-shot="{{ $loop->index }}" @if ($game->blur_screenshots && $shot['kind'] !== 'video') data-censored="1" @endif class="group relative z-10 shrink-0 cursor-pointer overflow-hidden rounded-2xl border border-ink-line transition hover:border-brand-pink/50">
                    @if ($shot['kind'] === 'video')
                        <video src="{{ $shot['url'] }}#t=0.1" muted playsinline preload="metadata" data-shot-media class="pointer-events-none h-36 w-64 bg-black object-cover sm:h-44 sm:w-80" style="aspect-ratio:16/9"></video>
                        <span class="pointer-events-none absolute left-2 top-2 rounded-full bg-black/70 px-2 py-0.5 text-[10px] font-semibold uppercase tracking-wide text-white">Video</span>
                    @else
                        <img src="{{ $shot['url'] }}" alt="{{ $game->title }} ekran görüntüsü" width="320" height="180" loading="lazy" data-shot-media class="pointer-events-none h-36 w-64 object-cover transition duration-300 group-hover:scale-105 sm:h-44 sm:w-80 {{ $game->blur_screenshots ? 'scale-110 blur-xl' : '' }}" style="aspect-ratio:16/9">
                    @endif
                    <div class="pointer-events-none absolute inset-0 bg-black/20 opacity-0 transition-opacity group-hover:opacity-100"></div>
                    @if ($game->blur_screenshots && $shot['kind'] !== 'video')
                        <span data-reveal-shot role="button" tabindex="0" class="absolute inset-0 grid place-items-center" aria-label="Görseli göster">
                            <span class="grid h-12 w-12 place-items-center rounded-full bg-black/65 text-white shadow-lg ring-1 ring-white/30 backdrop-blur">
                                <svg viewBox="0 0 24 24" class="h-6 w-6" fill="none" stroke="currentColor" stroke-width="1.8" aria-hidden="true"><path d="M2.5 12S6 6.5 12 6.5 21.5 12 21.5 12 17.9 17.5 12 17.5 2.5 12 2.5 12Z" stroke-linejoin="round"/><circle cx="12" cy="12" r="2.6"/></svg>
                            </span>
                        </span>
                    @endif
                    <span data-shot-expand class="pointer-events-none absolute bottom-2 right-2 rounded-full bg-black/60 p-1.5 text-white backdrop-blur {{ $game->blur_screenshots && $shot['kind'] !== 'video' ? 'hidden' : '' }}">
                        <svg viewBox="0 0 20 20" class="h-3.5 w-3.5" fill="none" stroke="currentColor" stroke-width="2" aria-hidden="true"><path d="M4 8V4h4m4 0h4v4m0 4v4h-4m-4 0H4v-4" stroke-linecap="round" stroke-linejoin="round"/></svg>
                    </span>
                </button>
                @endforeach
            </div>
 </section>
 @endif

 <div id="shot-viewer" hidden class="fixed inset-0 z-[90] hidden place-items-center bg-black/90 p-4 backdrop-blur-md">
 <div class="relative max-h-[90vh] max-w-5xl">
 <button type="button" data-shot-prev @if ($shots->count() < 2) hidden @endif class="absolute left-3 top-1/2 z-10 grid h-10 w-10 -translate-y-1/2 place-items-center rounded-full bg-ink-surface text-2xl text-ink-text shadow-lg ring-1 ring-white/20" aria-label="Önceki">‹</button>
 <img data-shot-full alt="" hidden class="max-h-[85vh] w-auto rounded-2xl shadow-2xl ring-1 ring-white/10">
 <video data-shot-video hidden controls playsinline class="max-h-[85vh] max-w-full rounded-2xl bg-black shadow-2xl ring-1 ring-white/10"></video>
 <button type="button" data-shot-next @if ($shots->count() < 2) hidden @endif class="absolute right-3 top-1/2 z-10 grid h-10 w-10 -translate-y-1/2 place-items-center rounded-full bg-ink-surface text-2xl text-ink-text shadow-lg ring-1 ring-white/20" aria-label="Sonraki">›</button>
 <button type="button" data-shot-close class="absolute -right-3 -top-3 z-10 grid h-9 w-9 place-items-center rounded-full bg-ink-surface text-ink-text shadow-lg ring-1 ring-white/20 transition hover:bg-white hover:text-ink" aria-label="Kapat">
 <svg viewBox="0 0 20 20" class="pointer-events-none h-5 w-5" fill="none" stroke="currentColor" stroke-width="2"><path d="M5 5l10 10M15 5L5 15" stroke-linecap="round"/></svg>
 </button>
 </div>
 </div>

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
                <label class="inline-flex cursor-pointer items-center gap-2 text-sm text-ink-muted">
                    <input type="checkbox" name="spoiler" value="1" @checked(old('spoiler')) class="rounded border-ink-line text-brand-pink focus:ring-brand-pink">
                    Spoiler içeriyor
                </label>
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
                    @if ($review->spoiler)
 <details class="mt-3">
 <summary class="cursor-pointer text-xs font-semibold text-brand-gold">Spoiler — göstermek için tıkla</summary>
 <p class="mt-2 whitespace-pre-line text-sm leading-6 text-ink-muted">{{ $review->body }}</p>
 </details>
 @else
 <p class="mt-3 whitespace-pre-line text-sm leading-6 text-ink-muted">{{ $review->body }}</p>
 @endif
 <div class="mt-3 flex items-center justify-between gap-3">
 @if ($viewer instanceof \App\Models\User && $viewer->isNot($review->user))
 <form method="POST" action="{{ route('games.reviews.vote', $review) }}">
 @csrf
 <button type="submit" class="text-xs font-semibold text-ink-muted transition hover:text-ink-text">Faydalı ({{ $review->votes_count }})</button>
 </form>
 @else
 <span class="text-xs text-ink-faint">Faydalı ({{ $review->votes_count }})</span>
 @endif
 @if ($viewer instanceof \App\Models\User && ($viewer->isAdmin() || $viewer->is($review->user)))
                            <form method="POST" action="{{ route('games.reviews.destroy', $review) }}">
                                @csrf
                                @method('delete')
                                <button type="submit" class="text-xs text-danger opacity-70 transition hover:opacity-100 hover:underline">Sil</button>
                            </form>
                    @endif
 </div>
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
