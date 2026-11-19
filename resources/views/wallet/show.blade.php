<x-night-layout title="Cüzdan">
    <div class="mx-auto max-w-3xl px-4 py-10" @if ($glow) x-data x-init="setTimeout(() => $el.querySelector('.stone-burst')?.classList.add('is-on'), 40)" @endif>
        <header class="flex items-end justify-between gap-4">
            <div>
                <p class="text-xs uppercase tracking-[0.25em] text-gold">Cüzdan</p>
                <h1 class="mt-2 font-display text-5xl">{{ $user->name }}</h1>
            </div>
            @if ($user->isVip())
                <span class="badge-vip">VIP</span>
            @endif
        </header>

        <section class="relative mt-8 overflow-hidden glass rounded-3xl p-6">
            <div class="stone-burst pointer-events-none absolute inset-0" aria-hidden="true"></div>
            <p class="text-xs uppercase tracking-[0.2em] text-mist/60">Ruh taşı</p>
            <p class="mt-2 flex items-center gap-3 font-display text-6xl text-gold">
                <span class="stone inline-block h-8 w-8 rounded-full bg-magenta"></span>
                {{ $user->ruh_tasi_bakiye }}
            </p>
            <p class="mt-4 text-sm text-mist/75">
                @if ($user->isVip())
                    VIP {{ $user->vip_bitis->timezone(config('app.timezone'))->format('d.m.Y H:i') }} tarihine kadar aktif.
                @else
                    VIP değil.
                @endif
            </p>
        </section>

        <section class="mt-6 glass rounded-3xl p-6">
            <h2 class="font-display text-3xl">Kod kullan</h2>
            <form class="mt-4 flex flex-col gap-3 sm:flex-row" method="POST" action="{{ route('wallet.redeem') }}">
                @csrf
                <label class="sr-only" for="kod">Kod</label>
                <input id="kod" name="kod" value="{{ old('kod') }}" autocomplete="off" spellcheck="false" placeholder="RUH-XXXX-XXXX-XXXX-XXXX" class="w-full rounded-2xl border-white/10 bg-ink text-mist placeholder:text-mist/40">
                <button class="btn-magenta shrink-0" type="submit">Kullan</button>
            </form>
            @error('kod')
                <p class="mt-3 text-sm text-magenta">{{ $message }}</p>
            @enderror
            @if (session('status'))
                <p class="mt-3 text-sm text-gold">{{ session('status') }}</p>
            @endif
            @if ($sonteklif)
                <p class="mt-4 text-sm text-mist/70">Kodlar <a class="text-gold underline" href="{{ $sonteklif }}" rel="noopener noreferrer">Sonteklif</a> üzerinden alınır.</p>
            @endif
        </section>

        <section class="mt-6">
            <h2 class="font-display text-3xl">İşlem geçmişi</h2>
            <ul class="mt-4 space-y-2">
                @forelse ($transactions as $transaction)
                    <li class="glass flex items-center justify-between rounded-2xl px-4 py-3 text-sm">
                        <span>
                            <span class="{{ $transaction->yon->value === 'kredi' ? 'text-gold' : 'text-magenta' }}">{{ $transaction->yon->value === 'kredi' ? '+' : '−' }}{{ $transaction->miktar }}</span>
                            <span class="ml-2 text-mist/70">{{ $transaction->sebep }}</span>
                        </span>
                        <span class="text-mist/50">{{ $transaction->bakiye_sonrasi }} · {{ $transaction->created_at->timezone(config('app.timezone'))->format('d.m.Y H:i') }}</span>
                    </li>
                @empty
                    <li class="text-sm text-mist/60">Henüz işlem yok.</li>
                @endforelse
            </ul>
            <div class="mt-4">{{ $transactions->links() }}</div>
        </section>
    </div>
</x-night-layout>
