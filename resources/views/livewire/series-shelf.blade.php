<div class="flex flex-wrap items-center gap-3">
    @auth
        <button type="button" wire:click="toggle" class="tap-target {{ $saved ? 'btn-magenta' : 'btn-wine' }}">
            {{ $saved ? 'Kütüphanede' : 'Kütüphaneye ekle' }}
        </button>
        <div class="flex items-center" role="group" aria-label="Puan">
            @foreach (range(1, 5) as $star)
                <button type="button" wire:click="rate({{ $star }})" class="tap-target rounded-full text-xl transition-colors {{ ($rating ?? 0) >= $star ? 'text-gold' : 'text-mist/35 hover:text-gold/70' }}" aria-label="{{ $star }} puan">★</button>
            @endforeach
        </div>
    @else
        <a class="btn-ghost tap-target" href="{{ route('login') }}">Kaydetmek için gir</a>
    @endauth
</div>
