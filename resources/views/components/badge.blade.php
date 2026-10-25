@props(['tone' => 'free'])

@php
    $classes = match ($tone) {
        'vip' => 'bg-gold text-[#1A1200] shadow-[0_4px_14px_-4px_rgba(245,165,36,0.7)]',
 'ok' => 'bg-[#3DDC97] text-[#06281A]',
 'ok' => 'bg-ok text-[#04210f]',
        'patch' => 'border border-white/15 bg-black/50 text-white backdrop-blur',
        'muted' => 'border border-ink-line bg-white/5 text-ink-muted',
        default => 'bg-[#3DDC97] text-[#06281A] shadow-[0_4px_14px_-6px_rgba(61,220,151,0.7)]',
    };
@endphp

<span {{ $attributes->merge(['class' => "inline-flex items-center gap-1 rounded-full px-2.5 py-1 text-[10px] font-bold uppercase leading-none tracking-[0.08em] $classes"]) }}>
    {{ $slot }}
</span>
