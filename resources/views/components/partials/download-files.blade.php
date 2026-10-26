<div x-data="fileTabs('{{ $tabs->keys()->first() }}')" class="mt-5">
    <div class="flex flex-wrap gap-1.5 rounded-full border border-ink-line bg-ink-deep/50 p-1" role="tablist">
        @foreach ($tabs as $key => $label)
            <button type="button" role="tab" @click="select('{{ $key }}')"
                class="flex-1 rounded-full px-3 py-1.5 text-xs font-semibold transition"
                :class="tabClass('{{ $key }}')">{{ $label }}</button>
        @endforeach
    </div>
    @foreach ($tabs as $key => $label)
        <ul @if (! $loop->first) x-show="active('{{ $key }}')" x-cloak @endif class="mt-5 space-y-3">
            @foreach ($files[$key] as $file)
                <li class="flex items-center justify-between gap-3 rounded-2xl border border-ink-line bg-ink-deep/60 p-4">
                    <div class="min-w-0">
                        <p class="truncate text-sm font-semibold">{{ $file->label }}</p>
                        <p class="mt-0.5 text-xs text-ink-muted">{{ $file->sizeLabel() }}</p>
                    </div>
                    <x-button :href="route('downloads.show', $file)" size="sm">İndir</x-button>
                </li>
            @endforeach
        </ul>
    @endforeach
</div>
