<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Services\ActivityLogger;
use App\Support\ImageUploader;
use App\Support\SettingsRepository;
use Illuminate\Contracts\View\View;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;

class SettingController extends Controller
{
    /**
     * Editable keys grouped the way they appear in the form.
     *
     * @var array<string, array<int, string>>
     */
    private const GROUPS = [
        'school' => ['school_name', 'school_short_name', 'school_address', 'school_phone', 'school_email', 'school_website'],
        'admission' => ['admission_name', 'admission_tagline'],
        'contact' => ['contact_person', 'contact_whatsapp', 'contact_email'],
        'homepage' => ['hero_title', 'hero_subtitle', 'faq'],
        'system' => ['maintenance_message'],
    ];

    public function __construct(
        private readonly SettingsRepository $settings,
        private readonly ImageUploader $images,
        private readonly ActivityLogger $activity,
    ) {}

    public function edit(): View
    {
        return view('admin.settings.edit', [
            'values' => collect(self::GROUPS)
                ->flatten()
                ->mapWithKeys(fn (string $key) => [$key => $this->settings->raw($key)])
                ->all(),
        ]);
    }

    public function update(Request $request): RedirectResponse
    {
        $validated = $request->validate([
            'school_name' => ['required', 'string', 'max:150'],
            'school_short_name' => ['nullable', 'string', 'max:80'],
            'school_address' => ['nullable', 'string', 'max:500'],
            'school_phone' => ['nullable', 'string', 'max:40'],
            'school_email' => ['nullable', 'email:rfc', 'max:150'],
            'school_website' => ['nullable', 'url', 'max:200'],

            'admission_name' => ['required', 'string', 'max:60'],
            'admission_tagline' => ['nullable', 'string', 'max:200'],

            'contact_person' => ['nullable', 'string', 'max:150'],
            'contact_whatsapp' => ['nullable', 'string', 'max:40'],
            'contact_email' => ['nullable', 'email:rfc', 'max:150'],

            'hero_title' => ['nullable', 'string', 'max:150'],
            'hero_subtitle' => ['nullable', 'string', 'max:500'],
            'faq' => ['nullable', 'string', 'max:8000'],

            'maintenance_message' => ['nullable', 'string', 'max:500'],

            'logo' => ['nullable', 'image', 'mimes:jpg,jpeg,png,webp', 'max:1024'],
        ], [
            'logo.max' => 'Ukuran logo maksimal 1 MB.',
            'school_website.url' => 'Alamat website harus lengkap, contoh: https://sekolah.sch.id',
        ]);

        unset($validated['logo']);

        foreach (self::GROUPS as $group => $keys) {
            $this->settings->setMany(
                collect($keys)->mapWithKeys(fn (string $key) => [$key => $validated[$key] ?? null])->all(),
                $group
            );
        }

        if ($request->hasFile('logo')) {
            $this->settings->set(
                'school_logo',
                $this->images->replace($request->file('logo'), 'branding', $this->settings->raw('school_logo')),
                'school'
            );
        }

        $this->activity->log(ActivityLogger::CONFIGURATION_CHANGED, 'Pengaturan sistem diperbarui.');

        return back()->with('success', 'Pengaturan berhasil disimpan.');
    }
}
