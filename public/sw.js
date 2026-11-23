const CACHE = 'ruhscans-shell-v1';

const SHELL = [
    '/offline.html',
    '/manifest.json',
    '/favicon.svg',
    '/favicon-neutral.svg',
    '/icons/icon-192.png',
    '/icons/icon-512.png',
    '/icons/icon-maskable-512.png',
];

self.addEventListener('install', (event) => {
    event.waitUntil(
        caches
            .open(CACHE)
            .then((cache) => cache.addAll(SHELL))
            .then(() => self.skipWaiting()),
    );
});

self.addEventListener('activate', (event) => {
    event.waitUntil(
        caches
            .keys()
            .then((keys) => Promise.all(keys.filter((key) => key !== CACHE).map((key) => caches.delete(key))))
            .then(() => self.clients.claim()),
    );
});

self.addEventListener('fetch', (event) => {
    const request = event.request;

    if (request.method !== 'GET') {
        return;
    }

    const url = new URL(request.url);

    if (url.origin !== self.location.origin) {
        return;
    }

    // Gezinmeler çevrimdışı önbelleğe alınmaz; yalnızca nötr kabuk gösterilir.
    if (request.mode === 'navigate') {
        event.respondWith(fetch(request).catch(() => caches.match('/offline.html')));

        return;
    }

    // Yalnızca derlenmiş varlıklar ve PWA kabuğu önbelleğe alınır.
    // Kapaklar, sayfa görselleri ve +18 içerik asla önbelleğe girmez.
    const isShellAsset = url.pathname.startsWith('/build/') || SHELL.includes(url.pathname);

    if (!isShellAsset) {
        return;
    }

    event.respondWith(
        caches.match(request).then((cached) => {
            const network = fetch(request)
                .then((response) => {
                    if (response.ok) {
                        const copy = response.clone();
                        caches.open(CACHE).then((cache) => cache.put(request, copy));
                    }

                    return response;
                })
                .catch(() => cached);

            return cached || network;
        }),
    );
});
