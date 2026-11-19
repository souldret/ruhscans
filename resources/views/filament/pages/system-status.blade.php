<x-filament-panels::page>
    <div class="grid gap-4 sm:grid-cols-2 xl:grid-cols-3">
        @foreach ([
            'Kuyruk' => $snapshot['queue'] ?? '—',
            'Başarısız iş' => $snapshot['failed'] ?? '—',
            'Disk' => $snapshot['disk'] ?? '—',
            'Laravel' => $snapshot['laravel'] ?? '—',
            'PHP' => $snapshot['php'] ?? '—',
        ] as $label => $value)
            <div class="rounded-xl border border-white/10 bg-white/5 p-4">
                <p class="text-sm text-gray-400">{{ $label }}</p>
                <p class="mt-1 text-lg font-semibold break-words">{{ $value }}</p>
            </div>
        @endforeach
    </div>

    <div class="mt-6 flex flex-wrap gap-2">
        <x-filament::button wire:click="refreshStatus" color="gray">Yenile</x-filament::button>
        <x-filament::button wire:click="probe('database')">Veritabanı</x-filament::button>
        <x-filament::button wire:click="probe('redis')">Redis</x-filament::button>
        <x-filament::button wire:click="probe('storage')">R2 / depolama</x-filament::button>
    </div>
</x-filament-panels::page>
