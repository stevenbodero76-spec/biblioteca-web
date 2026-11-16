const CACHE_NAME = 'novaether-shell-v1';

self.addEventListener('install', (event) => {
    self.skipWaiting();
    event.waitUntil(
        caches.open(CACHE_NAME).then((cache) => cache.addAll(['/manifest.webmanifest']))
    );
});

self.addEventListener('activate', (event) => {
    event.waitUntil(self.clients.claim());
});

self.addEventListener('fetch', (event) => {
    if (event.request.method !== 'GET') return;
    const url = new URL(event.request.url);
    if (url.origin !== self.location.origin) return;
    if (url.pathname.startsWith('/build/')) {
        event.respondWith(
            caches.match(event.request).then((cached) => cached || fetch(event.request).then((res) => {
                const clone = res.clone();
                caches.open(CACHE_NAME).then((cache) => cache.put(event.request, clone));
                return res;
            }))
        );
    }
});
