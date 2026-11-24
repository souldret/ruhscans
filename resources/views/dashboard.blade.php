@php
    $user = auth()->user();
@endphp
<x-night-layout :seo="['title' => 'Hoş geldin']">
    <div class="mx-auto max-w-4xl space-y-6 px-4 pb-16 pt-8">
        <section class="glass rounded-3xl bg-gradient-to-br from-wine/40 via-surface to-ink p-6 sm:p-8">
            <p class="text-xs font-semibold uppercase tracking-[0.25em] text-gold">Gece Kütüphanesi</p>
            <h1 class="mt-2 font-display text-3xl text-mist sm:text-4xl">Hoş geldin, {{ $user->name }}</h1>
            <p class="mt-2 text-base text-mist/75">Rafların hazır. Kaldığın seriden devam edebilir veya yeni bölümlere göz atabilirsin.</p>

            <div class="mt-6 flex flex-wrap gap-3">
                <a href="{{ route('home') }}" class="btn-magenta tap-target px-6 font-semibold">Ana sayfaya git</a>
                <a href="{{ route('discover') }}" class="btn-ghost tap-target px-6">Keşfet</a>
            </div>
        </section>

        <div class="grid grid-cols-2 gap-3 sm:grid-cols-4">
            <a href="{{ route('library') }}" class="glass tap-target flex flex-col items-start justify-between rounded-2xl p-4 transition hover:border-magenta/40">
                <span class="text-xs font-semibold uppercase tracking-wider text-mist/60">Rafın</span>
                <span class="mt-2 font-display text-xl text-mist">Kütüphane →</span>
            </a>
            <a href="{{ route('wallet.show') }}" class="glass tap-target flex flex-col items-start justify-between rounded-2xl p-4 transition hover:border-gold/40">
                <span class="text-xs font-semibold uppercase tracking-wider text-gold">Cüzdan</span>
                <span class="mt-2 flex items-center gap-1.5 font-display text-xl text-gold">
                    <x-stone class="h-4 w-4" /> {{ $user->ruh_tasi_bakiye }}
                </span>
            </a>
            <a href="{{ route('reader.history') }}" class="glass tap-target flex flex-col items-start justify-between rounded-2xl p-4 transition hover:border-magenta/40">
                <span class="text-xs font-semibold uppercase tracking-wider text-mist/60">Okuma</span>
                <span class="mt-2 font-display text-xl text-mist">Geçmiş →</span>
            </a>
            <a href="{{ route('profile') }}" class="glass tap-target flex flex-col items-start justify-between rounded-2xl p-4 transition hover:border-magenta/40">
                <span class="text-xs font-semibold uppercase tracking-wider text-mist/60">Hesap</span>
                <span class="mt-2 font-display text-xl text-mist">Profil →</span>
            </a>
        </div>
    </div>
</x-night-layout>
