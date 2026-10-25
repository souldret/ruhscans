@extends('layouts.site')

@section('title', $thread->subject.' — Destek')

@section('content')
    <nav aria-label="Gezinti" class="mb-6 flex items-center gap-2 text-sm text-ink-muted">
        <a href="{{ route('support.index') }}" class="transition hover:text-ink-text">Destek</a>
        <span aria-hidden="true">/</span>
        <span class="truncate text-ink-text">{{ $thread->subject }}</span>
    </nav>

    <section class="card p-6 sm:p-8">
        <div class="flex flex-wrap items-start justify-between gap-4 border-b border-ink-line pb-5">
            <div>
                <p class="eyebrow">Yazışma</p>
                <h1 class="mt-2 font-display text-2xl font-bold tracking-tight sm:text-3xl">{{ $thread->subject }}</h1>
                <p class="mt-1 text-xs text-ink-muted">{{ $thread->created_at?->timezone(config('app.timezone'))->format('d.m.Y H:i') }}</p>
            </div>
            <x-badge :tone="$thread->status === \App\Enums\SupportThreadStatus::Closed ? 'muted' : 'ok'">{{ $thread->status->label() }}</x-badge>
        </div>

        @if (session('sent'))
            <p class="alert-ok mt-5" role="status">Mesajın iletildi.</p>
        @endif
        @if (session('closed'))
            <p class="alert-ok mt-5" role="status">Yazışma kapatıldı. Yeni bir konu açabilirsin.</p>
        @endif

        <ol class="mt-6 space-y-4">
            @foreach ($thread->messages as $message)
                <li class="flex {{ $message->is_staff ? 'justify-start' : 'justify-end' }}">
                    <article class="max-w-[85%] rounded-3xl px-4 py-3 text-sm leading-6 {{ $message->is_staff ? 'border border-brand-gold/30 bg-brand-gold/10 text-ink-text' : 'bg-white/[0.06] text-ink-text' }}">
                        <p class="text-[11px] font-semibold uppercase tracking-wider {{ $message->is_staff ? 'text-brand-gold' : 'text-ink-muted' }}">
                            {{ $message->is_staff ? 'Yönetici' : $message->user?->name }}
                            <span class="font-normal normal-case tracking-normal text-ink-faint">· {{ $message->created_at?->timezone(config('app.timezone'))->format('d.m H:i') }}</span>
                        </p>
                        <p class="mt-1 whitespace-pre-wrap">{{ $message->body }}</p>
                    </article>
                </li>
            @endforeach
        </ol>

        @can('reply', $thread)
            <form method="POST" action="{{ route('support.reply', $thread) }}" class="mt-6 space-y-3 border-t border-ink-line pt-6">
                @csrf
                <label class="field-label" for="body">Yanıtın</label>
                <textarea id="body" name="body" rows="4" required maxlength="4000" class="field" placeholder="Devamını yaz">{{ old('body') }}</textarea>
                @error('body')<p class="field-error">{{ $message }}</p>@enderror
                <x-button type="submit" size="lg" class="min-h-12">Gönder</x-button>
            </form>
        @else
            <p class="mt-6 border-t border-ink-line pt-6 text-sm text-ink-muted">Bu yazışma kapalı. Yeni bir konu açmak istersen destek sayfasına dön.</p>
        @endcan

        @can('close', $thread)
            <form method="POST" action="{{ route('support.close', $thread) }}" class="mt-3">
                @csrf
                <x-button type="submit" variant="plain" size="sm">Yazışmayı kapat</x-button>
            </form>
        @endcan
    </section>
@endsection
