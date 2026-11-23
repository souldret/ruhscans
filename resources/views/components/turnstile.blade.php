@props([
    'name' => 'turnstile_token',
    'wireModel' => null,
])

@php
    $enabled = (bool) config('turnstile.enabled');
    $siteKey = (string) config('turnstile.site_key');
@endphp

@if ($enabled && $siteKey !== '')
    @once
        <script src="https://challenges.cloudflare.com/turnstile/v0/api.js?render=explicit" async defer></script>
    @endonce

    <div
        wire:ignore
        x-data="{
            widgetId: null,
            init() {
                const input = this.$refs.token;
                const setToken = (value) => {
                    input.value = value;
                    input.dispatchEvent(new Event('input', { bubbles: true }));
                };
                const render = () => {
                    if (typeof window.turnstile === 'undefined') {
                        setTimeout(render, 100);
                        return;
                    }
                    this.widgetId = window.turnstile.render(this.$refs.widget, {
                        sitekey: @js($siteKey),
                        theme: 'dark',
                        callback: (token) => setToken(token),
                        'expired-callback': () => setToken(''),
                        'error-callback': () => {
                            setToken('');
                            if (this.widgetId !== null) {
                                window.turnstile.reset(this.widgetId);
                            }
                        },
                    });
                };
                render();
                window.addEventListener('turnstile-reset', () => {
                    setToken('');
                    if (this.widgetId !== null && window.turnstile) {
                        window.turnstile.reset(this.widgetId);
                    }
                });
            }
        }"
        class="turnstile-wrap flex min-h-[65px] w-full justify-center overflow-hidden rounded-2xl border border-white/5 bg-ink/60 p-3"
    >
        <input
            x-ref="token"
            type="hidden"
            name="{{ $name }}"
            @if ($wireModel !== null) wire:model.defer="{{ $wireModel }}" @endif
        >
        <div x-ref="widget" class="cf-turnstile" data-sitekey="{{ $siteKey }}" data-theme="dark"></div>
    </div>
@endif
