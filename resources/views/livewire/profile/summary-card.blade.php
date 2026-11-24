<section class="glass rounded-3xl p-5 sm:p-7" aria-label="Profil özeti">
    <div class="flex flex-col gap-5 sm:flex-row sm:items-center">
        {{-- Avatar + yükle/kırp --}}
        <div class="flex items-center gap-4">
            <div class="relative h-20 w-20 shrink-0 overflow-hidden rounded-full border-2 border-magenta/50 shadow-glow">
                @if ($user->avatar_url)
                    <img src="{{ $user->avatar_url }}" alt="{{ $user->name }} avatarı" width="80" height="80" class="h-full w-full object-cover">
                @else
                    <div class="flex h-full w-full items-center justify-center bg-gradient-to-br from-wine via-magenta/70 to-ink font-display text-3xl text-mist" aria-hidden="true">
                        {{ $initial }}
                    </div>
                @endif
            </div>

            <div class="min-w-0 sm:hidden">
                <div class="flex flex-wrap items-center gap-2">
                    <h1 class="truncate font-display text-2xl text-mist">{{ $user->name }}</h1>
                    @if ($user->isVip())
                        <span class="badge-vip">VIP</span>
                    @endif
                </div>
                <p class="mt-1 text-sm text-mist/65">Üyelik: {{ $user->created_at?->translatedFormat('d M Y') }}</p>
            </div>
        </div>

        <div class="min-w-0 flex-1">
            <div class="hidden sm:block">
                <div class="flex flex-wrap items-center gap-2.5">
                    <h1 class="truncate font-display text-3xl text-mist">{{ $user->name }}</h1>
                    @if ($user->isVip())
                        <span class="badge-vip">VIP · {{ $vipDays }} gün kaldı</span>
                    @endif
                </div>
                <p class="mt-1 text-sm text-mist/65">
                    Üyelik: {{ $user->created_at?->translatedFormat('d M Y') }}
                    @if ($user->hasVerifiedEmail())
                        · <span class="text-gold">Doğrulanmış e-posta</span>
                    @else
                        · <span class="text-[#FF9CC4]">E-posta doğrulanmadı</span>
                    @endif
                </p>
            </div>

            {{-- Bakiye & VIP özeti --}}
            <div class="mt-4 grid grid-cols-2 gap-3 sm:max-w-md">
                <div class="rounded-2xl border border-white/10 bg-ink/60 px-4 py-3">
                    <p class="text-xs font-semibold uppercase tracking-wider text-mist/60">Ruh taşı</p>
                    <p class="mt-1 flex items-center gap-1.5 font-display text-2xl text-gold">
                        <x-stone class="h-5 w-5" />
                        <span>{{ $user->ruh_tasi_bakiye }}</span>
                    </p>
                </div>
                <div class="rounded-2xl border border-white/10 bg-ink/60 px-4 py-3">
                    <p class="text-xs font-semibold uppercase tracking-wider text-mist/60">VIP durumu</p>
                    <p class="mt-1 font-display text-lg {{ $user->isVip() ? 'text-gold' : 'text-mist/70' }}">
                        @if ($user->isVip())
                            {{ $vipDays }} gün kaldı
                        @else
                            Standart
                        @endif
                    </p>
                </div>
            </div>
        </div>

        {{-- Hızlı aksiyonlar --}}
        <div class="flex flex-wrap gap-2 sm:flex-col sm:items-stretch">
            <a href="{{ route('wallet.show') }}" class="btn-magenta tap-target flex-1 px-5 text-base font-semibold sm:flex-none">
                Kod kullan
            </a>
            <a href="{{ route('profile.export') }}" class="btn-ghost tap-target flex-1 px-4 text-sm sm:flex-none">
                Verilerimi indir (JSON)
            </a>
        </div>
    </div>

    {{-- Avatar yükle / kare kırpma alanı --}}
    <div
        class="mt-5 border-t border-white/10 pt-4"
        x-data="{
            preview: null,
            naturalW: 0,
            naturalH: 0,
            zoom: 1,
            offsetX: 0,
            offsetY: 0,
            onFile(e) {
                const file = e.target.files?.[0];
                if (!file) return;
                const url = URL.createObjectURL(file);
                const img = new Image();
                img.onload = () => {
                    this.naturalW = img.naturalWidth;
                    this.naturalH = img.naturalHeight;
                    this.zoom = 1;
                    this.offsetX = 0;
                    this.offsetY = 0;
                    this.preview = url;
                    this.syncCrop();
                };
                img.src = url;
            },
            syncCrop() {
                if (!this.naturalW || !this.naturalH) return;
                const base = Math.min(this.naturalW, this.naturalH);
                const size = Math.max(64, Math.round(base / this.zoom));
                const maxX = Math.max(0, this.naturalW - size);
                const maxY = Math.max(0, this.naturalH - size);
                const x = Math.round((maxX / 2) + (this.offsetX / 100) * (maxX / 2));
                const y = Math.round((maxY / 2) + (this.offsetY / 100) * (maxY / 2));
                $wire.set('cropX', Math.max(0, Math.min(maxX, x)));
                $wire.set('cropY', Math.max(0, Math.min(maxY, y)));
                $wire.set('cropSize', size);
            }
        }"
    >
        <div class="flex flex-wrap items-center gap-3">
            <label class="btn-ghost tap-target cursor-pointer px-4 text-sm">
                <span>Avatar yükle / değiştir</span>
                <input type="file" wire:model="avatar" accept="image/jpeg,image/png,image/webp" class="sr-only" @change="onFile($event)">
            </label>

            @if ($user->avatar_path)
                <button type="button" wire:click="removeAvatar" class="tap-target rounded-full px-3 text-sm text-mist/65 hover:text-[#FF9CC4]">
                    Avatarı kaldır
                </button>
            @endif

            <span class="text-xs text-mist/55">JPG, PNG veya WebP · en fazla 5 MB · otomatik 256×256 WebP</span>
        </div>

        @error('avatar')
            <p class="mt-2 text-sm text-[#FF9CC4]">{{ $message }}</p>
        @enderror

        @if ($avatar)
            <div class="mt-4 flex flex-col gap-4 rounded-2xl border border-white/10 bg-ink/70 p-4 sm:flex-row sm:items-center">
                <div class="relative mx-auto h-28 w-28 shrink-0 overflow-hidden rounded-full border-2 border-magenta/60 sm:mx-0">
                    <img
                        x-show="preview"
                        :src="preview"
                        alt="Avatar önizleme"
                        class="h-full w-full object-cover"
                        :style="`transform: scale(${zoom}) translate(${-offsetX * 0.25}%, ${-offsetY * 0.25}%)`"
                    >
                </div>
                <div class="flex-1 space-y-3">
                    <label class="block text-sm text-mist/80">
                        <span>Yakınlaştır</span>
                        <input type="range" min="1" max="2.5" step="0.1" x-model="zoom" @input="syncCrop()" class="mt-1 w-full accent-magenta">
                    </label>
                    <div class="flex flex-wrap gap-2">
                        <button type="button" wire:click="saveAvatar" wire:loading.attr="disabled" class="btn-magenta tap-target px-5 text-sm font-semibold">
                            <span wire:loading.remove wire:target="saveAvatar">Kırp ve kaydet</span>
                            <span wire:loading wire:target="saveAvatar">Yükleniyor…</span>
                        </button>
                        <button type="button" wire:click="$set('avatar', null)" @click="preview = null" class="btn-ghost tap-target px-4 text-sm">
                            İptal
                        </button>
                    </div>
                </div>
            </div>
        @endif
    </div>
</section>
