@extends('layouts.site')

@section('title', $title.' — '.config('app.name'))

@section('content')
    <section class="card mx-auto max-w-lg p-8 py-16 text-center sm:p-12">
        <img src="{{ asset('brand/mark.jpeg') }}" alt="" width="72" height="72" class="mx-auto h-16 w-16 rounded-full object-cover ring-2 ring-white/10">
        <p class="mt-6 font-display text-7xl font-extrabold text-accent">{{ $code }}</p>
        <h1 class="mt-4 font-display text-2xl font-bold tracking-tight sm:text-3xl">{{ $heading }}</h1>
        <p class="mx-auto mt-3 max-w-md text-sm leading-6 text-ink-muted">{{ $message }}</p>
        <div class="mt-8 flex flex-col justify-center gap-3 sm:flex-row">
            <x-button :href="route('home')">Ana sayfaya dön</x-button>
            <x-button :href="route('support.index')" variant="ghost">Destek</x-button>
        </div>
        <p class="mt-5 text-sm text-ink-muted">Hata mı aldın? Destek için <a href="{{ route('support.index') }}" class="link">buraya yaz</a>, yönetici yanıtlasın.</p>
    </section>
@endsection
