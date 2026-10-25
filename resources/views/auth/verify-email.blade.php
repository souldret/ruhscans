<x-guest-layout>
    <div class="mb-6">
        <h1 class="font-display text-2xl font-bold tracking-tight">E-postanı Doğrula</h1>
        <p class="mt-2 text-sm leading-6 text-ink-muted">
            Kayıt olduğun için teşekkürler! Başlamadan önce sana gönderdiğimiz bağlantıya tıklayarak e-posta adresini doğrulayabilir misin? E-posta gelmediyse yenisini gönderebiliriz.
        </p>
    </div>

    @if (session('status') == 'verification-link-sent')
        <p class="alert-ok mb-5" role="status">
            Kayıt sırasında belirttiğin e-posta adresine yeni bir doğrulama bağlantısı gönderildi.
        </p>
    @endif

    <div class="space-y-4">
        <form method="POST" action="{{ route('verification.send') }}">
            @csrf
            <x-primary-button class="w-full">
                Doğrulama e-postasını tekrar gönder
            </x-primary-button>
        </form>

        <form method="POST" action="{{ route('logout') }}" class="text-center">
            @csrf
            <button type="submit" class="text-xs text-ink-muted hover:text-ink-text hover:underline">
                Çıkış yap
            </button>
        </form>
    </div>
</x-guest-layout>
