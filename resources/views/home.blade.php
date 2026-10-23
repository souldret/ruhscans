@extends('layouts.app')

@section('title', config('app.name').' — Türkçeleştirilmiş Ren\'Py oyunları')

@section('content')
    <section class="mb-10">
        <h1 class="text-3xl font-semibold tracking-tight">Türkçeleştirilmiş Ren'Py oyunları</h1>
        <p class="mt-3 max-w-2xl text-zinc-400">
            Bağımsız görsel romanlar, Türkçe yama ile. İndirmek için ücretsiz üye olman yeterli.
        </p>
    </section>

    @if ($games->isEmpty())
        <p class="text-zinc-400">Henüz yayınlanmış oyun yok.</p>
    @else
        <div class="grid gap-4 sm:grid-cols-2 lg:grid-cols-3">
            @foreach ($games as $game)
                @include('games._card', ['game' => $game])
            @endforeach
        </div>
    @endif
@endsection
