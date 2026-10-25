@props(['href' => null, 'variant' => 'accent', 'size' => 'md'])

@php
    $variants = match ($variant) {
        'gold' => 'bg-gold text-ink shadow-glow-gold hover:brightness-110',
        'ghost' => 'border border-ink-line bg-white/[0.04] text-ink-text hover:border-ink-line-strong hover:bg-white/[0.08]',
        'danger' => 'border border-danger/40 bg-danger/10 text-danger hover:bg-danger/20',
        'plain' => 'text-ink-muted hover:bg-white/[0.06] hover:text-ink-text',
        default => 'bg-accent text-white shadow-glow hover:brightness-110',
    };

    $sizes = match ($size) {
        'sm' => 'px-3.5 py-1.5 text-xs',
        'lg' => 'px-6 py-3 text-base',
        default => 'px-5 py-2.5 text-sm',
    };

    $classes = "inline-flex items-center justify-center gap-2 rounded-full font-semibold transition duration-base ease-out active:scale-[0.97] disabled:pointer-events-none disabled:opacity-60 focus-visible:outline $sizes $variants";
@endphp

@if ($href)
    <a href="{{ $href }}" {{ $attributes->merge(['class' => $classes]) }}>
        {{ $slot }}
    </a>
@else
    <button {{ $attributes->merge(['type' => 'button', 'class' => $classes]) }}>
        {{ $slot }}
    </button>
@endif
