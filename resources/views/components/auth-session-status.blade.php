@props(['status'])

@if ($status)
    <div {{ $attributes->merge(['class' => 'alert-ok mb-4']) }}>
        {{ $status }}
    </div>
@endif
