<img
    src="{{ $src }}"
    @if ($srcset !== '') srcset="{{ $srcset }}" sizes="(min-width: 640px) 160px, 144px" @endif
    alt="{{ $game->title }} afişi"
    width="120"
    height="160"
    loading="lazy"
    decoding="async"
    {{ $attributes->merge(['class' => 'h-full w-full object-cover']) }}
>
