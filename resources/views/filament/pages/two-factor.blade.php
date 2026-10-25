<x-filament-panels::page>
    @php($user = filament()->auth()->user())

    @if ($user->two_factor_confirmed_at)
        <p class="text-sm text-gray-600 dark:text-gray-300">
            İki aşamalı doğrulama açık. Yönetim paneline girerken uygulama kodu istenir.
        </p>
    @elseif ($secret)
        <div class="max-w-lg space-y-4">
            <p class="text-sm text-gray-600 dark:text-gray-300">
                Bu adresi kimlik doğrulama uygulamasına ekle, sonra üretilen 6 haneli kodu onayla.
            </p>
            <code class="block break-all rounded-lg bg-gray-100 p-3 text-xs dark:bg-white/5">{{ $this->otpauth() }}</code>
            <p class="text-sm">Gizli anahtar: <strong class="font-mono">{{ $secret }}</strong></p>
            <form wire:submit="confirm" class="space-y-4">
                {{ $this->form }}
                <x-filament::button type="submit">Onayla</x-filament::button>
            </form>
        </div>
    @else
        <div class="max-w-lg space-y-3">
 <p class="text-sm text-gray-600 dark:text-gray-300">
 Yönetim paneli iki aşamalı doğrulama olmadan açılmaz. Kurulumu başlat, uygulama kodunu onayla.
 </p>
 <x-filament::button wire:click="start">Kurulumu başlat</x-filament::button>
 </div>
    @endif
</x-filament-panels::page>
