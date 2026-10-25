@extends('layouts.site')

@section('title', 'Destek — '.config('app.name'))

@section('content')
    <section class="flex flex-wrap items-end justify-between gap-4">
        <div>
            <p class="eyebrow">Destek</p>
            <h1 class="mt-2 font-display text-3xl font-bold tracking-tight sm:text-4xl">Bir sorun mu var?</h1>
            <p class="mt-2 max-w-xl text-sm leading-6 text-ink-muted">Coin, kod, indirme ya da site hatası. Mesajın yöneticiye düşer, yanıt buradan gelir.</p>
        </div>
        <x-button :href="route('coins')" variant="ghost" size="sm">Coin paketleri</x-button>
    </section>

    @if (session('sent'))
        <p class="alert-ok mt-6" role="status">Mesajın iletildi.</p>
    @endif

    <div class="mt-8 grid items-start gap-6 lg:grid-cols-[1fr_0.9fr]">
        <section class="card p-6 sm:p-7">
            <h2 class="font-display text-xl font-bold tracking-tight">Yeni mesaj</h2>
            <p class="mt-1.5 text-sm leading-6 text-ink-muted">Ne olduğunu kısaca yaz. Yönetici aynı yazışmadan cevaplar.</p>
            @guest
                <x-button :href="route('login')" size="lg" class="mt-5 min-h-12">Giriş yapıp yaz</x-button>
            @else
            <form method="POST" action="{{ route('support.store') }}" class="mt-5 space-y-4" novalidate>
                @csrf
                <div>
                    <label class="field-label" for="subject">Konu</label>
                    <input id="subject" name="subject" value="{{ old('subject', request('konu')) }}" required maxlength="160" class="field" placeholder="Kod gelmedi, indirme açılmıyor…">
                    @error('subject')<p class="field-error">{{ $message }}</p>@enderror
                </div>
                <div>
                    <label class="field-label" for="body">Mesaj</label>
                    <textarea id="body" name="body" rows="6" required maxlength="4000" class="field" placeholder="Ne denedin, hangi sayfada kaldın?">{{ old('body') }}</textarea>
                    @error('body')<p class="field-error">{{ $message }}</p>@enderror
                </div>
                <x-button type="submit" size="lg" class="min-h-12">Yöneticiye gönder</x-button>
            </form>
            @endguest
        </section>

        <section class="card p-6 sm:p-7">
            <h2 class="font-display text-xl font-bold tracking-tight">Yazışmaların</h2>
            @if ($threads->isEmpty())
                <p class="mt-3 text-sm leading-6 text-ink-muted">Henüz mesajın yok. Konunu yaz, yanıt burada birikir.</p>
            @else
                <ul class="mt-4 divide-y divide-ink-line">
                    @foreach ($threads as $thread)
                        <li>
                            <a href="{{ route('support.show', $thread) }}" class="flex items-start justify-between gap-3 py-3 transition hover:text-white">
                                <span class="min-w-0">
                                    <span class="flex items-center gap-2">
                                        @if ($thread->unread_by_user)
                                            <span class="h-2 w-2 shrink-0 rounded-full bg-brand-pink" aria-label="Yeni yanıt"></span>
                                        @endif
                                        <span class="truncate font-semibold">{{ $thread->subject }}</span>
                                    </span>
                                    <span class="mt-1 block truncate text-xs text-ink-muted">{{ $thread->latestMessage?->body }}</span>
                                </span>
                                <x-badge :tone="$thread->status === \App\Enums\SupportThreadStatus::Closed ? 'muted' : 'ok'">{{ $thread->status->label() }}</x-badge>
                            </a>
                        </li>
                    @endforeach
                </ul>
            @endif
        </section>
    </div>
@endsection
