@extends('layouts.app')

@section('title', $game->title.' — '.config('app.name'))

@section('content')
    <article>
        <p class="text-sm text-zinc-500">Ren'Py @if ($game->version) · sürüm {{ $game->version }} @endif</p>
        <h1 class="mt-1 text-3xl font-semibold">{{ $game->title }}</h1>

        <p class="mt-4 max-w-3xl whitespace-pre-line text-zinc-300">{{ $game->description }}</p>

        <p class="mt-4 text-sm text-zinc-500">
            {{ $game->downloads_count }} indirme
            @if ($game->uploader)
                · yükleyen {{ $game->uploader->name }}
            @endif
        </p>

        <div class="mt-8">
            @auth
                @can('download', $game)
                    <a href="{{ route('games.download', $game) }}" class="inline-block rounded-md bg-rose-500 px-4 py-2 font-medium text-white hover:bg-rose-400">
                        İndir
                    </a>
                @else
                    <p class="text-sm text-zinc-400">Bu oyun henüz indirilebilir değil.</p>
                @endcan
            @else
                <a href="{{ route('login') }}" class="inline-block rounded-md bg-rose-500 px-4 py-2 font-medium text-white hover:bg-rose-400">
                    İndirmek için giriş yap
                </a>
            @endauth
        </div>
    </article>
@endsection
