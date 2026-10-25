@extends('layouts.site')

@section('title', 'Hakkımızda — '.config('app.name'))

@section('content')
    <article class="mx-auto max-w-3xl pb-16">
        <h1 class="font-display text-4xl font-semibold">Hakkımızda</h1>
        <p class="mt-2 text-sm text-ink-muted">Taslak. Yayımlamadan önce hukuki olarak gözden geçirilecek.</p>
        <div class="mt-8 space-y-4 text-ink-muted">
            <p>{{ config('app.name') }}, bağımsız Ren'Py oyunlarını Türkçeleştirir ve üyelerine sunar. Çeviriler gönüllü emektir; oyunların satış hakkı geliştiricidedir.</p>
            <p>Site yetişkinlere yöneliktir. 18 yaşından küçüksen çık.</p>
        </div>
    </article>
@endsection
