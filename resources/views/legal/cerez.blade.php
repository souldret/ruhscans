@extends('layouts.site')

@section('title', 'Çerez Politikası — '.config('app.name'))

@section('content')
    <article class="mx-auto max-w-3xl pb-16">
        <h1 class="font-display text-4xl font-semibold">Çerez Politikası</h1>
        <p class="mt-2 text-sm text-ink-muted">Taslak. Yayımlamadan önce hukuki olarak gözden geçirilecek.</p>
        <div class="mt-8 space-y-4 text-ink-muted">
            <p>Oturum çerezi hesabı açık tutar. `adult` çerezi yaş onayını bir yıl hatırlar. İkisi de sitenin çalışması içindir, reklam ağına verilmez.</p>
            <p>Çerezi tarayıcıdan silersen yaş sorusu ve oturum yeniden gelir. Zorunlu olmayan bir izleme çerezi kullanılmıyor.</p>
        </div>
    </article>
@endsection
