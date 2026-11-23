<x-night-layout :seo="$seo">
    <div class="mx-auto max-w-3xl px-4 pb-16 pt-8" @if ($glow) x-data x-init="setTimeout(() => $el.querySelector('.stone-burst')?.classList.add('is-on'), 40)" @endif>
        <header class="flex flex-wrap items-end justify-between gap-3">
            <div>
                <p class="text-sm font-semibold uppercase tracking-[0.25em] text-gold">Cüzdan</p>
                <h1 class="mt-2 font-display text-4xl text-mist sm:text-5xl">{{ $user->name }}</h1>
            </div>
            @if ($user->isVip())
                <span class="badge-vip">VIP üye</span>
            @endif
        </header>

        <section class="relative mt-8 overflow-hidden glass rounded-3xl p-6 sm:p-8" aria-label="Ruh taşı bakiyesi">
            <div class="stone-burst pointer-events-none absolute inset-0" aria-hidden="true"></div>
            <p class="text-sm uppercase tracking-[0.2em] text-mist/60">Ruh taşı</p>
            <p class="mt-2 flex items-center gap-3 font-display text-6xl text-gold">
                <x-stone class="h-9 w-9 shrink-0" />
                {{ $user->ruh_tasi_bakiye }}
            </p>
            <p class="mt-4 text-base leading-7 text-mist/80">
                @if ($user->isVip())
                    VIP erişimin <span class="font-semibold text-gold">{{ $user->vip_bitis->timezone(config('app.timezone'))->format('d.m.Y H:i') }}</span> tarihine kadar aktif.
                @else
                    VIP erişimin yok. Kod kullanarak ruh taşı biriktir ve VIP ol.
                @endif
            </p>
        </section>

        <section class="mt-6 glass rounded-3xl p-6 sm:p-8" aria-labelledby="kod-baslik">
            <h2 id="kod-baslik" class="font-display text-3xl text-mist">Kod kullan</h2>
            <p class="mt-2 text-base leading-7 text-mist/60">Sonteklif'ten aldığın kodu buraya yapıştır.</p>

            <form class="mt-5 space-y-4" method="POST" action="{{ route('wallet.redeem') }}" x-data>
                @csrf
                <div>
                    <label class="mb-2 block text-base font-medium text-mist" for="kod">Ruh kodu</label>
                    <div class="flex flex-col gap-3 sm:flex-row">
                        <input
                            id="kod"
                            x-ref="kod"
                            name="kod"
                            value="{{ old('kod') }}"
                            autocomplete="off"
                            autocapitalize="characters"
                            autocorrect="off"
                            spellcheck="false"
                            inputmode="text"
                            enterkeyhint="done"
                            placeholder="RUH-XXXX-XXXX-XXXX-XXXX"
                            class="h-16 min-w-0 flex-1 rounded-2xl border border-white/10 bg-ink px-4 text-center text-lg tracking-[0.12em] text-mist placeholder:tracking-normal placeholder:text-mist/40 focus:border-magenta focus:ring-magenta"
                        >
                        <button
                            type="button"
                            class="tap-target shrink-0 rounded-full border border-white/10 px-5 text-base text-mist/80 hover:border-magenta/60 hover:text-magenta"
                            @click="navigator.clipboard?.readText().then(v => { $refs.kod.value = v; $refs.kod.focus(); }).catch(() => $refs.kod.focus())"
                        >
                            Yapıştır
                        </button>
                        <button class="btn-magenta h-14 shrink-0 px-8 text-base" type="submit">Kullan</button>
                    </div>
                </div>
                <x-turnstile />
            </form>

            <div aria-live="polite">
                @error('kod')
                    <p class="mt-4 text-base text-[#FF9CC4]">{{ $message }}</p>
                @enderror
                @error('turnstile_token')
                    <p class="mt-4 text-base text-[#FF9CC4]">{{ $message }}</p>
                @enderror
                @if (session('status'))
                    <p class="mt-4 rounded-2xl border border-gold/30 bg-gold/10 px-4 py-3 text-base text-gold">{{ session('status') }}</p>
                @endif
            </div>

            <div class="mt-6 rounded-2xl border border-white/10 bg-ink/50 p-5">
                <h3 class="font-display text-xl text-gold">Kod nereden alınır?</h3>
                <p class="mt-2 text-base leading-7 text-mist/70">
                    Ruh taşı kodları yalnızca Sonteklif üzerinden satılır. Ödeme geçidimiz yok; kodu satın aldıktan sonra burada kullan.
                </p>
                @if ($sonteklif)
                    <a class="tap-target mt-3 inline-flex items-center rounded-full bg-gold px-6 font-semibold text-ink hover:shadow-glow" href="{{ $sonteklif }}" rel="noopener noreferrer" target="_blank">
                        Sonteklif'e git ↗
                    </a>
                @endif
            </div>
        </section>

        <section class="mt-8" aria-labelledby="islem-baslik">
            <h2 id="islem-baslik" class="font-display text-3xl text-mist">İşlem geçmişi</h2>
            <ul class="mt-4 space-y-2">
                @forelse ($transactions as $transaction)
                    <li class="glass flex flex-wrap items-center justify-between gap-2 rounded-2xl px-4 py-3 text-base">
                        <span>
                            <span class="font-semibold {{ $transaction->yon->value === 'kredi' ? 'text-gold' : 'text-magenta' }}">{{ $transaction->yon->value === 'kredi' ? '+' : '−' }}{{ $transaction->miktar }}</span>
                            <span class="ml-2 text-mist/80">{{ $transaction->sebep }}</span>
                        </span>
                        <span class="text-mist/60">{{ $transaction->bakiye_sonrasi }} · {{ $transaction->created_at->timezone(config('app.timezone'))->format('d.m.Y H:i') }}</span>
                    </li>
                @empty
                    <li class="glass rounded-2xl px-4 py-5 text-base text-mist/60">Henüz işlem yok.</li>
                @endforelse
            </ul>
            <div class="mt-4">{{ $transactions->links() }}</div>
        </section>
    </div>
</x-night-layout>
