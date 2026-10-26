<img
 src="{{ $src }}"
 @if ($srcset !== '') srcset="{{ $srcset }}" sizes="{{ $sizes }}" @endif
 alt="{{ $game->title }} afişi"
 width="{{ $width }}"
 height="{{ $height }}"
 @if ($lazy) loading="lazy" @endif
 decoding="async"
 {{ $attributes->merge(['class' => 'h-full w-full object-cover']) }}
>
