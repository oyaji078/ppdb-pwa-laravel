{{-- Rendered by PwaController; served at /sw.js with Service-Worker-Allowed: /. --}}
const CACHE_VERSION = '{{ $version }}';
const STATIC_CACHE = `${CACHE_VERSION}-static`;
const PAGE_CACHE = `${CACHE_VERSION}-pages`;
const OFFLINE_URL = '{{ route('pwa.offline', absolute: false) }}';

const PRECACHE_URLS = @json($precache);

/**
 * Anything under these prefixes carries personal data and is never cached.
 * Requests for them go straight to the network.
 */
const PRIVATE_PREFIXES = ['/admin', '/pendaftar', '/daftar', '/cek-status'];

const isPrivate = (url) => PRIVATE_PREFIXES.some((prefix) => url.pathname.startsWith(prefix));

const isStaticAsset = (url) =>
    url.pathname.startsWith('/build/') ||
    url.pathname.startsWith('/icons/') ||
    /\.(css|js|woff2?|png|jpg|jpeg|svg|webp|ico)$/.test(url.pathname);

self.addEventListener('install', (event) => {
    event.waitUntil(
        caches
            .open(STATIC_CACHE)
            .then((cache) => cache.addAll(PRECACHE_URLS).catch(() => undefined))
            .then(() => self.skipWaiting()),
    );
});

self.addEventListener('activate', (event) => {
    event.waitUntil(
        caches
            .keys()
            .then((keys) =>
                Promise.all(
                    keys
                        .filter((key) => !key.startsWith(CACHE_VERSION))
                        .map((key) => caches.delete(key)),
                ),
            )
            .then(() => self.clients.claim()),
    );
});

self.addEventListener('message', (event) => {
    if (event.data === 'SKIP_WAITING') {
        self.skipWaiting();
    }
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

    // Private areas: network only, never stored.
    if (isPrivate(url)) {
        event.respondWith(fetch(request));

        return;
    }

    // Static assets: cache first, they are content-hashed by Vite.
    if (isStaticAsset(url)) {
        event.respondWith(
            caches.match(request).then(
                (cached) =>
                    cached ||
                    fetch(request).then((response) => {
                        if (response.ok) {
                            const copy = response.clone();
                            caches.open(STATIC_CACHE).then((cache) => cache.put(request, copy));
                        }

                        return response;
                    }),
            ),
        );

        return;
    }

    // Public pages: network first so content stays fresh, cache as a fallback.
    if (request.mode === 'navigate' || request.headers.get('accept')?.includes('text/html')) {
        event.respondWith(
            fetch(request)
                .then((response) => {
                    if (response.ok && !response.headers.get('Cache-Control')?.includes('no-store')) {
                        const copy = response.clone();
                        caches.open(PAGE_CACHE).then((cache) => cache.put(request, copy));
                    }

                    return response;
                })
                .catch(() =>
                    caches
                        .match(request)
                        .then((cached) => cached || caches.match(OFFLINE_URL))
                        .then((response) => response || Response.error()),
                ),
        );
    }
});
