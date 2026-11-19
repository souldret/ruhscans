@props(['title', 'body'])

<div {{ $attributes->merge(['class' => 'glass rounded-3xl px-6 py-12 text-center']) }}>
    <x-stone class="mx-auto h-8 w-8" />
    <h2 class="mt-4 font-display text-2xl">{{ $title }}</h2>
    <p class="mx-auto mt-2 max-w-md text-sm text-mist/70">{{ $body }}</p>
    @isset($action)
        <div class="mt-6">{{ $action }}</div>
    @endisset
</div>
