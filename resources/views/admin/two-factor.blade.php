<x-guest-layout>
    <div class="space-y-4">
        <h1 class="text-xl font-semibold text-gray-900">Yönetici doğrulaması</h1>
        <p class="text-sm text-gray-600">
            Panele girmeden önce authenticator uygulamasına bu hesabı ekleyin ve 6 haneli kodu girin.
        </p>

        <div id="admin-2fa-qr" class="flex justify-center rounded bg-white p-3" role="img" aria-label="Authenticator QR"></div>
        <script type="application/json" id="admin-2fa-otpauth">@json($otpauth)</script>
        <script src="{{ asset('js/qrcode-generator.js') }}"></script>
        <script>
            (function () {
                var payload = document.getElementById('admin-2fa-otpauth');
                var target = document.getElementById('admin-2fa-qr');
                if (!payload || !target || typeof qrcode !== 'function') {
                    return;
                }
                var otpauth = JSON.parse(payload.textContent || '""');
                var code = qrcode(0, 'M');
                code.addData(otpauth, 'Byte');
                code.make();
                target.innerHTML = code.createSvgTag({ cellSize: 5, margin: 12, scalable: true });
                var svg = target.querySelector('svg');
                if (svg) {
                    svg.setAttribute('width', '220');
                    svg.setAttribute('height', '220');
                    svg.removeAttribute('role');
                }
            })();
        </script>

        <div>
            <p class="text-xs font-medium text-gray-500">Gizli anahtar</p>
            <p class="break-all rounded bg-gray-100 px-3 py-2 font-mono text-sm">{{ $secret }}</p>
        </div>

        <p class="break-all text-xs text-gray-500">
            QR okunmazsa authenticator’da elle ekleyin:
            <a href="{{ $otpauth }}" class="underline">otpauth bağlantısı</a>
        </p>

        <form method="POST" action="{{ route('admin.two-factor.store') }}" class="space-y-3">
            @csrf
            <div>
                <x-input-label for="code" value="Doğrulama kodu" />
                <x-text-input id="code" name="code" type="text" inputmode="numeric" maxlength="6" class="mt-1 block w-full" required autofocus autocomplete="one-time-code" />
                <x-input-error :messages="$errors->get('code')" class="mt-2" />
            </div>
            <x-primary-button>Doğrula</x-primary-button>
        </form>
    </div>
</x-guest-layout>
