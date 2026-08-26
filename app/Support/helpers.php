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

if (! function_exists('field_name')) {
    /**
     * Convert a validation-style dot name into an HTML input name.
     *
     * Validation rules, old() and the error bag all address nested input with
     * dots ("father.name"), but that spelling cannot be used in the HTML name
     * attribute: PHP rewrites dots in top-level request keys to underscores, so
     * "father.name" would arrive as "father_name" and the nested rule would
     * never see it. Brackets are the wire format PHP parses back into an array.
     */
    function field_name(string $name): string
    {
        if (! str_contains($name, '.')) {
            return $name;
        }

        $segments = explode('.', $name);

        return array_shift($segments)
            .implode('', array_map(fn (string $segment) => '['.$segment.']', $segments));
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
