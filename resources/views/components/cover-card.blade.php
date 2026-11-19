@props(['series', 'compact' => false])

<a href="{{ route('series.show', $series) }}" class="cover-tilt group block w-36 shrink-0 sm:w-44" style="perspective: 800px">
    <article class="cover-frame relative overflow-hidden rounded-2xl bg-surface">
        @if ($series->cover_path)
            <img
                src="{{ \Illuminate\Support\Facades\Storage::disk(config('content.image_disk', 'public'))->url($series->cover_path) }}"
                alt=""
                width="320"
                height="480"
                loading="lazy"
                decoding="async"
                class="cover-art h-full w-full object-cover"
            >
        @else
            <div class="cover-art flex h-full w-full items-end bg-gradient-to-br from-wine via-surface to-ink p-3">
                <span class="font-display text-lg leading-tight text-mist">{{ $series->title }}</span>
            </div>
        @endif
        <span class="cover-sheen pointer-events-none absolute inset-0"></span>
        <div class="absolute left-2 top-2 flex flex-wrap gap-1">
            @if (optional($series->created_at)->gt(now()->subDays(14)))
                <span class="badge-new">Yeni</span>
            @endif
            @if ($series->status === \App\Enums\SeriesStatus::Tamamlandi)
                <span class="badge-done">Tamamlandı</span>
            @endif
            @if (($series->chapters_count ?? 0) > 0 && $series->relationLoaded('chapters') && $series->chapters->contains('vip_ucretsiz', true))
                <span class="badge-vip">VIP</span>
            @endif
        </div>
    </article>
    @unless ($compact)
        <h3 class="mt-2 line-clamp-2 text-sm text-mist">{{ $series->title }}</h3>
        <p class="mt-1 text-xs text-mist/60">{{ $series->type->label() }}</p>
    @endunless
</a>
