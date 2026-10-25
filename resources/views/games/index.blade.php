@extends('layouts.site')

@section('title', 'Oyunlar — '.config('app.name'))

@section('meta')
    @if ($games->previousPageUrl())
        <link rel="prev" href="{{ $games->previousPageUrl() }}">
    @endif
    @if ($games->nextPageUrl())
        <link rel="next" href="{{ $games->nextPageUrl() }}">
    @endif
@endsection

@section('content')
    @php
        $selected = $filters['etiket'] ?? [];
        $chips = [
            'erisim' => ['label' => 'Erişim', 'options' => ['' => 'Tümü', 'ucretsiz' => 'Ücretsiz', 'ucretli' => 'Coin’li', 'aldiklarim' => 'Satın aldıklarım']],
            'ceviri' => ['label' => 'Çeviri', 'options' => ['' => 'Tümü', 'complete' => 'Tamamlandı', 'ongoing' => 'Devam ediyor']],
        ];
        $active = collect($filters)->except('sirala')->filter(fn ($value) => filled($value))->count();
    @endphp

    <div class="flex flex-wrap items-end justify-between gap-4">
        <div>
            <p class="eyebrow">Katalog</p>
            <h1 class="mt-1 font-display text-3xl font-bold tracking-tight sm:text-4xl">Oyunlar</h1>
        </div>
        <p class="text-sm text-ink-muted">{{ number_format($games->total(), 0, ',', '.') }} oyun bulundu</p>
    </div>

    <form method="GET" action="{{ route('games.index') }}" class="card mt-6 space-y-5 p-4 sm:p-5" x-data x-ref="filters">
        <div class="flex flex-col gap-3 sm:flex-row">
            <div class="relative flex-1">
                <svg viewBox="0 0 20 20" class="pointer-events-none absolute left-4 top-1/2 h-4 w-4 -translate-y-1/2 text-ink-faint" fill="none" stroke="currentColor" stroke-width="2" aria-hidden="true"><circle cx="9" cy="9" r="6"/><path d="m14 14 3.5 3.5" stroke-linecap="round"/></svg>
                <label for="q" class="sr-only">Ara</label>
                <input id="q" name="q" type="search" maxlength="100" value="{{ $filters['q'] ?? '' }}" placeholder="Oyun veya geliştirici adı" class="field rounded-full pl-11">
            </div>
            <div class="flex gap-2">
                <label class="relative flex-1 sm:flex-none">
                    <span class="sr-only">Sırala</span>
                    <select name="sirala" @change="$refs.filters.requestSubmit()" class="field h-full cursor-pointer rounded-full py-2.5 pl-4 pr-10">
                        <option value="yeni" @selected(($filters['sirala'] ?? 'yeni') === 'yeni')>En yeni</option>
                        <option value="populer" @selected(($filters['sirala'] ?? '') === 'populer')>Popüler</option>
                        <option value="guncellenen" @selected(($filters['sirala'] ?? '') === 'guncellenen')>Son güncellenen</option>
 <option value="fiyat-artan" @selected(($filters['sirala'] ?? '') === 'fiyat-artan')>Fiyat: artan</option>
 <option value="fiyat-azalan" @selected(($filters['sirala'] ?? '') === 'fiyat-azalan')>Fiyat: azalan</option>
                    </select>
                </label>
                <x-button type="submit">Uygula</x-button>
            </div>
        </div>

        <div class="flex flex-wrap gap-x-6 gap-y-3">
            @foreach ($chips as $name => $group)
                <fieldset class="flex flex-wrap items-center gap-2">
                    <legend class="sr-only">{{ $group['label'] }}</legend>
                    <span class="mr-1 text-xs font-medium text-ink-faint" aria-hidden="true">{{ $group['label'] }}</span>
                    @foreach ($group['options'] as $value => $label)
                        <label class="relative">
                            <input type="radio" name="{{ $name }}" value="{{ $value }}" @checked(($filters[$name] ?? '') === (string) $value) @change="$refs.filters.requestSubmit()" class="peer sr-only">
                            <span class="chip">{{ $label }}</span>
                        </label>
                    @endforeach
                </fieldset>
            @endforeach
        </div>

        @if ($tags->isNotEmpty())
            <details class="group" @if ($selected !== []) open @endif>
                <summary class="flex cursor-pointer list-none items-center gap-2 text-sm text-ink-muted transition hover:text-ink-text">
                    <svg viewBox="0 0 20 20" class="h-4 w-4 transition-transform group-open:rotate-90" fill="none" stroke="currentColor" stroke-width="2" aria-hidden="true"><path d="m7.5 15 5-5-5-5" stroke-linecap="round" stroke-linejoin="round"/></svg>
                    Etiketler
                    @if ($selected !== [])
                        <span class="rounded-full bg-brand-pink/20 px-2 py-0.5 text-xs text-white">{{ count($selected) }} seçili</span>
                    @endif
                </summary>
                <div class="mt-3 flex max-h-48 flex-wrap gap-2 overflow-y-auto pr-1">
                    @foreach ($tags as $tag)
                        <label class="relative">
                            <input type="checkbox" name="etiket[]" value="{{ $tag->slug }}" @checked(in_array($tag->slug, $selected, true)) class="peer sr-only">
                            <span class="chip">
                                <svg viewBox="0 0 16 16" class="hidden h-3 w-3 peer-checked:inline" aria-hidden="true"></svg>
                                {{ $tag->name }}
                            </span>
                        </label>
                    @endforeach
                </div>
            </details>
        @endif

        @if ($active > 0)
            <div class="flex items-center justify-between border-t border-ink-line pt-4 text-sm">
                <span class="text-ink-muted">{{ $active }} filtre etkin</span>
                <a href="{{ route('games.index', array_filter(['sirala' => $filters['sirala'] ?? null])) }}" class="text-ink-muted underline-offset-4 transition hover:text-ink-text hover:underline">Filtreleri temizle</a>
            </div>
        @endif
    </form>

    @if ($games->isEmpty())
        <div class="card-flat mt-8 flex flex-col items-center px-6 py-16 text-center">
            <div class="grid h-14 w-14 place-items-center rounded-full bg-white/5">
                <svg viewBox="0 0 20 20" class="h-6 w-6 text-ink-muted" fill="none" stroke="currentColor" stroke-width="1.7" aria-hidden="true"><circle cx="9" cy="9" r="6"/><path d="m14 14 3.5 3.5" stroke-linecap="round"/></svg>
            </div>
            <p class="mt-4 font-display text-lg font-semibold">Bu filtreyle eşleşen oyun yok.</p>
            <p class="mt-1 text-sm text-ink-muted">Filtreleri gevşetmeyi dene.</p>
            <x-button :href="route('games.index')" variant="ghost" class="mt-6">Tüm oyunları göster</x-button>
        </div>
    @else
        <div class="mt-8 grid grid-cols-2 gap-x-4 gap-y-7 min-[480px]:grid-cols-3 sm:grid-cols-4 md:grid-cols-5 lg:grid-cols-6">
            @foreach ($games as $game)
                <x-game-card :game="$game" class="!w-full" />
            @endforeach
        </div>
        <div class="mt-10">{{ $games->links() }}</div>
    @endif
@endsection
