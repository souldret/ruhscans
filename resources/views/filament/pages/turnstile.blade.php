<x-filament-panels::page>
    <form wire:submit="save" class="max-w-xl space-y-4">
        {{ $this->form }}
        <x-filament::button type="submit">Kaydet</x-filament::button>
    </form>
    <p class="mt-4 max-w-xl text-sm text-gray-500">
        Anahtarları Cloudflare hesabından, Turnstile bölümünden alırsın. Kaydettikten sonra giriş ve kayıt formunda kutu görünür.
    </p>
</x-filament-panels::page>
