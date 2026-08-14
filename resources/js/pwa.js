/**
 * Service worker registration plus the online/offline banner.
 *
 * Registration is deliberately skipped on admin and applicant paths: those
 * pages carry personal data and must never end up in a cache.
 */
const PRIVATE_PREFIXES = ['/admin', '/pendaftar'];

const isPrivatePath = () => PRIVATE_PREFIXES.some((prefix) => window.location.pathname.startsWith(prefix));

const registerServiceWorker = () => {
    if (!('serviceWorker' in navigator) || isPrivatePath()) {
        return;
    }

    navigator.serviceWorker.register('/sw.js', { scope: '/' }).catch(() => {
        // A failed registration only costs offline support; the site still works.
    });
};

const setupConnectivityBanner = () => {
    const banner = document.querySelector('[data-offline-banner]');

    if (!banner) {
        return;
    }

    const sync = () => banner.classList.toggle('hidden', navigator.onLine);

    window.addEventListener('online', sync);
    window.addEventListener('offline', sync);
    sync();
};

/**
 * Blocks submits that cannot possibly succeed while offline, rather than
 * letting the browser fail with its own error page.
 */
const guardOfflineSubmits = () => {
    document.addEventListener('submit', (event) => {
        if (navigator.onLine) {
            return;
        }

        event.preventDefault();

        const banner = document.querySelector('[data-offline-banner]');
        banner?.classList.remove('hidden');
        banner?.scrollIntoView({ behavior: 'smooth', block: 'center' });
    });
};

window.addEventListener('load', registerServiceWorker);
document.addEventListener('DOMContentLoaded', () => {
    setupConnectivityBanner();
    guardOfflineSubmits();
});
