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

Alpine.data('heroSlider', (total) => ({
 index: 0,
 total,
 timer: null,
 paused: false,
 reduced: window.matchMedia('(prefers-reduced-motion: reduce)').matches,
 start() {
 this.stop();

 if (this.total < 2 || this.reduced) {
 return;
 }

 this.paused = false;
 this.timer = setInterval(() => this.next(), 6000);
 },
 stop() {
 clearInterval(this.timer);
 this.paused = true;
 },
 go(i) {
 this.index = (i + this.total) % this.total;
 this.start();
 },
 next() {
 this.index = (this.index + 1) % this.total;
 },
 previous() {
 this.go(this.index - 1);
 },
 forward() {
 this.go(this.index + 1);
 },
 showing(i) {
 return Number(this.index) === Number(i);
 },
 dotClass(i) {
 return Number(this.index) === Number(i) ? 'w-10' : 'w-4 hover:bg-white/40';
 },
 slideClass(i) {
 return Number(this.index) === Number(i) ? 'relative' : 'absolute inset-0';
 },
 progressClass() {
 return !this.paused && !this.reduced ? 'animate-progress' : '';
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

Alpine.data('lightbox', () => ({
    open: false,
    shots: [],
    index: 0,
    show(shots, index) {
        this.shots = shots;
        this.index = index;
        this.open = true;
    },
    get src() {
        return this.shots[this.index] || '';
    },
    next() {
        if (this.shots.length > 1) {
            this.index = (this.index + 1) % this.shots.length;
        }
    },
    previous() {
        if (this.shots.length > 1) {
            this.index = (this.index - 1 + this.shots.length) % this.shots.length;
        }
    },
    close() {
        this.open = false;
    },
}));

Alpine.data('faq', () => ({
 open: null,
 toggle(index) {
 this.open = this.open === index ? null : index;
 },
 showing(index) {
 return this.open === index;
 },
 chevron(index) {
 return this.open === index ? 'rotate-180' : '';
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
