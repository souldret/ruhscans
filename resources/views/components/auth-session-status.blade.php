@props(['status'])

@if ($status)
    <div {{ $attributes->merge(['class' => 'mb-5 rounded-2xl border border-gold/30 bg-gold/10 px-4 py-3 text-base text-gold']) }}>
        {{ $status }}
    </div>
@endif
