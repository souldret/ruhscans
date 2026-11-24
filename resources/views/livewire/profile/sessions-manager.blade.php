<section class="glass rounded-3xl p-5 sm:p-7" aria-labelledby="oturumlar-baslik">
    <div class="flex flex-wrap items-start justify-between gap-3">
        <div>
            <h2 id="oturumlar-baslik" class="font-display text-2xl text-mist">Aktif cihazlar ve oturumlar</h2>
            <p class="mt-1 text-base text-mist/70">Hesabına açık cihazları gör ve tanımadığın oturumları kapat.</p>
        </div>
        <button
            type="button"
            wire:click="logoutOtherDevices"
            wire:loading.attr="disabled"
            class="btn-wine tap-target px-5 text-sm font-semibold"
        >
            Diğer cihazlardan çıkış yap
        </button>
    </div>

    @if ($statusMessage !== '')
        <p class="mt-4 rounded-2xl border border-gold/30 bg-gold/10 px-4 py-2.5 text-sm text-gold" aria-live="polite">
            {{ $statusMessage }}
        </p>
    @endif

    <ul class="mt-5 divide-y divide-white/10">
        @forelse ($sessions as $session)
            @php $isCurrent = $session->id === $currentSessionId; @endphp
            <li class="flex flex-wrap items-center justify-between gap-3 py-3.5">
                <div class="min-w-0">
                    <div class="flex flex-wrap items-center gap-2">
                        <span class="text-base font-medium text-mist">{{ $session->deviceLabel() }}</span>
                        @if ($isCurrent)
                            <span class="badge-new">Bu cihaz</span>
                        @endif
                    </div>
                    <p class="mt-0.5 text-sm text-mist/60">
                        IP: {{ $session->ip_address ?: 'Bilinmiyor' }}
                        · Son etkinlik: {{ $session->last_activity?->diffForHumans() }}
                    </p>
                </div>

                @unless ($isCurrent)
                    <button
                        type="button"
                        wire:click="revokeSession('{{ $session->id }}')"
                        class="btn-ghost tap-target shrink-0 px-4 text-sm text-[#FF9CC4] hover:border-[#FF9CC4]/60"
                    >
                        Çıkış yap
                    </button>
                @endunless
            </li>
        @empty
            <li class="py-6 text-center text-base text-mist/60">Aktif oturum kaydı henüz oluşmadı.</li>
        @endforelse
    </ul>
</section>
