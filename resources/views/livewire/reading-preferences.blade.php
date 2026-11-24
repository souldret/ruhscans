@php
    $viewer = auth()->user();
    $panelUser = $viewer
        && $viewer->hasAnyRole([\App\Models\User::ROLE_ADMIN, \App\Models\User::ROLE_CEVIRI])
        && ! $viewer->isBlocked();
@endphp
<div class="mx-auto max-w-2xl px-4 pb-16 pt-8">
    <p class="text-sm font-semibold uppercase tracking-[0.2em] text-gold">Profil</p>
    <h1 class="mt-2 font-display text-4xl text-mist">{{ $viewer?->name ?? 'Okuma tercihleri' }}</h1>
    <p class="mt-2 text-base text-mist/70">Cüzdanın, okuma geçmişin ve gizlilik tercihlerin tek yerde.</p>

    {{-- Hızlı erişim kartları (mobilde Cüzdan ve Geçmişe tek dokunuş) --}}
    <div class="mt-6 grid grid-cols-2 gap-3 sm:grid-cols-3">
        <a href="{{ route('wallet.show') }}" class="glass tap-target flex flex-col items-start justify-between rounded-2xl p-4 transition hover:border-gold/40">
            <span class="text-xs font-semibold uppercase tracking-wider text-gold">Cüzdan</span>
            <span class="mt-2 flex items-center gap-1.5 font-display text-2xl text-mist">
                <x-stone class="h-5 w-5" />
                {{ $viewer?->ruh_tasi_bakiye ?? 0 }}
            </span>
        </a>
        <a href="{{ route('reader.history') }}" class="glass tap-target flex flex-col items-start justify-between rounded-2xl p-4 transition hover:border-magenta/40">
            <span class="text-xs font-semibold uppercase tracking-wider text-mist/60">Okuma</span>
            <span class="mt-2 font-display text-xl text-mist">Geçmiş →</span>
        </a>
        <a href="{{ route('profile') }}" class="glass tap-target col-span-2 flex flex-col items-start justify-between rounded-2xl p-4 transition hover:border-magenta/40 sm:col-span-1">
            <span class="text-xs font-semibold uppercase tracking-wider text-mist/60">Hesap</span>
            <span class="mt-2 font-display text-xl text-mist">Parola & ayar →</span>
        </a>
        @if ($panelUser)
            <a href="{{ url('/admin') }}" class="glass tap-target col-span-2 flex items-center justify-between rounded-2xl border-gold/30 p-4 text-gold transition hover:border-gold sm:col-span-3">
                <span class="font-display text-xl">Admin Paneli</span>
                <span>→</span>
            </a>
        @endif
    </div>

    <form wire:submit="save" class="glass mt-6 space-y-5 rounded-3xl p-6">
        <h2 class="font-display text-2xl text-mist">Okuma tercihleri</h2>

        <label class="flex min-h-11 cursor-pointer items-center justify-between gap-4 text-base text-mist">
            <span>Kapakları bulanık tut</span>
            <input type="checkbox" wire:model="kapak_blur" class="h-5 w-5 shrink-0 rounded border-white/25 bg-ink text-magenta focus:ring-magenta">
        </label>

        <label class="flex min-h-11 cursor-pointer items-center justify-between gap-4 text-base text-mist">
            <span>Gizli mod</span>
            <input type="checkbox" wire:model="gizli_mod" class="h-5 w-5 shrink-0 rounded border-white/25 bg-ink text-magenta focus:ring-magenta">
        </label>

        <p class="text-sm leading-6 text-mist/65">Gizli mod kapakları hover ile de açmaz ve okuma geçmişi kaydetmez. Panik tuşu ya da Esc'ye iki kez basmak seni aşağıdaki adrese götürür ve sekme başlığını nötrler.</p>

        <div>
            <label for="panik_url" class="mb-2 block text-base font-medium text-mist">Panik hedefi</label>
            <input id="panik_url" type="url" wire:model="panik_url" class="h-14 w-full rounded-2xl border border-white/10 bg-ink px-4 text-base text-mist focus:border-magenta focus:ring-magenta" required>
            @error('panik_url') <span class="mt-2 block text-sm text-[#FF9CC4]">{{ $message }}</span> @enderror
        </div>

        <fieldset>
            <legend class="text-base font-medium text-mist">Etiket kara listesi</legend>
            <div class="mt-3 flex flex-wrap gap-2">
                @foreach ($tags as $tag)
                    <label class="btn-ghost tap-target cursor-pointer !py-1.5 text-sm has-[:checked]:border-magenta has-[:checked]:bg-wine">
                        <input type="checkbox" class="sr-only" value="{{ $tag->slug }}" wire:model="etiket_karaliste">
                        {{ $tag->name }}
                    </label>
                @endforeach
            </div>
        </fieldset>

        <button type="submit" class="btn-magenta tap-target h-14 w-full text-base font-semibold">Tercihleri kaydet</button>
    </form>
</div>
