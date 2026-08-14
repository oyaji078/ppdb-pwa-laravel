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
        'faq' => '',
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
     * Absolute filesystem path of the logo, for embedding into PDFs.
     */
    public function logoPath(): ?string
    {
        $path = $this->get('school_logo');

        if (! $path || ! Storage::disk('public')->exists($path)) {
            return null;
        }

        return Storage::disk('public')->path($path);
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
