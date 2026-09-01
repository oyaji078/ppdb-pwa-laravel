<?php

namespace App\Http\Controllers;

use Illuminate\Contracts\View\View;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Response;

/**
 * Serves the PWA manifest and service worker from PHP so both can pick up the
 * school name and asset hashes from the application rather than being static
 * files that drift out of date.
 */
class PwaController extends Controller
{
    public function manifest(): JsonResponse
    {
        $settings = settings();
        $name = $settings->schoolName();
        $short = $settings->get('school_short_name') ?? $settings->admissionName();

        return response()->json([
            'name' => $settings->admissionName().' '.$name,
            'short_name' => $short,
            'description' => $settings->get('admission_tagline').' '.$name,
            'lang' => 'id',
            'dir' => 'ltr',
            'start_url' => '/',
            'scope' => '/',
            'display' => 'standalone',
            'orientation' => 'portrait-primary',
            'background_color' => '#f8fafc',
            'theme_color' => '#1d4ed8',
            'categories' => ['education'],
            // Rendered from the logo uploaded in the admin settings, so an
            // installed app carries the school's own mark rather than the
            // placeholder shipped with the source.
            'icons' => [
                [
                    'src' => $this->iconUrl(192),
                    'sizes' => '192x192',
                    'type' => 'image/png',
                    'purpose' => 'any',
                ],
                [
                    'src' => $this->iconUrl(512),
                    'sizes' => '512x512',
                    'type' => 'image/png',
                    'purpose' => 'any',
                ],
                [
                    'src' => $this->iconUrl(512, maskable: true),
                    'sizes' => '512x512',
                    'type' => 'image/png',
                    'purpose' => 'maskable',
                ],
            ],
            'shortcuts' => [
                [
                    'name' => 'Daftar',
                    'url' => route('registration.start', absolute: false),
                    'icons' => [['src' => $this->iconUrl(192), 'sizes' => '192x192']],
                ],
                [
                    'name' => 'Cek Status',
                    'url' => route('status.form', absolute: false),
                    'icons' => [['src' => $this->iconUrl(192), 'sizes' => '192x192']],
                ],
            ],
        ], 200, ['Content-Type' => 'application/manifest+json']);
    }

    /**
     * The service worker must be served from the site root to control the whole
     * scope, and must never be cached itself.
     */
    public function serviceWorker(): Response
    {
        return response()
            ->view('pwa.service-worker', [
                'version' => $this->cacheVersion(),
                'precache' => $this->precacheUrls(),
            ])
            ->header('Content-Type', 'application/javascript; charset=utf-8')
            ->header('Service-Worker-Allowed', '/')
            ->header('Cache-Control', 'no-cache, no-store, must-revalidate');
    }

    public function offline(): View
    {
        return view('pwa.offline');
    }

    /**
     * URL of an app icon at the given size, versioned by the current logo so a
     * new upload is picked up instead of being served from a stale cache.
     */
    private function iconUrl(int $size, bool $maskable = false): string
    {
        $query = ['size' => $size, 'v' => settings()->iconVersion()];

        if ($maskable) {
            $query['maskable'] = 1;
        }

        return route('pwa.icon', $query);
    }

    /**
     * Changing this string invalidates every cached entry after a deploy.
     */
    private function cacheVersion(): string
    {
        $manifest = public_path('build/manifest.json');

        $signature = is_file($manifest)
            ? (string) filemtime($manifest)
            : (string) config('app.version', '1');

        // The logo is part of the signature: replacing it has to evict the
        // precached icons alongside the rest of the shell.
        return 'ppdb-v'.substr(md5($signature.settings()->iconVersion().config('app.key')), 0, 10);
    }

    /**
     * Public shell pages worth having available offline. Deliberately excludes
     * anything under /admin or /pendaftar.
     *
     * @return array<int, string>
     */
    private function precacheUrls(): array
    {
        return [
            route('pwa.offline', absolute: false),
            route('home', absolute: false),
            route('profile', absolute: false),
            route('programs', absolute: false),
            route('facilities', absolute: false),
            route('ppdb.index', absolute: false),
            route('ppdb.schedule', absolute: false),
            route('ppdb.requirements', absolute: false),
            route('contact', absolute: false),
            $this->iconUrl(192),
            $this->iconUrl(512),
        ];
    }
}
