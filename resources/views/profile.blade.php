@php
    $viewer = auth()->user();
    $panelUser = $viewer
        && $viewer->hasAnyRole([\App\Models\User::ROLE_ADMIN, \App\Models\User::ROLE_CEVIRI])
        && ! $viewer->isBlocked();
    $activeTab = $initialTab ?? 'ozet';
    $tabs = [
        'ozet' => 'Hesap',
        'gizlilik' => 'Gizlilik & +18',
        'cuzdan' => 'Cüzdan',
        'oturumlar' => 'Oturumlar',
        'gecmis' => 'Geçmiş',
    ];
    if ($activeTab === 'hesap') {
        $activeTab = 'ozet';
    }
@endphp

<x-night-layout :seo="$seo ?? ['title' => 'Profil']">
    <div
        class="mx-auto max-w-4xl space-y-6 px-4 pb-16 pt-6 sm:pt-8"
        x-data="{
            tab: @js($activeTab),
            select(id) {
                this.tab = id;
                const url = new URL(window.location.href);
                url.searchParams.set('sekme', id);
                window.history.replaceState({}, '', url);
            }
        }"
    >
        {{-- Özet kartı her sekmede üstte görünür --}}
        <livewire:profile.summary-card />

        @if ($panelUser)
            <a href="{{ url('/admin') }}" class="glass tap-target flex items-center justify-between rounded-2xl border border-gold/30 px-5 py-3.5 text-gold transition hover:border-gold">
                <span class="font-display text-xl">Admin Paneli</span>
                <span>→</span>
            </a>
        @endif

        {{-- Mobil öncelikli yatay kaydırılabilir sekme çubuğu --}}
        <div class="no-scrollbar -mx-4 flex gap-2 overflow-x-auto px-4 pb-1 sm:mx-0 sm:flex-wrap sm:px-0" role="tablist" aria-label="Profil bölümleri">
            @foreach ($tabs as $key => $label)
                <button
                    type="button"
                    role="tab"
                    :aria-selected="(tab === '{{ $key }}').toString()"
                    @click="select('{{ $key }}')"
                    class="tap-target shrink-0 rounded-full px-5 text-sm font-semibold transition"
                    :class="tab === '{{ $key }}' ? 'bg-magenta text-ink shadow-glow' : 'glass text-mist/80 hover:border-magenta/50 hover:text-mist'"
                >
                    {{ $label }}
                </button>
            @endforeach
        </div>

        {{-- Sekme 1: Hesap (Ad/E-posta, Şifre, Hesap Silme, Veri Dışa Aktarma) --}}
        <div x-show="tab === 'ozet'" class="space-y-6" role="tabpanel">
            <div class="glass rounded-3xl p-5 sm:p-7">
                <livewire:profile.update-profile-information-form />
            </div>

            <div class="glass rounded-3xl p-5 sm:p-7">
                <livewire:profile.update-password-form />
            </div>

            <section class="glass rounded-3xl p-5 sm:p-7" aria-labelledby="veri-aktarim-baslik">
                <h2 id="veri-aktarim-baslik" class="font-display text-2xl text-mist">Kişisel veri dışa aktarma</h2>
                <p class="mt-1 text-base text-mist/70">Profil bilgilerini, tercihlerini, kütüphaneni, okuma geçmişini, açılan bölümlerini ve cüzdan hareketlerini tek bir JSON dosyası olarak indirebilirsin.</p>
                <div class="mt-4">
                    <a href="{{ route('profile.export') }}" class="btn-ghost tap-target inline-flex px-6 text-base text-gold">
                        Verilerimi indir (JSON)
                    </a>
                </div>
            </section>

            <div class="glass rounded-3xl border border-[#FF2E88]/20 p-5 sm:p-7">
                <livewire:profile.delete-user-form />
            </div>
        </div>

        {{-- Sekme 2: Gizlilik ve +18 --}}
        <div x-show="tab === 'gizlilik'" x-cloak role="tabpanel">
            <livewire:profile.privacy-settings />
        </div>

        {{-- Sekme 3: Cüzdan özeti ve açılan bölümler --}}
        <div x-show="tab === 'cuzdan'" x-cloak role="tabpanel">
            <livewire:profile.wallet-summary />
        </div>

        {{-- Sekme 4: Aktif cihazlar ve oturumlar --}}
        <div x-show="tab === 'oturumlar'" x-cloak role="tabpanel">
            <livewire:profile.sessions-manager />
        </div>

        {{-- Sekme 5: Okuma geçmişi yönetimi --}}
        <div x-show="tab === 'gecmis'" x-cloak role="tabpanel">
            <livewire:profile.reading-history-manager />
        </div>
    </div>
</x-night-layout>
