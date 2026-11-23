<x-night-layout :seo="$seo">
    <article class="mx-auto max-w-2xl px-4 pb-16 pt-8">
        <p class="text-sm font-semibold uppercase tracking-[0.2em] text-gold">Ruhscans · 18+</p>
        <h1 class="mt-2 font-display text-4xl leading-tight text-mist">
            {{ $kind === 'dmca' ? 'Telif bildirimi' : 'Şikayet' }}
        </h1>
        <p class="mt-3 rounded-2xl border border-gold/30 bg-gold/10 px-4 py-3 text-base text-gold">Taslak metin · hukuki inceleme bekliyor · 18+</p>

        @if (session('notice'))
            <p class="mt-5 rounded-2xl border border-gold/30 bg-gold/10 px-4 py-3 text-base text-gold">{{ session('notice') }}</p>
        @endif

        <form method="POST" action="{{ route('legal.notice.store') }}" class="glass mt-6 space-y-5 rounded-3xl p-6 sm:p-8">
            @csrf
            <input type="hidden" name="kind" value="{{ $kind }}">
            <input type="hidden" name="series_slug" value="{{ $series?->slug }}">
            <input type="hidden" name="chapter_number" value="{{ $chapter?->number }}">

            <div class="absolute -left-[9999px] h-0 w-0 overflow-hidden" aria-hidden="true">
                <label for="website">Web sitesi</label>
                <input id="website" type="text" name="website" tabindex="-1" autocomplete="off">
            </div>

            <div>
                <label class="mb-2 block text-base font-medium text-mist" for="email">E-posta</label>
                <input id="email" name="email" type="email" required maxlength="255" value="{{ old('email', auth()->user()?->email) }}" class="h-14 w-full rounded-2xl border border-white/10 bg-ink px-4 text-base text-mist placeholder:text-mist/40 focus:border-magenta focus:ring-magenta">
                @error('email') <p class="mt-2 text-base text-[#FF9CC4]">{{ $message }}</p> @enderror
            </div>

            <div>
                <label class="mb-2 block text-base font-medium text-mist" for="url">İlgili adres</label>
                <input id="url" name="url" type="url" maxlength="500" value="{{ old('url', $url) }}" class="h-14 w-full rounded-2xl border border-white/10 bg-ink px-4 text-base text-mist placeholder:text-mist/40 focus:border-magenta focus:ring-magenta">
                @error('url') <p class="mt-2 text-base text-[#FF9CC4]">{{ $message }}</p> @enderror
            </div>

            @if ($series)
                <p class="text-base text-mist/75">Seri: {{ $series->title }}@if ($chapter) · bölüm {{ $chapter->number }}@endif</p>
            @endif

            <div>
                <label class="mb-2 block text-base font-medium text-mist" for="reason">Açıklama</label>
                <textarea id="reason" name="reason" required minlength="20" maxlength="5000" rows="6" class="w-full rounded-2xl border border-white/10 bg-ink px-4 py-3 text-base text-mist placeholder:text-mist/40 focus:border-magenta focus:ring-magenta">{{ old('reason') }}</textarea>
                @error('reason') <p class="mt-2 text-base text-[#FF9CC4]">{{ $message }}</p> @enderror
            </div>

            <div>
                <x-turnstile />
                @error('turnstile_token') <p class="mt-2 text-base text-[#FF9CC4]">{{ $message }}</p> @enderror
            </div>

            <button type="submit" class="tap-target inline-flex w-full items-center justify-center rounded-full bg-magenta px-6 py-4 text-lg font-semibold text-ink hover:shadow-glow">Gönder</button>
        </form>
    </article>
</x-night-layout>
