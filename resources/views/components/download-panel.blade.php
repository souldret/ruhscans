@props(['game'])

@php
    $user = auth()->user();
    $unlocked = $user && ($user->isAdmin() || $user->unlocks()->where('game_id', $game->id)->exists());
    $price = $game->effectivePrice();
    $onSale = $game->sale_price !== null && ($game->sale_ends_at === null || $game->sale_ends_at->isFuture()) && $game->sale_price < $game->coin_price;
    $balance = $user?->coin_balance ?? 0;
    $files = $game->files->groupBy(fn ($file) => $file->platform->value);
    $tabs = collect([
        'windows' => 'Windows',
        'android' => 'Android',
        'mac' => 'Mac',
        'linux' => 'Linux',
        'patch' => 'Yama',
    ])->filter(fn ($label, $key) => $files->has($key));
@endphp

<aside class="card h-fit p-6 sm:p-7 lg:sticky lg:top-24"
    x-data="downloadPanel({
 open: {{ $unlocked || $game->isFree() ? 'true' : 'false' }},
 free: {{ $game->isFree() ? 'true' : 'false' }},
 balance: {{ (int) $balance }},
 price: {{ (int) $price }},
 url: '{{ route('games.unlock', $game) }}',
 })">
    <div class="flex items-center justify-between gap-2 border-b border-ink-line pb-4">
        <div>
            <p class="eyebrow">İndirme</p>
            <h2 class="mt-1 font-display text-xl font-bold tracking-tight" x-text="title()"></h2>
        </div>
        <span x-show="bought()" x-cloak><x-badge tone="vip">Satın alındı</x-badge></span>
        @if ($game->isFree())
            <x-badge tone="free">Ücretsiz</x-badge>
        @endif
    </div>

    <div x-show="locked()" x-cloak class="mt-5 space-y-4">
        @guest
            <p class="text-sm leading-6 text-ink-muted">Fiyatı görmek ve oyunu açmak için giriş yap.</p>
            <x-button :href="route('login')" class="w-full">Giriş yap</x-button>
        @else
            <div class="flex items-end justify-between">
                <p class="font-display text-3xl font-bold text-gold">{{ $price }} <span class="text-base text-ink-muted">coin</span></p>
                @if ($onSale)
                    <p class="text-sm text-ink-faint line-through">{{ $game->coin_price }} coin</p>
                @endif
            </div>
            @if ($onSale && $game->sale_ends_at)
                <p class="text-xs text-ink-muted">İndirim {{ $game->sale_ends_at->timezone(config('app.display_timezone'))->translatedFormat('d F Y H:i') }} tarihinde bitiyor.</p>
            @endif

            <form method="POST" action="{{ route('games.unlock', $game) }}" x-show="confirm" x-cloak class="rounded-2xl border border-ink-line bg-ink-deep/60 p-4 text-sm" @submit="buy($event)">
                @csrf
                <p>Bakiyen: <span class="font-semibold" x-text="balance">{{ $balance }}</span> coin</p>
                <p class="mt-1">Harcanacak: <span class="font-semibold" x-text="price">{{ $price }}</span> coin</p>
                <p class="mt-1">Kalacak: <span class="font-semibold" x-text="remaining()">{{ $balance - $price }}</span> coin</p>
                <div class="mt-3 flex gap-2">
                    <x-button type="submit" variant="gold" class="flex-1" x-bind:disabled="busy">Onayla</x-button>
                    <x-button type="button" variant="ghost" class="flex-1" @click="cancel()">Vazgeç</x-button>
                </div>
            </form>

            <div x-show="short" x-cloak class="rounded-2xl border border-brand-pink/40 bg-brand-pink/10 p-4 text-sm">
                <p><span class="font-semibold" x-text="missing"></span> coin daha gerekiyor.</p>
                <x-button :href="route('coins')" variant="gold" class="mt-3 w-full">Coin yükle</x-button>
            </div>

            @unless ($unlocked)
                <div x-show="ready()" x-cloak>
                    <x-button type="button" variant="gold" class="w-full" @click="ask()">
                        {{ $price }} coin ile aç
                    </x-button>
                </div>
            @endunless
        @endguest
    </div>

    @if ($unlocked || $game->isFree())
        @include('components.partials.download-files', ['tabs' => $tabs, 'files' => $files])
    @endif
</aside>
