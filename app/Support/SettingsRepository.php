<?php

namespace App\Support;

use App\Models\Setting;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Storage;

/**
 * Read/write access to the settings table with a single cached round trip.
 *
 * Every school-identity string in the UI resolves through here, so nothing
 * about the institution is hardcoded in views.
 */
class SettingsRepository
{
    private const CACHE_KEY = 'ppdb.settings';

    /** @var array<string, string|null>|null */
    private ?array $loaded = null;

    /**
     * Fallbacks used before an administrator saves anything, and whenever a
     * key is missing from the database.
     *
     * @var array<string, string>
     */
    public const DEFAULTS = [
        'school_name' => 'SMA Islam Hamzanwadi Peneda',
        'school_short_name' => 'SMA Islam Hamzanwadi',
        'school_logo' => '',
        'school_address' => 'Peneda, Kabupaten Lombok Timur, Nusa Tenggara Barat',
        'school_phone' => '',
        'school_email' => '',
        'school_website' => '',
        'admission_name' => 'PPDB',
        'admission_tagline' => 'Penerimaan Peserta Didik Baru',
        'contact_whatsapp' => '',
        'contact_email' => '',
        'contact_person' => '',
        'max_upload_size' => '2048',
        'maintenance_message' => '',
        'hero_title' => '',
        'hero_subtitle' => '',
        'hero_banner' => '',
        'hero_overlay' => '60',
        'faq' => '',

        // School location, shown as an embedded map on the contact page.
        'map_latitude' => '',
        'map_longitude' => '',
        'map_zoom' => '16',
        'map_place_name' => '',

        // Outgoing mail. Configured from the admin panel so a school can move
        // hosts without touching .env; mail_password is stored encrypted.
        'mail_enabled' => '0',
        'mail_mailer' => 'smtp',
        'mail_host' => '',
        'mail_port' => '587',
        'mail_username' => '',
        'mail_password' => '',
        'mail_encryption' => 'tls',
        'mail_from_address' => '',
        'mail_from_name' => '',
        'mail_require_verification' => '0',
    ];

    /**
     * @return array<string, string|null>
     */
    public function all(): array
    {
        if ($this->loaded !== null) {
            return $this->loaded;
        }

        $stored = Cache::rememberForever(self::CACHE_KEY, function (): array {
            return Setting::query()->pluck('value', 'key')->all();
        });

        return $this->loaded = array_merge(self::DEFAULTS, $stored);
    }

    public function get(string $key, ?string $default = null): ?string
    {
        $value = $this->all()[$key] ?? $default ?? self::DEFAULTS[$key] ?? null;

        return $value === '' ? ($default ?? null) : $value;
    }

    /**
     * Raw value including empty strings — used by admin forms so a cleared
     * field does not silently fall back to the default.
     */
    public function raw(string $key): ?string
    {
        return $this->all()[$key] ?? self::DEFAULTS[$key] ?? null;
    }

    public function set(string $key, ?string $value, string $group = 'general', string $type = 'string'): void
    {
        Setting::query()->updateOrCreate(
            ['key' => $key],
            ['value' => $value, 'group' => $group, 'type' => $type]
        );

        $this->flush();
    }

    /**
     * @param  array<string, string|null>  $values
     */
    public function setMany(array $values, string $group = 'general'): void
    {
        foreach ($values as $key => $value) {
            Setting::query()->updateOrCreate(
                ['key' => $key],
                ['value' => $value, 'group' => $group]
            );
        }

        $this->flush();
    }

    public function flush(): void
    {
        $this->loaded = null;
        Cache::forget(self::CACHE_KEY);
    }

    public function schoolName(): string
    {
        return $this->get('school_name') ?? self::DEFAULTS['school_name'];
    }

    public function admissionName(): string
    {
        return $this->get('admission_name') ?? self::DEFAULTS['admission_name'];
    }

    /**
     * Public URL of the uploaded logo, or null when none is set.
     */
    public function logoUrl(): ?string
    {
        $path = $this->get('school_logo');

        if (! $path) {
            return null;
        }

        return Storage::disk('public')->url($path);
    }

    /**
     * Public URL of the uploaded homepage banner, or null when the hero should
     * fall back to its plain brand colour.
     */
    public function heroBannerUrl(): ?string
    {
        $path = $this->get('hero_banner');

        if (! $path) {
            return null;
        }

        return Storage::disk('public')->url($path);
    }

    /**
     * How dark the scrim over the banner is, as a 0..1 CSS opacity. Text on the
     * hero has to stay readable whatever photo the school uploads.
     */
    public function heroOverlayOpacity(): float
    {
        $percent = (int) ($this->get('hero_overlay') ?? 60);

        return max(0, min(90, $percent)) / 100;
    }

    /**
     * Whether a location has been configured well enough to draw a map.
     */
    public function hasMapLocation(): bool
    {
        return is_numeric($this->get('map_latitude')) && is_numeric($this->get('map_longitude'));
    }

    /**
     * Embeddable Google Maps URL. Uses the plain `output=embed` form, which
     * needs no API key and no billing account — a school can paste coordinates
     * and be done.
     */
    public function mapEmbedUrl(): ?string
    {
        if (! $this->hasMapLocation()) {
            return null;
        }

        return sprintf(
            'https://www.google.com/maps?q=%s,%s&z=%d&hl=id&output=embed',
            $this->get('map_latitude'),
            $this->get('map_longitude'),
            (int) ($this->get('map_zoom') ?: 16),
        );
    }

    /**
     * Link that opens the location in the Google Maps app or website.
     */
    public function mapLinkUrl(): ?string
    {
        if (! $this->hasMapLocation()) {
            return null;
        }

        return sprintf(
            'https://www.google.com/maps/search/?api=1&query=%s,%s',
            $this->get('map_latitude'),
            $this->get('map_longitude'),
        );
    }

    /**
     * Fingerprint of the current logo, used to version every icon URL.
     *
     * Uploads get a random filename, so the stored path already changes
     * whenever the logo does: hashing it gives a cache key that expires exactly
     * when it should, and a stable one while the logo stays put.
     */
    public function iconVersion(): string
    {
        return substr(md5((string) ($this->get('school_logo') ?? 'default')), 0, 10);
    }

    /**
     * The logo's bytes and mime type, for embedding into PDFs and for resizing
     * into app icons.
     *
     * Read through the disk rather than from a filesystem path: on Vercel the
     * public disk is object storage, where a local path does not exist. The
     * mime type comes from the extension so that this costs one round trip
     * rather than two.
     *
     * @return array{contents: string, mime: string}|null
     */
    public function logoFile(): ?array
    {
        $path = $this->get('school_logo');

        if (! $path) {
            return null;
        }

        $contents = Storage::disk('public')->get($path);

        if ($contents === null || $contents === '') {
            return null;
        }

        return [
            'contents' => $contents,
            'mime' => match (strtolower((string) pathinfo($path, PATHINFO_EXTENSION))) {
                'jpg', 'jpeg' => 'image/jpeg',
                'webp' => 'image/webp',
                'svg' => 'image/svg+xml',
                default => 'image/png',
            },
        ];
    }

    /**
     * WhatsApp deep link built from the configured number.
     */
    public function whatsappUrl(): ?string
    {
        $number = $this->get('contact_whatsapp');

        if (! $number) {
            return null;
        }

        $digits = preg_replace('/\D+/', '', $number);

        if ($digits === '') {
            return null;
        }

        if (str_starts_with($digits, '0')) {
            $digits = '62'.substr($digits, 1);
        }

        return 'https://wa.me/'.$digits;
    }

    /**
     * FAQ entries stored as "question|answer" lines.
     *
     * @return array<int, array{question: string, answer: string}>
     */
    public function faq(): array
    {
        $raw = $this->get('faq');

        if (! $raw) {
            return [];
        }

        $items = [];

        foreach (preg_split('/\r\n|\r|\n/', $raw) as $line) {
            if (! str_contains($line, '|')) {
                continue;
            }

            [$question, $answer] = explode('|', $line, 2);
            $question = trim($question);
            $answer = trim($answer);

            if ($question !== '' && $answer !== '') {
                $items[] = ['question' => $question, 'answer' => $answer];
            }
        }

        return $items;
    }
}
