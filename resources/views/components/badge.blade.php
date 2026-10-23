@props(['tone' => 'free'])

@php
    $classes = match ($tone) {
        'vip' => 'bg-gold text-ink',
        'patch' => 'border border-white/30 bg-black/40 text-white backdrop-blur',
        default => 'bg-ok text-ink',
    };
@endphp

<span {{ $attributes->merge(['class' => "inline-flex items-center gap-1 rounded-full px-2.5 py-1 text-[11px] font-semibold uppercase tracking-wide $classes"]) }}>
    {{ $slot }}
</span>
