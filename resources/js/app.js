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

    Alpine.data('reader', (boot) => ({
        mode: boot.mode || 'webtoon',
        stealth: !!boot.stealth,
        locked: !!boot.locked,
        pages: [],
        index: 0,
        focus: false,
        atEnd: false,
        nextReady: null,
        touchX: 0,
        progressTimer: null,
        title: boot.title,
        seriesTitle: boot.seriesTitle,
        nextUrl: boot.nextUrl,
        seriesUrl: boot.seriesUrl,
        async init() {
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
        get current() {
            return this.pages[this.index] || null;
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
            this.pages.slice(position, position + 4).forEach((page) => {
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
                this.nextReady = pages.slice(0, 3);
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
                window.scrollBy({ top: direction * window.innerHeight * 0.85, behavior: 'smooth' });

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
        onKey(event) {
            if (event.target.closest('input, textarea, select')) {
                return;
            }

            if (event.key === 'f') {
                this.focus = !this.focus;
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
        },
        touchEnd(event) {
            const delta = (event.changedTouches[0]?.clientX || 0) - this.touchX;

            if (Math.abs(delta) < 48) {
                return;
            }

            this.step(delta < 0 ? 1 : -1);
        },
        label() {
            if (!this.current) {
                return '';
            }

            return `${this.index + 1} / ${this.pages.length}`;
        },
    }));
});
