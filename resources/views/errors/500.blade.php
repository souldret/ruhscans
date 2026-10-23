@extends('layouts.site')

@section('title', 'Bir sorun oluştu — '.config('app.name'))

@section('content')
    <section class="py-20 text-center">
        <p class="font-display text-7xl font-semibold text-accent">500</p>
        <h1 class="mt-4 font-display text-3xl font-semibold">Perde kapandı</h1>
        <p class="mx-auto mt-3 max-w-md text-ink-muted">İşlemin tamamlanamadı. Biraz sonra yeniden dene.</p>
        <div class="mt-6"><x-button :href="route('home')">Ana sayfaya dön</x-button></div>
    </section>
@endsection
