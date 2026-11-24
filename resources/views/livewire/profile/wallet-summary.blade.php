<div class="space-y-6">
    {{-- Son 10 hareket --}}
    <section class="glass rounded-3xl p-5 sm:p-7" aria-labelledby="cuzdan-hareketler">
        <div class="flex flex-wrap items-center justify-between gap-3">
            <div>
                <h2 id="cuzdan-hareketler" class="font-display text-2xl text-mist">Son cüzdan hareketleri</h2>
                <p class="mt-1 text-sm text-mist/65">Son 10 işlem gösteriliyor.</p>
            </div>
            <a href="{{ route('wallet.show') }}" class="btn-ghost tap-target px-4 text-sm text-gold">
                Tümünü gör →
            </a>
        </div>

        <ul class="mt-4 divide-y divide-white/10">
            @forelse ($transactions as $tx)
                <li class="flex flex-wrap items-center justify-between gap-2 py-3 text-base">
                    <div class="min-w-0">
                        <span class="font-semibold {{ $tx->yon->value === 'kredi' ? 'text-gold' : 'text-magenta' }}">
                            {{ $tx->yon->value === 'kredi' ? '+' : '−' }}{{ $tx->miktar }}
                        </span>
                        <span class="ml-2 text-mist/85">{{ $tx->sebep }}</span>
                    </div>
                    <span class="text-sm text-mist/60">
                        Bakiye: {{ $tx->bakiye_sonrasi }} · {{ $tx->created_at->timezone(config('app.timezone'))->format('d.m.Y H:i') }}
                    </span>
                </li>
            @empty
                <li class="py-6 text-center text-base text-mist/60">Henüz cüzdan hareketin yok.</li>
            @endforelse
        </ul>
    </section>

    {{-- Açılan bölümler --}}
    <section class="glass rounded-3xl p-5 sm:p-7" aria-labelledby="acilan-bolumler">
        <h2 id="acilan-bolumler" class="font-display text-2xl text-mist">Açılan bölümler</h2>
        <p class="mt-1 text-sm text-mist/65">Ruh taşıyla açtığın bölümlere istediğin zaman tekrar dönebilirsin.</p>

        <ul class="mt-4 divide-y divide-white/10">
            @forelse ($unlocks as $unlock)
                @php
                    $chapter = $unlock->chapter;
                    $series = $chapter?->series;
                @endphp
                <li class="flex items-center justify-between gap-3 py-3">
                    <div class="min-w-0">
                        <p class="truncate text-base font-medium text-mist">
                            {{ $series?->title ?? 'Kaldırılmış seri' }}
                        </p>
                        <p class="text-sm text-mist/60">
                            @if ($chapter)
                                Bölüm {{ $chapter->number }} ·
                            @endif
                            {{ $unlock->created_at?->timezone(config('app.timezone'))->format('d.m.Y H:i') }}
                        </p>
                    </div>
                    @if ($series && $chapter)
                        <a href="{{ route('reader.show', [$series, $chapter->number]) }}" class="btn-ghost tap-target shrink-0 px-4 text-sm">
                            Oku
                        </a>
                    @endif
                </li>
            @empty
                <li class="py-6 text-center text-base text-mist/60">Henüz açılmış ücretli bölüm yok.</li>
            @endforelse
        </ul>
    </section>
</div>
