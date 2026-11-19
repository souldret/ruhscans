<div class="flex flex-wrap items-center gap-3">
    @auth
        <button type="button" wire:click="toggle" class="{{ $saved ? 'btn-magenta' : 'btn-wine' }}">
            {{ $saved ? 'Kütüphanede' : 'Kütüphaneye ekle' }}
        </button>
        <div class="flex items-center gap-1" role="group" aria-label="Puan">
            @foreach (range(1, 5) as $star)
                <button type="button" wire:click="rate({{ $star }})" class="text-lg {{ ($rating ?? 0) >= $star ? 'text-gold' : 'text-mist/30' }}" aria-label="{{ $star }} puan">★</button>
            @endforeach
        </div>
    @else
        <a class="btn-magenta" href="{{ route('login') }}">Kaydetmek için gir</a>
    @endauth
</div>
