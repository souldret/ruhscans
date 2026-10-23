@extends('layouts.site')

@section('title', 'Erişim yok — '.config('app.name'))

@section('content')
    <section class="py-20 text-center">
        <p class="font-display text-7xl font-semibold text-accent">403</p>
        <h1 class="mt-4 font-display text-3xl font-semibold">Bu salona giriş yok</h1>
        <p class="mx-auto mt-3 max-w-md text-ink-muted">Bu sayfayı görmek için yetkin bulunmuyor.</p>
        <div class="mt-6"><x-button :href="route('home')">Ana sayfaya dön</x-button></div>
    </section>
@endsection
