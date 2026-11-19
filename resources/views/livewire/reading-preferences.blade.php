<div>
    <div class="mx-auto max-w-xl px-4 py-8">
        <h1 class="font-display text-4xl">Okuma tercihleri</h1>
        <p class="mt-2 text-sm text-mist/70">Bulanıklık, gizli mod ve panik adresi bu hesapta saklanır.</p>

        <form wire:submit="save" class="glass mt-6 space-y-5 rounded-3xl p-5">
            <label class="flex items-center justify-between gap-4">
                <span>Kapakları bulanık tut</span>
                <input type="checkbox" wire:model="kapak_blur" class="rounded border-white/20 bg-ink text-magenta">
            </label>
            <label class="flex items-center justify-between gap-4">
                <span>Gizli mod</span>
                <input type="checkbox" wire:model="gizli_mod" class="rounded border-white/20 bg-ink text-magenta">
            </label>
            <p class="text-xs text-mist/60">Gizli mod kapakları hover ile de açmaz. Panik tuşu ya da Esc'ye iki kez basmak seni aşağıdaki adrese götürür ve sekme başlığını nötrler.</p>
            <label class="block">
                <span class="text-sm">Panik hedefi</span>
                <input type="url" wire:model="panik_url" class="mt-1 w-full rounded-2xl border-white/10 bg-ink text-mist" required>
                @error('panik_url') <span class="mt-1 block text-xs text-magenta">{{ $message }}</span> @enderror
            </label>
            <fieldset>
                <legend class="text-sm">Etiket kara listesi</legend>
                <div class="mt-3 flex flex-wrap gap-2">
                    @foreach ($tags as $tag)
                        <label class="btn-ghost !py-1 has-[:checked]:bg-wine">
                            <input type="checkbox" class="sr-only" value="{{ $tag->slug }}" wire:model="etiket_karaliste">
                            {{ $tag->name }}
                        </label>
                    @endforeach
                </div>
            </fieldset>
            <button type="submit" class="btn-magenta">Kaydet</button>
        </form>

        <div class="mt-8">
            <a class="text-sm text-mist/70 underline" href="{{ route('profile') }}">Hesap ve parola</a>
        </div>
    </div>
</div>
