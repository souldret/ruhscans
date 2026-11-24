@if ($paginator->hasPages())
    <nav role="navigation" aria-label="Sayfalama" class="flex flex-wrap items-center justify-between gap-3">
        <p class="text-sm text-mist/60">
            Toplam <span class="font-medium text-mist">{{ $paginator->total() }}</span> kayıt
        </p>

        <div class="flex items-center gap-1.5">
            @if ($paginator->onFirstPage())
                <span class="tap-target cursor-not-allowed rounded-full border border-white/5 bg-white/[0.02] px-4 text-sm text-mist/35" aria-disabled="true">← Önceki</span>
            @else
                <a href="{{ $paginator->previousPageUrl() }}" rel="prev" class="btn-ghost tap-target px-4 text-sm">← Önceki</a>
            @endif

            <span class="px-3 text-sm font-medium text-mist/80">
                {{ $paginator->currentPage() }} / {{ $paginator->lastPage() }}
            </span>

            @if ($paginator->hasMorePages())
                <a href="{{ $paginator->nextPageUrl() }}" rel="next" class="btn-ghost tap-target px-4 text-sm">Sonraki →</a>
            @else
                <span class="tap-target cursor-not-allowed rounded-full border border-white/5 bg-white/[0.02] px-4 text-sm text-mist/35" aria-disabled="true">Sonraki →</span>
            @endif
        </div>
    </nav>
@endif
