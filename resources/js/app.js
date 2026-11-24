document.addEventListener('alpine:init', () => {
    Alpine.data('nightShell', (config) => ({
        blur: config.blur,
        stealth: config.stealth,
        neutralTitle: !!config.neutralTitle,
        originalTitle: document.title,
        panicUrl: config.panic,
        toasts: [],
        lastEsc: 0,
        installAvailable: false,
        init() {
            document.documentElement.classList.toggle('covers-open', !this.blur);
            this.applyTabDisguise();

            window.addEventListener('prefs-updated', (event) => {
                this.blur = !!event.detail.blur;
                this.stealth = !!event.detail.stealth;
                if (typeof event.detail.neutralTitle !== 'undefined') {
                    this.neutralTitle = !!event.detail.neutralTitle;
                    this.applyTabDisguise();
                }
                this.panicUrl = event.detail.panic;
                document.documentElement.classList.toggle('covers-open', !this.blur);
            });

            window.addEventListener('toast', (event) => this.push(event.detail.message));

            window.addEventListener('pwa-install-ready', () => { this.installAvailable = true; });
            window.addEventListener('pwa-installed', () => { this.installAvailable = false; });

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
        applyTabDisguise() {
            const icon = document.querySelector("link[rel='icon']");
            if (this.neutralTitle) {
                document.title = 'Yeni sekme';
                if (icon) icon.setAttribute('href', '/favicon-neutral.svg');
            } else {
                document.title = this.originalTitle;
                if (icon) icon.setAttribute('href', '/favicon.svg');
            }
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

    Alpine.data('reader', (boot) => ({
        mode: boot.mode || 'webtoon',
        stealth: !!boot.stealth,
        locked: !!boot.locked,
        pages: [],
        watermark: null,
        index: 0,
        focus: false,
        chrome: true,
        settingsOpen: false,
        swiped: false,
        atEnd: false,
        nextReady: null,
        touchX: 0,
        progressTimer: null,
        title: boot.title,
        seriesTitle: boot.seriesTitle,
        nextUrl: boot.nextUrl,
        previousUrl: boot.previousUrl,
        seriesUrl: boot.seriesUrl,
        panicUrl: boot.panicUrl,
        async init() {
            document.body.classList.add('reader-open');

            if (this.locked) {
                return;
            }

            const stored = this.stealth ? null : localStorage.getItem(this.storageKey());
            const start = stored ? Number(stored) : Number(boot.startPage || 1);
            this.pages = await this.load(boot.pagesUrl);

            if (!this.pages.length) {
                return;
            }

            const found = this.pages.findIndex((page) => page.order >= start);
            this.index = found >= 0 ? found : 0;
            this.prefetch(this.index);
            this.warmNext();

            if (this.mode !== 'webtoon') {
                this.mark(this.current.order);
            }
        },
        destroy() {
            document.body.classList.remove('reader-open');
        },
        get current() {
            return this.pages[this.index] || null;
        },
        get progressPercent() {
            if (!this.pages.length) {
                return 0;
            }

            return Math.round(((this.index + 1) / this.pages.length) * 100);
        },
        storageKey() {
            return `ruhscans:okuma:${boot.pagesUrl}`;
        },
        async load(url) {
            const response = await fetch(url, { headers: { Accept: 'application/json' } });
            const body = await response.json();

            if (body.locked) {
                this.locked = true;

                return [];
            }

            this.watermark = body.watermark || null;

            return body.pages || [];
        },
        seen(order) {
            if (this.mode !== 'webtoon') {
                return;
            }

            this.mark(order);
            const position = this.pages.findIndex((page) => page.order === order);

            if (position >= 0) {
                this.prefetch(position);
                this.atEnd = position >= this.pages.length - 1;
            }
        },
        prefetch(position) {
            this.pages.slice(position, position + 2).forEach((page) => {
                const image = new Image();
                image.src = page.url;
            });
        },
        async warmNext() {
            if (!boot.nextPagesUrl) {
                return;
            }

            try {
                const pages = await this.load(boot.nextPagesUrl);
                this.nextReady = pages.slice(0, 2);
                this.nextReady.forEach((page) => {
                    const image = new Image();
                    image.src = page.url;
                });
            } catch (error) {
                this.nextReady = null;
            }
        },
        retry(page, event) {
            const attempt = page.attempt || 0;

            if (attempt >= 3) {
                return;
            }

            page.attempt = attempt + 1;
            const wait = 400 * page.attempt;
            setTimeout(() => {
                const glue = page.url.includes('?') ? '&' : '?';
                event.target.src = `${page.url}${glue}yeniden=${page.attempt}`;
            }, wait);
        },
        mark(order) {
            this.atEnd = this.pages.length > 0 && order >= this.pages[this.pages.length - 1].order;

            if (this.stealth) {
                return;
            }

            if (!boot.progressUrl) {
                localStorage.setItem(this.storageKey(), String(order));

                return;
            }

            clearTimeout(this.progressTimer);
            this.progressTimer = setTimeout(() => this.push(order), 800);
        },
        async push(order) {
            const token = document.querySelector('meta[name="csrf-token"]')?.content;

            await fetch(boot.progressUrl, {
                method: 'POST',
                headers: {
                    Accept: 'application/json',
                    'Content-Type': 'application/json',
                    'X-CSRF-TOKEN': token || '',
                },
                body: JSON.stringify({ page: order }),
            });
        },
        async saveMode() {
            if (this.stealth || !boot.modeUrl) {
                if (!this.stealth) {
                    localStorage.setItem('ruhscans:okuma-modu', this.mode);
                }

                return;
            }

            const token = document.querySelector('meta[name="csrf-token"]')?.content;

            await fetch(boot.modeUrl, {
                method: 'POST',
                headers: {
                    Accept: 'application/json',
                    'Content-Type': 'application/json',
                    'X-CSRF-TOKEN': token || '',
                },
                body: JSON.stringify({ mode: this.mode }),
            });
        },
        step(direction) {
            if (this.mode === 'webtoon') {
                const reduceMotion = window.matchMedia('(prefers-reduced-motion: reduce)').matches;
                window.scrollBy({ top: direction * window.innerHeight * 0.85, behavior: reduceMotion ? 'auto' : 'smooth' });

                return;
            }

            const forward = this.mode === 'rtl' ? -1 : 1;
            const next = this.index + direction * forward;
            this.index = Math.min(this.pages.length - 1, Math.max(0, next));

            if (this.current) {
                this.mark(this.current.order);
                this.prefetch(this.index);
            }
        },
        toggleFocus() {
            this.focus = !this.focus;

            if (this.focus) {
                this.chrome = false;
                this.settingsOpen = false;
            } else {
                this.chrome = true;
            }
        },
        toggleChrome(event) {
            if (event?.target?.closest?.('a, button, select, input')) {
                return;
            }

            this.chrome = !this.chrome;
            this.settingsOpen = false;
        },
        zone(direction) {
            if (this.swiped) {
                return;
            }

            this.step(direction);
        },
        goChapter(url) {
            if (url) {
                window.location.href = url;
            }
        },
        panic() {
            document.title = 'Yeni sekme';
            const icon = document.querySelector("link[rel='icon']");

            if (icon) {
                icon.setAttribute('href', '/favicon-neutral.svg');
            }

            window.location.replace(this.panicUrl || 'https://www.google.com');
        },
        onKey(event) {
            if (event.target.closest('input, textarea, select')) {
                return;
            }

            if (event.key === 'f') {
                this.focus = !this.focus;
            }

            if (event.key === 'c') {
                this.chrome = !this.chrome;
            }

            if (event.key === 'ArrowRight' || event.key === 'ArrowDown') {
                event.preventDefault();
                this.step(1);
            }

            if (event.key === 'ArrowLeft' || event.key === 'ArrowUp') {
                event.preventDefault();
                this.step(-1);
            }
        },
        touchStart(event) {
            this.touchX = event.changedTouches[0]?.clientX || 0;
            this.swiped = false;
        },
        touchEnd(event) {
            const delta = (event.changedTouches[0]?.clientX || 0) - this.touchX;

            if (Math.abs(delta) < 48) {
                return;
            }

            this.swiped = true;
            this.step(delta < 0 ? 1 : -1);
            setTimeout(() => { this.swiped = false; }, 350);
        },
        label() {
            if (!this.current) {
                return '';
            }

            return `${this.index + 1} / ${this.pages.length}`;
        },
    }));
});

if ('serviceWorker' in navigator) {
    window.addEventListener('load', () => {
        navigator.serviceWorker.register('/sw.js', { scope: '/' }).catch(() => {});
    });
}

let deferredInstallPrompt = null;

window.addEventListener('beforeinstallprompt', (event) => {
    event.preventDefault();
    deferredInstallPrompt = event;
    window.dispatchEvent(new CustomEvent('pwa-install-ready'));
});

window.addEventListener('pwa-install-request', async () => {
    if (!deferredInstallPrompt) {
        return;
    }

    deferredInstallPrompt.prompt();
    await deferredInstallPrompt.userChoice;
    deferredInstallPrompt = null;
    window.dispatchEvent(new CustomEvent('pwa-installed'));
});

window.addEventListener('appinstalled', () => {
    deferredInstallPrompt = null;
    window.dispatchEvent(new CustomEvent('pwa-installed'));
});
