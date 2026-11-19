<x-filament-panels::page>
    <form wire:submit="generate" class="space-y-6">
        {{ $this->form }}

        <div class="rounded-xl border border-amber-400/40 bg-amber-400/10 p-4 text-sm">
            Düz kodlar üretilince yalnızca bir kez CSV olarak iner. Sayfa yenilenince tekrar indirilemez ve dosya saklanmaz. 10.000 ve üzeri üretim kuyrukta hazırlanır.
        </div>

        <x-filament::button type="submit">Üret</x-filament::button>
    </form>

    @if ($notice)
        <div class="mt-6 space-y-3 rounded-xl border border-white/10 p-4">
            <p>{{ $notice }}</p>
            @if ($downloadUrl)
                <x-filament::button tag="a" :href="$downloadUrl">CSV indir</x-filament::button>
            @endif
        </div>
    @endif
</x-filament-panels::page>
