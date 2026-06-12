const CACHE_NAME = 'ddm-production-v1';
const APP_SHELL = [
    '/',
    '/input-proses',
    '/spk',
    '/manifest.webmanifest',
    '/pwa-icon.svg',
    '/offline.html'
];

self.addEventListener('install', (event) => {
    event.waitUntil(
        caches.open(CACHE_NAME).then((cache) => cache.addAll(APP_SHELL))
    );
    self.skipWaiting();
});

self.addEventListener('activate', (event) => {
    event.waitUntil(
        caches.keys().then((keys) => Promise.all(
            keys.filter((key) => key !== CACHE_NAME).map((key) => caches.delete(key))
        ))
    );
    self.clients.claim();
});

self.addEventListener('fetch', (event) => {
    const request = event.request;

    if (request.method !== 'GET') {
        return;
    }

    event.respondWith(
        fetch(request)
            .then((response) => {
                if (response.ok && request.url.startsWith(self.location.origin)) {
                    const copy = response.clone();
                    caches.open(CACHE_NAME).then((cache) => cache.put(request, copy));
                }

                return response;
            })
            .catch(() => caches.match(request).then((cached) => cached || caches.match('/offline.html')))
    );
});
