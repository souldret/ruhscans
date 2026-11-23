@props(['value'])

<label {{ $attributes->merge(['class' => 'mb-2 block text-base font-medium text-mist']) }}>
    {{ $value ?? $slot }}
</label>
