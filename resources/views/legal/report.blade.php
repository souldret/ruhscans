<x-night-layout :seo="$seo">
 <article class="mx-auto max-w-2xl space-y-4 px-4 py-10 text-sm leading-6 text-mist/80">
 <h1 class="font-display text-4xl text-mist">
 {{ $kind === 'dmca' ? 'Telif bildirimi' : 'Şikayet' }}
 </h1>
 <p class="text-xs uppercase tracking-wide text-gold">Taslak metin · hukuki inceleme bekliyor · 18+</p>

        @if (session('notice'))
            <p class="rounded-md bg-green-50 px-3 py-2 text-green-800">{{ session('notice') }}</p>
        @endif

        <form method="POST" action="{{ route('legal.notice.store') }}" class="space-y-4">
            @csrf
            <input type="hidden" name="kind" value="{{ $kind }}">
            <input type="hidden" name="series_slug" value="{{ $series?->slug }}">
            <input type="hidden" name="chapter_number" value="{{ $chapter?->number }}">

            <div class="absolute -left-[9999px] h-0 w-0 overflow-hidden" aria-hidden="true">
                <label for="website">Web sitesi</label>
                <input id="website" type="text" name="website" tabindex="-1" autocomplete="off">
            </div>

            <div>
                <label class="block text-xs text-gray-500" for="email">E-posta</label>
                <input id="email" name="email" type="email" required maxlength="255" value="{{ old('email', auth()->user()?->email) }}" class="mt-1 w-full rounded-md border-gray-300">
                @error('email') <p class="mt-1 text-red-600">{{ $message }}</p> @enderror
            </div>

            <div>
                <label class="block text-xs text-gray-500" for="url">İlgili adres</label>
                <input id="url" name="url" type="url" maxlength="500" value="{{ old('url', $url) }}" class="mt-1 w-full rounded-md border-gray-300">
                @error('url') <p class="mt-1 text-red-600">{{ $message }}</p> @enderror
            </div>

            @if ($series)
                <p>Seri: {{ $series->title }}@if ($chapter) · bölüm {{ $chapter->number }}@endif</p>
            @endif

            <div>
                <label class="block text-xs text-gray-500" for="reason">Açıklama</label>
                <textarea id="reason" name="reason" required minlength="20" maxlength="5000" rows="6" class="mt-1 w-full rounded-md border-gray-300">{{ old('reason') }}</textarea>
                @error('reason') <p class="mt-1 text-red-600">{{ $message }}</p> @enderror
            </div>

            <div>
                <x-turnstile />
                @error('turnstile_token') <p class="mt-1 text-red-600">{{ $message }}</p> @enderror
            </div>

            <button type="submit" class="rounded-2xl bg-magenta px-4 py-2 text-white">Gönder</button>
 </form>
 </article>
</x-night-layout>
