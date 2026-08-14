<?php

use App\Support\SettingsRepository;

if (! function_exists('settings')) {
    /**
     * Resolve the settings repository, or a single setting when given a key.
     */
    function settings(?string $key = null, ?string $default = null): SettingsRepository|string|null
    {
        $repository = app(SettingsRepository::class);

        return $key === null ? $repository : $repository->get($key, $default);
    }
}

if (! function_exists('mask_identity_number')) {
    /**
     * Mask the middle of an identity number: 5203********1234.
     */
    function mask_identity_number(?string $number, int $head = 4, int $tail = 4): string
    {
        $number = trim((string) $number);

        if ($number === '') {
            return '-';
        }

        if (strlen($number) <= $head + $tail) {
            return str_repeat('*', strlen($number));
        }

        return substr($number, 0, $head)
            .str_repeat('*', strlen($number) - $head - $tail)
            .substr($number, -$tail);
    }
}
