document.addEventListener('alpine:init', () => {
    Alpine.data('nightShell', (config) => ({
        blur: config.blur,
        stealth: config.stealth,
        panicUrl: config.panic,
        toasts: [],
        lastEsc: 0,
        init() {
            document.documentElement.classList.toggle('covers-open', !this.blur);

            window.addEventListener('prefs-updated', (event) => {
                this.blur = !!event.detail.blur;
                this.stealth = !!event.detail.stealth;
                this.panicUrl = event.detail.panic;
                document.documentElement.classList.toggle('covers-open', !this.blur);
            });

            window.addEventListener('toast', (event) => this.push(event.detail.message));

            window.addEventListener('keydown', (event) => {
                if (event.key !== 'Escape' || event.repeat) {
                    return;
                }

                const now = Date.now();

                if (now - this.lastEsc < 700) {
                    this.panic();
                }

                this.lastEsc = now;
            });
        },
        setBlur(value) {
            this.blur = !!value;
            document.documentElement.classList.toggle('covers-open', !this.blur);
        },
        push(message) {
            const id = Date.now() + Math.random();
            this.toasts.push({ id, message });
            setTimeout(() => {
                this.toasts = this.toasts.filter((toast) => toast.id !== id);
            }, 2800);
        },
        panic() {
            document.title = 'Yeni sekme';
            const icon = document.querySelector("link[rel='icon']");

            if (icon) {
                icon.setAttribute('href', '/favicon-neutral.svg');
            }

            window.location.replace(this.panicUrl || 'https://www.google.com');
        },
    }));
});
