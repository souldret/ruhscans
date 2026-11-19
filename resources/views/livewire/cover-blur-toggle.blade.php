<div x-data>
    <button
        type="button"
        class="btn-ghost !px-3 !py-1.5 text-xs"
        wire:click="setBlur(! $wire.blur)"
        x-on:click="$dispatch('blur-changed', { blur: ! $wire.blur })"
        :aria-pressed="(!blur).toString()"
        aria-label="Kapak bulanıklığını aç veya kapat"
    >
        <span x-text="blur ? 'Blur açık' : 'Blur kapalı'"></span>
    </button>
</div>
