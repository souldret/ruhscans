@if ($paginator->hasPages())
    <nav role="navigation" aria-label="Sayfalama" class="flex items-center justify-between gap-4 text-xs font-medium text-ink-muted sm:text-sm">
        @if ($paginator->onFirstPage())
            <span class="inline-flex cursor-not-allowed items-center gap-1.5 rounded-full border border-ink-line bg-white/[0.02] px-4 py-2 text-ink-faint opacity-50" aria-disabled="true">
                <svg viewBox="0 0 20 20" class="h-4 w-4" fill="none" stroke="currentColor" stroke-width="2" aria-hidden="true"><path d="M12.5 15 7.5 10l5-5" stroke-linecap="round" stroke-linejoin="round"/></svg>
                Önceki
            </span>
        @else
            <a href="{{ $paginator->previousPageUrl() }}" rel="prev" class="inline-flex items-center gap-1.5 rounded-full border border-ink-line bg-white/[0.04] px-4 py-2 text-ink-text transition hover:border-ink-line-strong hover:bg-white/[0.08] hover:text-white">
                <svg viewBox="0 0 20 20" class="h-4 w-4" fill="none" stroke="currentColor" stroke-width="2" aria-hidden="true"><path d="M12.5 15 7.5 10l5-5" stroke-linecap="round" stroke-linejoin="round"/></svg>
                Önceki
            </a>
        @endif

        <span class="rounded-full border border-ink-line bg-ink-deep/60 px-3.5 py-1.5 font-mono text-xs text-ink-text">
            {{ $paginator->firstItem() }}–{{ $paginator->lastItem() }} <span class="text-ink-faint">/</span> {{ $paginator->total() }}
        </span>

        @if ($paginator->hasMorePages())
            <a href="{{ $paginator->nextPageUrl() }}" rel="next" class="inline-flex items-center gap-1.5 rounded-full border border-ink-line bg-white/[0.04] px-4 py-2 text-ink-text transition hover:border-ink-line-strong hover:bg-white/[0.08] hover:text-white">
                Sonraki
                <svg viewBox="0 0 20 20" class="h-4 w-4" fill="none" stroke="currentColor" stroke-width="2" aria-hidden="true"><path d="m7.5 15 5-5-5-5" stroke-linecap="round" stroke-linejoin="round"/></svg>
            </a>
        @else
            <span class="inline-flex cursor-not-allowed items-center gap-1.5 rounded-full border border-ink-line bg-white/[0.02] px-4 py-2 text-ink-faint opacity-50" aria-disabled="true">
                Sonraki
                <svg viewBox="0 0 20 20" class="h-4 w-4" fill="none" stroke="currentColor" stroke-width="2" aria-hidden="true"><path d="m7.5 15 5-5-5-5" stroke-linecap="round" stroke-linejoin="round"/></svg>
            </span>
        @endif
    </nav>
@endif
