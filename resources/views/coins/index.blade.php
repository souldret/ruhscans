@extends('layouts.site')

@section('title', 'Coin paketleri — '.config('app.name'))

@section('content')
    @php
        $member = $user instanceof \App\Models\User ? $user : null;
    @endphp

    <section class="card relative overflow-hidden border-brand-gold/30 bg-gradient-to-br from-brand-gold/15 via-ink-surface to-ink-surface p-6 sm:p-10">
        <div class="pointer-events-none absolute -right-16 -top-16 h-56 w-56 rounded-full bg-brand-gold/20 blur-3xl"></div>
        <div class="relative flex flex-wrap items-center justify-between gap-6">
            <div>
                <p class="eyebrow text-brand-gold">Coin</p>
                <h1 class="mt-2 font-display text-3xl font-bold tracking-tight sm:text-5xl">Paketini seç, <span class="text-gold">oyunu aç</span></h1>
                <p class="mt-4 max-w-xl text-base leading-7 text-ink-muted">Ödeme satış ortağımızda tamamlanır. Kodun e-postana gelir, buraya yazınca bakiyene eklenir.</p>
            </div>
            <div class="w-full rounded-3xl border border-brand-gold/30 bg-ink-deep/70 p-5 sm:w-auto sm:min-w-60">
                <p class="text-xs uppercase tracking-widest text-ink-muted">Bakiyen</p>
                @auth
                    <p class="mt-1 font-display text-4xl font-bold text-gold">{{ number_format($member->coin_balance, 0, ',', '.') }}</p>
                    <p class="text-xs text-ink-muted">coin</p>
                @else
                    <p class="mt-2 text-sm text-ink-muted">Bakiyeni görmek için giriş yap.</p>
                    <x-button :href="route('login')" variant="gold" size="lg" class="mt-4 min-h-12 w-full">Giriş yap</x-button>
                @endauth
            </div>
        </div>
    </section>

    @if ($featured === null && $packages->isEmpty())
        <section class="card mt-8 border-dashed p-10 text-center">
            <span class="mx-auto grid h-16 w-16 place-items-center rounded-3xl bg-gradient-to-br from-brand-gold to-brand-amber text-ink">
                <svg viewBox="0 0 24 24" class="h-8 w-8" fill="currentColor" aria-hidden="true"><path d="M12 2.5 14.6 9H21l-5.2 3.8L17.8 19 12 15.2 6.2 19l2-6.2L3 9h6.4L12 2.5Z"/></svg>
            </span>
            <h2 class="mt-5 font-display text-2xl font-bold">Şu an satışta paket yok</h2>
            <p class="mx-auto mt-2 max-w-md text-sm leading-6 text-ink-muted">Yeni paketler hazır olduğunda burada görünecek. Elindeki kodu yine de aşağıdan girebilirsin.</p>
        </section>
    @else
        @if ($featured)
            <div class="mt-8">
                <x-coin-package-card :package="$featured" featured />
            </div>
        @endif

        @if ($packages->isNotEmpty())
            <div class="mt-6 grid gap-4 sm:grid-cols-2 lg:grid-cols-3">
                @foreach ($packages as $package)
                    <x-coin-package-card :package="$package" />
                @endforeach
            </div>
        @endif
    @endif

    <section class="mt-10">
        <h2 class="font-display text-2xl font-bold tracking-tight">Nasıl çalışır</h2>
        <ol class="mt-4 grid gap-4 sm:grid-cols-3">
            @foreach (['Paketi seç' => 'Sana uygun coin paketinin satın al düğmesine bas.', 'Kodu al' => 'Ödeme sonrası kodun e-postana gelir.', 'Kodu buraya gir' => 'Aşağıdaki kutuya yaz, coin bakiyene eklensin.'] as $title => $text)
                <li class="card p-5">
                    <span class="grid h-9 w-9 place-items-center rounded-full bg-brand-gold/15 font-display font-bold text-brand-gold">{{ $loop->iteration }}</span>
                    <h3 class="mt-3 font-display text-lg font-bold">{{ $title }}</h3>
                    <p class="mt-1 text-sm leading-6 text-ink-muted">{{ $text }}</p>
                </li>
            @endforeach
        </ol>
    </section>

    <section class="card mt-8 p-6 sm:p-7">
        <h2 class="font-display text-xl font-bold tracking-tight">Kodun var mı?</h2>
        <p class="mt-1.5 text-sm leading-6 text-ink-muted">XXXX-XXXX-XXXX-XXXX biçimindeki kodu yaz. Coin miktarı bakiyene eklenir.</p>
        @auth
            <form method="POST" action="{{ route('account.redeem') }}" class="mt-5 space-y-3">
                @csrf
                <label for="code" class="sr-only">Coin kodu</label>
                <input id="code" name="code" value="{{ old('code') }}" autocomplete="off" placeholder="XXXX-XXXX-XXXX-XXXX" class="field min-h-12 font-mono uppercase tracking-widest text-brand-gold">
                @error('code')
                    <p class="field-error">{{ $message }}</p>
                @enderror
                <x-button type="submit" variant="gold" size="lg" class="min-h-12 w-full sm:w-auto">Kodu tanımla</x-button>
            </form>
        @else
            <x-button :href="route('login')" variant="gold" size="lg" class="mt-5 min-h-12">Giriş yapıp kodu gir</x-button>
        @endauth
    </section>

    <section class="mt-10" x-data="faq">
        <h2 class="font-display text-2xl font-bold tracking-tight">Sık sorulan sorular</h2>
        <div class="mt-4 divide-y divide-ink-line overflow-hidden rounded-3xl border border-ink-line">
            @foreach ([
                'Kod nasıl kullanılır?' => 'Hesabına giriş yap, bu sayfadaki ya da hesap sayfasındaki kutuya kodu yaz. Coin anında bakiyene eklenir.',
                'İade edilir mi?' => 'Kullanılmamış kodlar satış kanalının koşullarına göre iade edilir. Kullanılmış kodun coin’i oyun açmak için harcandıysa iade yönetim onayına bağlıdır.',
                'Coin’in süresi var mı?' => 'Hayır. Bakiyendeki coin’in son kullanma tarihi yoktur, dilediğin zaman harcarsın.',
                'Açılan oyunlar kalıcı mı?' => 'Evet. Bir oyunu açtığında o oyunun sonraki dosyaları ve yamaları da ek ücret olmadan sana açık kalır.',
            ] as $question => $answer)
                <div>
                    <button type="button" data-faq-index="{{ $loop->index }}" class="flex min-h-12 w-full items-center justify-between gap-4 px-5 py-4 text-left font-semibold" @click="toggle({{ $loop->index }})" aria-expanded="false">
                        {{ $question }}
                        <svg viewBox="0 0 20 20" class="h-4 w-4 shrink-0 transition" fill="none" stroke="currentColor" stroke-width="2" aria-hidden="true"><path d="m5 8 5 5 5-5" stroke-linecap="round" stroke-linejoin="round"/></svg>
                    </button>
                    <p data-faq-panel="{{ $loop->index }}" hidden class="px-5 pb-5 text-sm leading-6 text-ink-muted">{{ $answer }}</p>
                </div>
            @endforeach
        </div>
        <p class="mt-4 rounded-2xl border border-brand-gold/30 bg-brand-gold/10 p-4 text-sm leading-6 text-ink-text">
            Kodları yalnızca bu sayfadaki resmi satış bağlantılarımızdan alın. Başka yerden alınan kodlar geçersiz olabilir.
        </p>
    </section>

    <section class="card mt-6 flex flex-wrap items-center justify-between gap-4 p-6">
        <div>
            <h2 class="font-display text-lg font-bold">Hata mı aldın?</h2>
            <p class="mt-1 text-sm leading-6 text-ink-muted">Kod gelmedi, ödeme düştü ya da coin bakiyene yansımadıysa yöneticiye yaz.</p>
        </div>
        <x-button :href="route('support.index')" variant="ghost" class="min-h-12">Destek için buraya</x-button>
        </div>
    </section>
@endsection
