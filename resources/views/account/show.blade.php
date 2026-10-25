@extends('layouts.site')

@section('title', 'Hesap — '.config('app.name'))

@section('content')
    @php
        $totalCodes = $codeCount ?? $user->redeemedCodes->count();
        $totalDownloads = $downloadCount ?? $user->downloads->count();
    @endphp

    <div class="card p-6 sm:p-8">
        <div class="flex flex-wrap items-center justify-between gap-6 border-b border-ink-line pb-6">
            <div class="flex items-center gap-4">
                <span class="grid h-14 w-14 place-items-center rounded-2xl bg-accent text-2xl font-bold text-white shadow-glow">
                    {{ mb_strtoupper(mb_substr($user->name, 0, 1)) }}
                </span>
                <div>
                    <div class="flex items-center gap-2">
                        <h1 class="font-display text-2xl font-bold tracking-tight sm:text-3xl">{{ $user->name }}</h1>
                        @if ($user->coin_balance > 0)
 <x-badge tone="vip">{{ $user->coin_balance }} coin</x-badge>
                        @endif
                    </div>
                    <p class="mt-1 text-sm text-ink-muted">{{ $user->email }}</p>
                </div>
            </div>
            <div class="flex items-center gap-2">
                <x-button :href="route('support.index')" variant="ghost" size="sm">Destek</x-button>
                @if ($user->isAdmin())
                    <x-button href="{{ url('/yonetim') }}" variant="gold" size="sm">Yönetim Paneli</x-button>
                @endif
                <form method="POST" action="{{ route('logout') }}">
                    @csrf
                    <x-button type="submit" variant="ghost" size="sm">Çıkış</x-button>
                </form>
            </div>
        </div>

        {{-- İstatistik Kartları --}}
        <div class="mt-6 grid gap-4 sm:grid-cols-3">
            <section class="rounded-2xl border border-ink-line bg-ink-deep/50 p-5 transition hover:border-ink-line-strong">
                <div class="flex items-center justify-between">
                    <p class="eyebrow">Bakiye</p>
                    @if ($user->coin_balance > 0)
                        <span class="h-2 w-2 rounded-full bg-brand-gold animate-pulse"></span>
                    @endif
                </div>
        <p class="mt-2 font-display text-3xl font-bold text-gold"
 x-data="coinCounter({{ (int) $user->coin_balance }}, {{ session('celebrate') ? (int) session('coin_gained', 0) : 0 }})">
            <span x-text="shown"></span> <span class="text-base font-medium text-ink-muted">coin</span>
        </p>
        <p class="mt-1.5 text-xs text-ink-muted">Oyunları coin ile açarsın.</p>
            </section>

            <section class="rounded-2xl border border-ink-line bg-ink-deep/50 p-5 transition hover:border-ink-line-strong">
                <p class="eyebrow">Kullanılan kod</p>
                <p class="mt-2 font-display text-3xl font-bold text-ink-text">{{ $totalCodes }}</p>
                <p class="mt-1.5 text-xs text-ink-muted">Son kayıtlar aşağıda.</p>
            </section>

            <section class="rounded-2xl border border-ink-line bg-ink-deep/50 p-5 transition hover:border-ink-line-strong">
                <p class="eyebrow">İndirme</p>
                <p class="mt-2 font-display text-3xl font-bold text-ink-text">{{ $totalDownloads }}</p>
                <p class="mt-1.5 text-xs text-ink-muted">Son indirdiğin dosyalar.</p>
            </section>
        </div>
    </div>

    {{-- Aksiyonlar: Kod Kullanma ve Şifre Güncelleme --}}
    <div class="mt-8 grid items-start gap-6 lg:grid-cols-2">
        {{-- Kod Kullan --}}
        <section class="card relative overflow-hidden p-6 sm:p-7" x-data="flash({{ session('celebrate') ? 'true' : 'false' }})">
            <div x-show="celebrate" x-cloak class="pointer-events-none absolute inset-0">
                <span class="absolute left-8 top-6 h-3 w-3 animate-ping rounded-full bg-brand-gold"></span>
                <span class="absolute right-12 top-10 h-2 w-2 animate-ping rounded-full bg-brand-pink"></span>
                <span class="absolute bottom-8 left-1/3 h-2.5 w-2.5 animate-ping rounded-full bg-ok"></span>
            </div>

            <div class="flex items-center gap-2">
                <div class="grid h-8 w-8 place-items-center rounded-lg bg-brand-gold/15 text-brand-gold">
                    <svg viewBox="0 0 20 20" class="h-4 w-4" fill="none" stroke="currentColor" stroke-width="2"><rect x="3" y="4" width="14" height="12" rx="2"/><path d="M7 8h6M7 12h3"/></svg>
                </div>
                <h2 class="font-display text-xl font-bold tracking-tight">Kod kullan</h2>
            </div>
            <p class="mt-1.5 text-xs leading-5 text-ink-muted">Kodun coin miktarı bakiyene eklenir.</p>

            @if (session('celebrate'))
                <p class="alert-gold mt-4 font-medium" role="status">
                    Bakiyen {{ session('coin_balance') }} coin oldu.
                </p>
            @endif

            <form method="POST" action="{{ route('account.redeem') }}" class="mt-5 space-y-3">
                @csrf
                <label for="code" class="sr-only">Coin kodu</label>
                <div class="relative">
                    <input id="code" name="code" value="{{ old('code') }}" autocomplete="off" placeholder="XXXX-XXXX-XXXX-XXXX" class="field font-mono uppercase tracking-widest text-brand-gold placeholder:normal-case placeholder:tracking-normal placeholder:text-ink-faint">
                </div>
                @error('code')
                    <p class="field-error">{{ $message }}</p>
                @enderror
                <x-button type="submit" variant="gold" class="w-full">Kodu tanımla</x-button>
            </form>
        </section>

        {{-- Şifre Değiştir --}}
        <section class="card p-6 sm:p-7">
            <div class="flex items-center gap-2">
                <div class="grid h-8 w-8 place-items-center rounded-lg bg-white/5 text-ink-muted">
                    <svg viewBox="0 0 20 20" class="h-4 w-4" fill="none" stroke="currentColor" stroke-width="2"><rect x="4" y="9" width="12" height="8" rx="2"/><path d="M7 9V6a3 3 0 0 1 6 0v3"/></svg>
                </div>
                <h2 class="font-display text-xl font-bold tracking-tight">Şifre değiştir</h2>
            </div>

            @if (session('status') === 'password-updated')
                <p class="alert-ok mt-4" role="status">Şifren güncellendi.</p>
            @endif

            <form method="POST" action="{{ route('password.update') }}" class="mt-5 space-y-3.5">
                @csrf
                @method('put')
                <div>
                    <label class="field-label" for="current_password">Mevcut şifre</label>
                    <input id="current_password" name="current_password" type="password" autocomplete="current-password" required class="field">
                </div>
                <div>
                    <label class="field-label" for="password">Yeni şifre</label>
                    <input id="password" name="password" type="password" autocomplete="new-password" required class="field">
                </div>
                <div>
                    <label class="field-label" for="password_confirmation">Yeni şifre tekrar</label>
                    <input id="password_confirmation" name="password_confirmation" type="password" autocomplete="new-password" required class="field">
                </div>
                @foreach (['current_password', 'password'] as $field)
                    @error($field)
                        <p class="field-error">{{ $message }}</p>
                    @enderror
                @endforeach
                <x-button type="submit" class="w-full">Şifreyi kaydet</x-button>
            </form>
        </section>
    </div>

 {{-- Coin defteri ve satın alınanlar --}}
 <div class="mt-8 grid gap-6 lg:grid-cols-2">
 <section class="card p-6">
 <h2 class="font-display text-lg font-bold tracking-tight">Coin hareketleri</h2>
 @if ($transactions->isEmpty())
 <p class="mt-4 rounded-2xl border border-ink-line bg-white/[0.02] p-5 text-center text-sm text-ink-muted">Henüz hareket yok.</p>
 @else
 <ul class="mt-4 divide-y divide-ink-line overflow-hidden rounded-2xl border border-ink-line bg-ink-deep/30">
 @foreach ($transactions as $row)
 <li class="grid grid-cols-[1fr_auto_auto] items-center gap-3 p-3.5 text-xs sm:text-sm">
 <span class="min-w-0">
 <span class="block text-ink-text">{{ $row->type->label() }}@if ($row->note) · {{ $row->note }}@endif</span>
 <span class="text-ink-faint">{{ $row->created_at?->timezone(config('app.display_timezone'))->translatedFormat('d M Y H:i') }}</span>
 </span>
 <span class="font-semibold {{ $row->amount > 0 ? 'text-ok' : 'text-brand-pink' }}">{{ $row->amount > 0 ? '+' : '' }}{{ $row->amount }}</span>
 <span class="text-ink-muted">{{ $row->balance_after }}</span>
 </li>
 @endforeach
 </ul>
 <div class="mt-4">{{ $transactions->links() }}</div>
 @endif
 </section>
 <section class="card p-6">
 <h2 class="font-display text-lg font-bold tracking-tight">Satın aldığın oyunlar</h2>
 @if ($unlocks->isEmpty())
 <p class="mt-4 rounded-2xl border border-ink-line bg-white/[0.02] p-5 text-center text-sm text-ink-muted">Henüz oyun açmadın.</p>
 @else
 <ul class="mt-4 divide-y divide-ink-line overflow-hidden rounded-2xl border border-ink-line bg-ink-deep/30">
 @foreach ($unlocks as $unlock)
 <li class="flex items-center justify-between gap-3 p-3.5 text-xs sm:text-sm">
 <a href="{{ $unlock->game ? route('games.show', $unlock->game) : '#' }}" class="min-w-0 truncate text-ink-text hover:text-white">{{ $unlock->game?->title ?? 'Silinmiş oyun' }}</a>
 <span class="shrink-0 text-ink-muted">{{ $unlock->coins_paid }} coin</span>
 </li>
 @endforeach
 </ul>
 @endif
 </section>
 </div>

    {{-- Geçmiş Listeleri --}}
    <div class="mt-8 grid gap-6 lg:grid-cols-2">
        {{-- Kod Geçmişi --}}
        <section class="card p-6">
            <h2 class="font-display text-lg font-bold tracking-tight">Kod geçmişi</h2>
            @if ($user->redeemedCodes->isEmpty())
                <p class="mt-4 rounded-2xl border border-ink-line bg-white/[0.02] p-5 text-center text-sm text-ink-muted">Henüz kod kullanmadın.</p>
            @else
                <ul class="mt-4 divide-y divide-ink-line overflow-hidden rounded-2xl border border-ink-line bg-ink-deep/30">
                    @foreach ($user->redeemedCodes as $code)
                        <li class="flex items-center justify-between p-3.5 text-xs sm:text-sm">
                            <span class="font-mono font-medium text-brand-gold">{{ $code->code }}</span>
                            <span class="text-ink-muted">{{ $code->coin_amount }} coin · {{ $code->used_at?->timezone(config('app.display_timezone'))->translatedFormat('d M Y') }}</span>
                        </li>
                    @endforeach
                </ul>
            @endif
        </section>

        {{-- İndirme Geçmişi --}}
        <section class="card p-6">
            <h2 class="font-display text-lg font-bold tracking-tight">İndirme geçmişi</h2>
            @if ($user->downloads->isEmpty())
                <p class="mt-4 rounded-2xl border border-ink-line bg-white/[0.02] p-5 text-center text-sm text-ink-muted">Henüz indirme yok.</p>
            @else
                <ul class="mt-4 divide-y divide-ink-line overflow-hidden rounded-2xl border border-ink-line bg-ink-deep/30">
                    @foreach ($user->downloads as $download)
                        <li class="flex items-center justify-between gap-3 p-3.5 text-xs sm:text-sm">
                            <span class="min-w-0 truncate text-ink-text">{{ $download->gameFile?->game?->title ?? 'Silinmiş oyun' }} · <span class="text-ink-muted">{{ $download->gameFile?->label }}</span></span>
                            <span class="shrink-0 text-ink-faint">{{ $download->created_at?->timezone(config('app.display_timezone'))->translatedFormat('d M Y') }}</span>
                        </li>
                    @endforeach
                </ul>
            @endif
        </section>
    </div>
@endsection


