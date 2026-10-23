@extends('layouts.site')

@section('title', $game->title.' — '.config('app.name'))

@section('content')
    <article class="grid gap-8 lg:grid-cols-[16rem_1fr]">
        <img src="{{ $game->cover_path ? asset('storage/'.$game->cover_path) : asset('images/poster.svg') }}" alt="{{ $game->title }} afişi" width="600" height="800" class="aspect-[3/4] w-full rounded-2xl object-cover">

        <div>
            <x-badge tone="patch">Türkçe Yama</x-badge>
            <h1 class="mt-3 font-display text-4xl font-semibold">{{ $game->title }}</h1>
            @if ($game->original_title)
                <p class="mt-1 text-ink-muted">{{ $game->original_title }} · {{ $game->developer }}</p>
            @endif
            <p class="mt-5 max-w-2xl text-ink-muted">{{ $game->description }}</p>

            <dl class="mt-6 grid max-w-md grid-cols-2 gap-4 text-sm">
                <div><dt class="text-ink-muted">Çeviri</dt><dd>%{{ $game->translation_percent }}</dd></div>
                <div><dt class="text-ink-muted">Sürüm</dt><dd>{{ $game->translation_version }}</dd></div>
                <div><dt class="text-ink-muted">Boyut</dt><dd>{{ $game->size_mb }} MB</dd></div>
                <div><dt class="text-ink-muted">Erişim</dt><dd>{{ $game->access->label() }}</dd></div>
            </dl>

            <div class="mt-6 flex flex-wrap gap-2">
                @foreach ($game->tags as $tag)
                    <span class="rounded-full border border-ink-line px-3 py-1 text-xs text-ink-muted">{{ $tag->name }}</span>
                @endforeach
            </div>
        </div>
    </article>
@endsection
