@props(['href' => null, 'variant' => 'accent'])

@php
    $classes = match ($variant) {
        'gold' => 'bg-gold text-ink',
        'ghost' => 'border border-ink-line bg-white/5 text-ink-text hover:bg-white/10',
        default => 'bg-accent text-white',
    };
@endphp

@if ($href)
    <a href="{{ $href }}" {{ $attributes->merge(['class' => "inline-flex items-center justify-center gap-2 rounded-full px-5 py-2.5 text-sm font-semibold transition duration-base ease-out focus-visible:outline $classes"]) }}>
        {{ $slot }}
    </a>
@else
    <button {{ $attributes->merge(['type' => 'button', 'class' => "inline-flex items-center justify-center gap-2 rounded-full px-5 py-2.5 text-sm font-semibold transition duration-base ease-out focus-visible:outline $classes"]) }}>
        {{ $slot }}
    </button>
@endif
