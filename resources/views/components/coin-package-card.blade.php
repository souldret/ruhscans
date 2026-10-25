@props(['package', 'featured' => false])

<article class="card relative flex flex-col p-6 sm:p-7 {{ $featured ? 'border-brand-gold/50 bg-gradient-to-br from-brand-gold/15 via-ink-surface to-ink-surface lg:p-10' : '' }}">
    <div class="flex items-center justify-between gap-3">
        <span class="grid h-11 w-11 place-items-center rounded-2xl bg-gradient-to-br from-brand-gold to-brand-amber text-ink shadow-glow-gold">
            <svg viewBox="0 0 24 24" class="h-6 w-6" fill="currentColor" aria-hidden="true"><path d="M12 2.5 14.6 9H21l-5.2 3.8L17.8 19 12 15.2 6.2 19l2-6.2L3 9h6.4L12 2.5Z"/></svg>
        </span>
        @if ($package->badge)
            <x-badge tone="vip">{{ $package->badge }}</x-badge>
        @endif
    </div>

    <h2 class="mt-4 font-display {{ $featured ? 'text-2xl sm:text-3xl' : 'text-xl' }} font-bold tracking-tight">{{ $package->name }}</h2>

    <p class="mt-3 font-display {{ $featured ? 'text-5xl' : 'text-4xl' }} font-bold text-gold">
        {{ number_format($package->coin_amount, 0, ',', '.') }}
        <span class="text-base font-medium text-ink-muted">coin</span>
    </p>
    @if ($package->bonus_coin > 0)
        <p class="mt-1 text-sm font-semibold text-brand-gold">+ {{ number_format($package->bonus_coin, 0, ',', '.') }} bonus coin</p>
    @endif

    <p class="mt-4 font-display text-lg font-semibold">{{ $package->price_label }}</p>
    @if ($package->description)
        <p class="mt-2 text-sm leading-6 text-ink-muted">{{ $package->description }}</p>
    @endif

    <x-button :href="route('coins.go', $package)" variant="gold" size="lg" target="_blank" rel="noopener noreferrer" class="mt-6 min-h-12 w-full">
        {{ $package->button_text }}
    </x-button>
</article>
