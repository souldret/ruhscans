import './bootstrap';

import Alpine from '@alpinejs/csp';

window.Alpine = Alpine;

Alpine.data('menu', () => ({
 open: false,
 toggle() {
 this.open = !this.open;
 },
 close() {
 this.open = false;
 },
 hamburger() {
 return this.open ? 'hidden' : 'inline-flex';
 },
 closeIcon() {
 return this.open ? 'inline-flex' : 'hidden';
 },
 panel() {
 return this.open ? 'block' : 'hidden';
 },
}));

Alpine.data('searchBox', () => ({
 search: false,
 open() {
 this.search = true;
 },
 close() {
 this.search = false;
 },
 shortcut(event) {
 const typing = ['INPUT', 'TEXTAREA', 'SELECT'].includes(event.target.tagName);

 if (!typing) {
 event.preventDefault();
 this.search = true;
 }
 },
 focusQuery() {
 if (this.search) {
 this.$nextTick(() => this.$refs.query && this.$refs.query.focus());
 }
 },
}));

Alpine.data('rowScroller', () => ({
 start: true,
 end: false,
 update() {
 const el = this.$refs.track;

 if (!el) {
 return;
 }

 this.start = el.scrollLeft <= 4;
 this.end = el.scrollLeft + el.clientWidth >= el.scrollWidth - 4;
 },
 scroll(dir) {
 const el = this.$refs.track;
 el.scrollBy({ left: dir * el.clientWidth * 0.8, behavior: 'smooth' });
 },
 back() {
 this.scroll(-1);
 },
 forward() {
 this.scroll(1);
 },
}));

Alpine.data('faq', () => ({
    open: null,
    toggle(index) {
        this.open = this.open === index ? null : index;
        document.querySelectorAll('[data-faq-index]').forEach((button) => {
            const i = Number(button.dataset.faqIndex);
            const on = i === this.open;
            button.setAttribute('aria-expanded', on ? 'true' : 'false');
            button.querySelector('svg')?.classList.toggle('rotate-180', on);
            const panel = document.querySelector(`[data-faq-panel="${i}"]`);
            if (panel) {
                panel.hidden = !on;
            }
        });
    },
}));

Alpine.data('downloadPanel', (config) => ({
 open: config.open,
 confirm: false,
 short: false,
 missing: 0,
 balance: config.balance,
 price: config.price,
 busy: false,
 title() {
 return this.open ? 'İndir' : 'Kilidi aç';
 },
 bought() {
 return this.open && !config.free;
 },
 locked() {
 return !this.open;
 },
 ask() {
 if (this.balance >= this.price) {
 this.confirm = true;
 return;
 }

 this.short = true;
 this.missing = this.price - this.balance;
 },
 cancel() {
 this.confirm = false;
 },
 remaining() {
 return this.balance - this.price;
 },
 ready() {
 return !this.confirm && !this.short;
 },
 async buy(event) {
 event.preventDefault();
 this.busy = true;

 try {
 const response = await fetch(config.url, {
 method: 'POST',
 headers: {
 'X-CSRF-TOKEN': document.querySelector('meta[name=csrf-token]').content,
 Accept: 'application/json',
 'X-Requested-With': 'XMLHttpRequest',
 },
 });
 const body = await response.json();
 this.busy = false;

 if (body.ok) {
 window.location.reload();
 return;
 }

 this.missing = body.missing ?? 0;
 } catch (error) {
 this.busy = false;
 event.target.submit();
 return;
 }

 this.short = true;
 this.confirm = false;
 },
}));

Alpine.data('fileTabs', (first) => ({
 tab: first,
 select(key) {
 this.tab = key;
 },
 active(key) {
 return this.tab === key;
 },
 tabClass(key) {
 return this.tab === key ? 'bg-accent text-white' : 'text-ink-muted hover:text-ink-text';
 },
}));

Alpine.data('coinCounter', (target, gained) => ({
 shown: 0,
 init() {
 const from = Math.max(0, target - gained);
 const start = performance.now();
 const tick = (now) => {
 const progress = Math.min(1, (now - start) / 900);
 this.shown = Math.round(from + (target - from) * progress);

 if (progress < 1) {
 requestAnimationFrame(tick);
 }
 };

 requestAnimationFrame(tick);
 },
}));

Alpine.data('flash', (visible) => ({
 celebrate: visible,
 init() {
 if (this.celebrate) {
 setTimeout(() => {
 this.celebrate = false;
 }, 3000);
 }
 },
}));

Alpine.data('notice', () => ({
 show: true,
 init() {
 setTimeout(() => {
 this.show = false;
 }, 2000);
 },
 hide() {
 this.show = false;
 },
}));

Alpine.data('dialog', (name, visible, focusable) => ({
 show: visible,
 init() {
 this.$watch('show', (value) => {
 document.body.classList.toggle('overflow-y-hidden', value);

 if (value && focusable) {
 setTimeout(() => this.firstFocusable()?.focus(), 100);
 }
 });
 },
 focusables() {
 const selector = 'a, button, input:not([type=hidden]), textarea, select, details, [tabindex]:not([tabindex="-1"])';

 return [...this.$el.querySelectorAll(selector)].filter((el) => !el.hasAttribute('disabled'));
 },
 firstFocusable() {
 return this.focusables()[0];
 },
 lastFocusable() {
 return this.focusables().at(-1);
 },
 nextFocusable() {
 const items = this.focusables();

 return items[(items.indexOf(document.activeElement) + 1) % (items.length + 1)] || this.firstFocusable();
 },
 previousFocusable() {
 const items = this.focusables();

 return items[Math.max(0, items.indexOf(document.activeElement)) - 1] || this.lastFocusable();
 },
 matches(event) {
 return event.detail === name;
 },
 openFrom(event) {
 if (this.matches(event)) {
 this.show = true;
 }
 },
 closeFrom(event) {
 if (this.matches(event)) {
 this.show = false;
 }
 },
 close() {
 this.show = false;
 },
 tab(event) {
 if (!event.shiftKey) {
 this.nextFocusable()?.focus();
 }
 },
 shiftTab() {
 this.previousFocusable()?.focus();
 },
}));

Alpine.start();

(() => {
    const root = document.querySelector('[data-hero]');
    if (!root) {
        return;
    }

    const slides = [...root.querySelectorAll('[data-slide]')];
    const dots = [...root.querySelectorAll('[data-hero-dot]')];
    if (slides.length < 2) {
        return;
    }

    const reduced = window.matchMedia('(prefers-reduced-motion: reduce)').matches;
    let index = 0;
    let timer = null;

    const show = (next) => {
        index = (next + slides.length) % slides.length;
        slides.forEach((slide, i) => {
            const on = i === index;
            slide.hidden = !on;
            slide.setAttribute('aria-hidden', on ? 'false' : 'true');
        });
        dots.forEach((dot, i) => {
            const on = i === index;
            dot.classList.toggle('w-10', on);
            dot.classList.toggle('w-4', !on);
            dot.setAttribute('aria-selected', on ? 'true' : 'false');
            const bar = dot.querySelector('[data-hero-progress]');
            if (!bar) {
                return;
            }
            bar.classList.toggle('hidden', !on);
            bar.classList.remove('animate-progress');
            if (on && !reduced && timer) {
                void bar.offsetWidth;
                bar.classList.add('animate-progress');
            }
        });
    };

    const start = () => {
        clearInterval(timer);
        timer = null;
        if (reduced) {
            return;
        }
        timer = setInterval(() => show(index + 1), 6000);
        const bar = dots[index]?.querySelector('[data-hero-progress]');
        if (bar && !bar.classList.contains('hidden')) {
            bar.classList.remove('animate-progress');
            void bar.offsetWidth;
            bar.classList.add('animate-progress');
        }
    };

    const stop = () => {
        clearInterval(timer);
        timer = null;
        dots.forEach((dot) => dot.querySelector('[data-hero-progress]')?.classList.remove('animate-progress'));
    };

    dots.forEach((dot) => {
        dot.addEventListener('click', () => {
            show(Number(dot.dataset.index));
            start();
        });
    });
    root.querySelector('[data-hero-next]')?.addEventListener('click', () => {
        show(index + 1);
        start();
    });
    root.querySelector('[data-hero-prev]')?.addEventListener('click', () => {
        show(index - 1);
        start();
    });
    root.addEventListener('mouseenter', stop);
    root.addEventListener('mouseleave', start);
    root.addEventListener('focusin', stop);
    root.addEventListener('focusout', (event) => {
        if (!root.contains(event.relatedTarget)) {
            start();
        }
    });
    root.addEventListener('keydown', (event) => {
        if (event.key === 'ArrowRight') {
            show(index + 1);
            start();
        } else if (event.key === 'ArrowLeft') {
            show(index - 1);
            start();
        }
    });

    show(0);
    start();
})();



(() => {
 const gallery = document.getElementById('shot-gallery');
 const viewer = document.getElementById('shot-viewer');

 if (!gallery || !viewer) {
 return;
 }

 const shots = JSON.parse(gallery.dataset.shots || '[]').map((shot) => (
 typeof shot === 'string' ? { url: shot, kind: 'image' } : shot
 ));
 const full = viewer.querySelector('[data-shot-full]');
 const video = viewer.querySelector('[data-shot-video]');
 let index = 0;

 const paint = () => {
 const shot = shots[index] || { url: '', kind: 'image' };
 const isVideo = shot.kind === 'video';
 full.hidden = isVideo;
 video.hidden = !isVideo;
 full.removeAttribute('src');
 video.pause();
 video.removeAttribute('src');
 video.load();
 if (isVideo) {
 video.src = shot.url;
 video.play().catch(() => {});
 } else {
 full.src = shot.url;
 }
 };

 const open = (next) => {
 index = next;
 paint();
 viewer.hidden = false;
 viewer.classList.remove('hidden');
 viewer.classList.add('grid');
 document.body.classList.add('overflow-y-hidden');
 };

 const close = () => {
 viewer.hidden = true;
 viewer.classList.add('hidden');
 viewer.classList.remove('grid');
 full.removeAttribute('src');
 video.pause();
 video.removeAttribute('src');
 video.load();
 document.body.classList.remove('overflow-y-hidden');
 };

 gallery.addEventListener('click', (event) => {
 const button = event.target.closest('[data-shot]');

 if (button && gallery.contains(button)) {
 open(Number(button.dataset.shot));
 }
 });

 viewer.addEventListener('click', (event) => {
 if (event.target === viewer) {
 close();
 return;
 }

 if (event.target.closest('[data-shot-close]')) {
 close();
 return;
 }

 if (event.target.closest('[data-shot-next]')) {
 index = (index + 1) % shots.length;
 paint();
 return;
 }

 if (event.target.closest('[data-shot-prev]')) {
 index = (index - 1 + shots.length) % shots.length;
 paint();
 }
 });

 document.addEventListener('keydown', (event) => {
 if (viewer.hidden) {
 return;
 }

 if (event.key === 'Escape') {
 close();
 } else if (event.key === 'ArrowRight' && shots.length > 1) {
 index = (index + 1) % shots.length;
 paint();
 } else if (event.key === 'ArrowLeft' && shots.length > 1) {
 index = (index - 1 + shots.length) % shots.length;
 paint();
 }
 });
})();


document.addEventListener('submit', (event) => {
 const form = event.target;

 if (!(form instanceof HTMLFormElement)) {
 return;
 }

 // Arama formları veya iptal edilen formlarda gönderme kilidi koyma
 if (form.getAttribute('role') === 'search' || form.dataset.submitting === '1') {
 return;
 }

 form.dataset.submitting = '1';
 const button = form.querySelector('[type="submit"]');

 window.setTimeout(() => {
 if (!(button instanceof HTMLButtonElement) || button.dataset.noDisable === 'true') {
 return;
 }

 button.disabled = true;
 button.setAttribute('aria-busy', 'true');
 button.dataset.label = button.textContent ?? '';
 button.textContent = 'Gönderiliyor…';
 }, 0);
});
