@extends('layouts.app')

@section('title', 'Oyunlar — '.config('app.name'))

@section('content')
    <h1 class="mb-6 text-2xl font-semibold">Oyunlar</h1>

    @if ($games->isEmpty())
        <p class="text-zinc-400">Henüz yayınlanmış oyun yok.</p>
    @else
        <div class="grid gap-4 sm:grid-cols-2 lg:grid-cols-3">
            @foreach ($games as $game)
                @include('games._card', ['game' => $game])
            @endforeach
        </div>

        <div class="mt-8">
            {{ $games->links() }}
        </div>
    @endif
@endsection
