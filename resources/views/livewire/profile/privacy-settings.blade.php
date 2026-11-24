<div class="space-y-6">
    {{-- Anlık gizlilik anahtarları --}}
    <section class="glass rounded-3xl p-5 sm:p-7" aria-labelledby="gizlilik-baslik">
        <h2 id="gizlilik-baslik" class="font-display text-2xl text-mist">Gizlilik ve +18 tercihleri</h2>
        <p class="mt-1 text-base text-mist/70">Değişiklikler anında hesabına kaydedilir.</p>

        @if ($statusMessage !== '')
            <p class="mt-4 rounded-2xl border border-gold/30 bg-gold/10 px-4 py-2.5 text-sm text-gold" aria-live="polite">
                {{ $statusMessage }}
            </p>
        @endif

        <div class="mt-5 divide-y divide-white/10">
            <label class="flex min-h-14 cursor-pointer items-center justify-between gap-4 py-3">
                <div>
                    <span class="block text-base font-medium text-mist">Kapakları bulanık tut</span>
                    <span class="block text-sm text-mist/60">Katalog ve seri kartlarındaki +18 kapakları varsayılan olarak bulanık gösterir.</span>
                </div>
                <input type="checkbox" wire:model.live="kapak_blur" class="h-5 w-5 shrink-0 rounded border-white/25 bg-ink text-magenta focus:ring-magenta">
            </label>

            <label class="flex min-h-14 cursor-pointer items-center justify-between gap-4 py-3">
                <div>
                    <span class="block text-base font-medium text-mist">Gizli mod</span>
                    <span class="block text-sm text-mist/60">Okuma geçmişi kaydetmez ve kapakları hover ile de açmaz.</span>
                </div>
                <input type="checkbox" wire:model.live="gizli_mod" class="h-5 w-5 shrink-0 rounded border-white/25 bg-ink text-magenta focus:ring-magenta">
            </label>

            <label class="flex min-h-14 cursor-pointer items-center justify-between gap-4 py-3">
                <div>
                    <span class="block text-base font-medium text-mist">Nötr sekme başlığı</span>
                    <span class="block text-sm text-mist/60">Tarayıcı sekmesinde seri adı yerine sade "Yeni sekme" başlığı ve nötr ikon gösterir.</span>
                </div>
                <input type="checkbox" wire:model.live="notr_sekme_baslik" class="h-5 w-5 shrink-0 rounded border-white/25 bg-ink text-magenta focus:ring-magenta">
            </label>
        </div>
    </section>

    {{-- Panik tuşu hedef adresi --}}
    <section class="glass rounded-3xl p-5 sm:p-7" aria-labelledby="panik-baslik">
        <h2 id="panik-baslik" class="font-display text-2xl text-mist">Panik tuşu hedefi</h2>
        <p class="mt-1 text-base text-mist/70">Panik düğmesine bastığında veya Esc'ye iki kez dokunduğunda yönlendirileceğin güvenli adres (yalnızca http:// veya https://).</p>

        <form wire:submit="savePanicUrl" class="mt-5 flex flex-col gap-3 sm:flex-row">
            <div class="min-w-0 flex-1">
                <label for="profile-panik-url" class="sr-only">Panik hedef URL</label>
                <input
                    id="profile-panik-url"
                    type="url"
                    wire:model="panik_url"
                    placeholder="https://www.google.com"
                    required
                    class="h-14 w-full rounded-2xl border border-white/10 bg-ink px-4 text-base text-mist placeholder:text-mist/40 focus:border-magenta focus:ring-magenta"
                >
                @error('panik_url')
                    <p class="mt-2 text-sm text-[#FF9CC4]">{{ $message }}</p>
                @enderror
            </div>
            <button type="submit" class="btn-magenta tap-target h-14 shrink-0 px-6 text-base font-semibold">
                Kaydet
            </button>
        </form>
    </section>

    {{-- Etiket kara listesi (arama + çip) --}}
    <section class="glass rounded-3xl p-5 sm:p-7" aria-labelledby="karaliste-baslik">
        <h2 id="karaliste-baslik" class="font-display text-2xl text-mist">Etiket kara listesi</h2>
        <p class="mt-1 text-base text-mist/70">Seçtiğin etiketlere sahip seriler ana sayfada ve keşfette gizlenir.</p>

        @if ($selectedTags->isNotEmpty())
            <div class="mt-4">
                <p class="text-xs font-semibold uppercase tracking-wider text-gold">Engellenenler ({{ $selectedTags->count() }})</p>
                <div class="mt-2 flex flex-wrap gap-2">
                    @foreach ($selectedTags as $tag)
                        <button
                            type="button"
                            wire:click="toggleTag('{{ $tag->slug }}')"
                            class="tap-target inline-flex items-center gap-1.5 rounded-full border border-magenta/60 bg-wine px-4 text-sm font-medium text-mist hover:bg-wine/80"
                        >
                            <span>{{ $tag->name }}</span>
                            <span aria-hidden="true">×</span>
                        </button>
                    @endforeach
                </div>
            </div>
        @endif

        <div class="mt-4">
            <label for="tag-search" class="sr-only">Etiket ara</label>
            <input
                id="tag-search"
                type="search"
                wire:model.live.debounce.250ms="tagSearch"
                placeholder="Etiket ara…"
                class="h-12 w-full rounded-2xl border border-white/10 bg-ink px-4 text-base text-mist placeholder:text-mist/40 focus:border-magenta focus:ring-magenta"
            >
        </div>

        <div class="mt-4 flex flex-wrap gap-2">
            @forelse ($tags as $tag)
                @php $blocked = in_array($tag->slug, $etiket_karaliste, true); @endphp
                <button
                    type="button"
                    wire:click="toggleTag('{{ $tag->slug }}')"
                    class="tap-target rounded-full px-4 text-sm transition {{ $blocked ? 'border border-magenta bg-wine text-mist' : 'border border-white/10 bg-ink/50 text-mist/80 hover:border-magenta/50' }}"
                    aria-pressed="{{ $blocked ? 'true' : 'false' }}"
                >
                    {{ $blocked ? '✓ ' : '+ ' }}{{ $tag->name }}
                </button>
            @empty
                <p class="text-sm text-mist/60">Eşleşen etiket bulunamadı.</p>
            @endforelse
        </div>
    </section>
</div>
