@extends('layouts.site')

@section('title', 'İçerik Kaldırma — '.config('app.name'))

@section('content')
    <article class="mx-auto max-w-3xl pb-16">
        <div class="card p-6 sm:p-8">
            <h1 class="font-display text-3xl font-bold tracking-tight sm:text-4xl">İçerik Kaldırma ve Telif Bildirimi</h1>
            <p class="mt-2 text-xs uppercase tracking-wider text-ink-faint">Taslak. Yayımlamadan önce hukuki olarak gözden geçirilecek.</p>
            <div class="mt-6 space-y-4 text-sm leading-6 text-ink-muted">
                <p>Hak sahibi ya da yetkili temsilcisi, kaldırılmasını istediği sayfanın adresini ve gerekçesini aşağıdaki formla iletir. Bildirim incelenmeden içerik yayından kalkmaz.</p>
            </div>

            @if (session('sent'))
                <p class="alert-ok mt-6" role="status">Bildirimin alındı. İncelemeden sonra dönüş yapılır.</p>
            @else
                <form method="POST" action="{{ route('legal.takedown.store') }}" class="mt-8 space-y-4" novalidate>
                    @csrf
                    <div class="absolute -left-[9999px]" aria-hidden="true">
                        <label for="company">Şirket</label>
                        <input id="company" name="company" tabindex="-1" autocomplete="off">
                    </div>
                    <div>
                        <label class="field-label" for="name">Ad</label>
                        <input id="name" name="name" value="{{ old('name') }}" required class="field" placeholder="Ad Soyad">
                        @error('name')<p class="field-error">{{ $message }}</p>@enderror
                    </div>
                    <div>
                        <label class="field-label" for="email">E-posta</label>
                        <input id="email" name="email" type="email" value="{{ old('email') }}" required class="field" placeholder="ornek@posta.com">
                        @error('email')<p class="field-error">{{ $message }}</p>@enderror
                    </div>
                    <div>
                        <label class="field-label" for="url">İçerik adresi</label>
                        <input id="url" name="url" type="url" value="{{ old('url') }}" required class="field" placeholder="https://ruhscans.../oyun/...">
                        @error('url')<p class="field-error">{{ $message }}</p>@enderror
                    </div>
                    <div>
                        <label class="field-label" for="reason">Gerekçe</label>
                        <textarea id="reason" name="reason" rows="5" required class="field" placeholder="Telif hakkı sahipliği veya kaldırma talebi gerekçesini açıklayın...">{{ old('reason') }}</textarea>
                        @error('reason')<p class="field-error">{{ $message }}</p>@enderror
                    </div>
                    <div class="pt-2">
                        <x-button type="submit">Bildir</x-button>
                    </div>
                </form>
            @endif
        </div>
    </article>
@endsection
