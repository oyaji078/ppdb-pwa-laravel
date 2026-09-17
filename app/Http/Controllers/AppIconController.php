<?php

namespace App\Http\Controllers;

use App\Support\SettingsRepository;
use Illuminate\Http\Request;
use Illuminate\Http\Response;
use Illuminate\Support\Facades\Cache;

/**
 * Renders the school's uploaded logo into the square PNGs a browser and an
 * installed PWA ask for: the favicon, the iOS home-screen icon, and the
 * manifest icons.
 *
 * Schools upload one logo of whatever size and aspect ratio they happen to
 * have, so the icons are derived on the fly rather than kept as a second set of
 * files an admin would have to remember to replace. When no logo is set the
 * bundled placeholder is fed through the same pipeline, which keeps every size
 * available either way.
 */
class AppIconController extends Controller
{
    /**
     * Only the sizes the application actually references. A caller cannot ask
     * the server to rasterise arbitrary dimensions.
     */
    private const SIZES = [32, 48, 180, 192, 512];

    /**
     * Fraction of the canvas the logo is allowed to occupy on a maskable icon.
     * Android crops these to the launcher's own shape, and the guaranteed safe
     * region is the circle covering the middle 80%, so a square drawn inside it
     * has to stay near 60%.
     */
    private const MASKABLE_CONTENT_RATIO = 0.62;

    private const CACHE_TTL = 60 * 60 * 24 * 30;

    public function __construct(private readonly SettingsRepository $settings) {}

    public function favicon(Request $request): Response
    {
        return $this->show($request, 32);
    }

    public function show(Request $request, int $size): Response
    {
        abort_unless(in_array($size, self::SIZES, true), 404);

        $maskable = $request->boolean('maskable');

        // Base64 rather than the raw bytes: the cache store is a database
        // column holding text, and a PNG is full of bytes no text column will
        // take. Laravel's own store guards against this, but only on Postgres,
        // so on MySQL the write was rejected outright ("Incorrect string
        // value") and every icon request became a 500.
        //
        // The "b64" in the key marks the format. Entries written before this
        // hold raw bytes, and decoding one of those would yield rubbish, so
        // they are left to expire rather than read.
        $encoded = Cache::remember(
            sprintf('app-icon:b64:%s:%d:%s', $this->settings->iconVersion(), $size, $maskable ? 'maskable' : 'any'),
            self::CACHE_TTL,
            function () use ($size, $maskable): ?string {
                $rendered = $this->render($size, $maskable);

                return $rendered === null ? null : base64_encode($rendered);
            },
        );

        abort_if($encoded === null, 404);

        $png = base64_decode($encoded, true);

        abort_if($png === false, 404);

        return response($png, 200, [
            'Content-Type' => 'image/png',
            // Every link carries the logo fingerprint as ?v=, so a cached copy
            // can never outlive the logo it was made from.
            'Cache-Control' => 'public, max-age=604800, immutable',
        ]);
    }

    private function render(int $size, bool $maskable): ?string
    {
        $source = $this->sourceImage();

        if ($source === null) {
            return null;
        }

        $logo = @imagecreatefromstring($source);

        if ($logo === false) {
            return null;
        }

        $canvas = imagecreatetruecolor($size, $size);
        imagealphablending($canvas, false);
        imagesavealpha($canvas, true);

        if ($maskable) {
            // A maskable icon is cropped, so the corners must be filled. White
            // reads as intentional behind a logo; transparency would show the
            // launcher's own background through the crop.
            imagefilledrectangle($canvas, 0, 0, $size, $size, (int) imagecolorallocatealpha($canvas, 255, 255, 255, 0));
            $box = (int) round($size * self::MASKABLE_CONTENT_RATIO);
        } else {
            imagefilledrectangle($canvas, 0, 0, $size, $size, (int) imagecolorallocatealpha($canvas, 0, 0, 0, 127));
            $box = $size;
        }

        $sourceWidth = imagesx($logo);
        $sourceHeight = imagesy($logo);

        // Fit rather than fill: a wide or tall logo is letterboxed instead of
        // being cropped, so no part of the school's mark is cut off.
        $scale = min($box / $sourceWidth, $box / $sourceHeight);
        $width = max(1, (int) round($sourceWidth * $scale));
        $height = max(1, (int) round($sourceHeight * $scale));

        imagealphablending($canvas, true);
        imagecopyresampled(
            $canvas,
            $logo,
            (int) round(($size - $width) / 2),
            (int) round(($size - $height) / 2),
            0,
            0,
            $width,
            $height,
            $sourceWidth,
            $sourceHeight,
        );

        ob_start();
        imagepng($canvas, null, 9);
        $png = (string) ob_get_clean();

        imagedestroy($canvas);
        imagedestroy($logo);

        return $png === '' ? null : $png;
    }

    /**
     * The uploaded logo, or the bundled placeholder when there is none or when
     * the upload is not something GD can read.
     */
    private function sourceImage(): ?string
    {
        $logo = $this->settings->logoFile();

        if ($logo !== null && $logo['mime'] !== 'image/svg+xml') {
            return $logo['contents'];
        }

        $fallback = public_path('icons/icon-512.png');

        return is_file($fallback) ? (string) file_get_contents($fallback) : null;
    }
}
