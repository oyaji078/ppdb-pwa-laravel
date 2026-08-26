<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Services\ActivityLogger;
use App\Services\ApplicantMailer;
use App\Support\ImageUploader;
use App\Support\MailConfigurator;
use App\Support\SettingsRepository;
use Illuminate\Contracts\View\View;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;
use Throwable;

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
        'homepage' => ['hero_title', 'hero_subtitle', 'hero_overlay', 'faq'],
        'location' => ['map_latitude', 'map_longitude', 'map_zoom', 'map_place_name'],
        'system' => ['maintenance_message'],
        'mail' => [
            'mail_enabled', 'mail_mailer', 'mail_host', 'mail_port', 'mail_username',
            'mail_encryption', 'mail_from_address', 'mail_from_name', 'mail_require_verification',
        ],
    ];

    public function __construct(
        private readonly SettingsRepository $settings,
        private readonly ImageUploader $images,
        private readonly ActivityLogger $activity,
        private readonly MailConfigurator $mail,
        private readonly ApplicantMailer $mailer,
    ) {}

    public function edit(): View
    {
        return view('admin.settings.edit', [
            'values' => collect(self::GROUPS)
                ->flatten()
                ->mapWithKeys(fn (string $key) => [$key => $this->settings->raw($key)])
                ->all(),
            // Never echo the stored password back into the form; only say
            // whether one is on file.
            'hasMailPassword' => filled($this->settings->raw('mail_password')),
        ]);
    }

    /**
     * Proves the stored credentials actually work. The exception message is
     * surfaced verbatim because "Connection could not be established with host"
     * is precisely what the admin needs to see.
     */
    public function sendTestMail(Request $request): RedirectResponse
    {
        $validated = $request->validate(
            ['test_email' => ['required', 'email:rfc', 'max:150']],
            ['test_email.required' => 'Isi alamat email tujuan untuk uji coba.']
        );

        if (! $this->mail->enabled()) {
            return back()->with('warning', 'Aktifkan pengiriman email dan simpan pengaturan terlebih dahulu.');
        }

        try {
            $this->mailer->sendTest($validated['test_email']);
        } catch (Throwable $e) {
            return back()->with('error', $this->explainMailFailure($e->getMessage()));
        }

        return back()->with('success', 'Email uji coba terkirim ke '.$validated['test_email'].'.');
    }

    /**
     * Turns the transport's own wording into something a school operator can act
     * on, while still quoting the original so nothing is hidden.
     */
    private function explainMailFailure(string $error): string
    {
        $hint = match (true) {
            str_contains($error, 'getaddrinfo'),
            str_contains($error, 'php_network_getaddresses'),
            str_contains($error, 'Name or service not known') => 'Host SMTP tidak ditemukan. Periksa ejaannya — yang diisi adalah nama server seperti smtp.gmail.com, bukan alamat email.',

            str_contains($error, 'Connection refused'),
            str_contains($error, 'Connection timed out'),
            str_contains($error, 'timed out') => 'Server email tidak menjawab. Periksa port dan enkripsi (587 dengan TLS, atau 465 dengan SSL), dan pastikan hosting tidak memblokir port tersebut.',

            str_contains($error, 'Authentication failed'),
            str_contains($error, 'Username and Password not accepted'),
            str_contains($error, '535') => 'Username atau kata sandi SMTP ditolak. Untuk Gmail, gunakan App Password 16 karakter, bukan kata sandi akun biasa.',

            str_contains($error, 'certificate'),
            str_contains($error, 'SSL') => 'Sambungan aman gagal. Coba ganti enkripsi antara TLS dan SSL beserta portnya.',

            default => null,
        };

        return $hint === null
            ? 'Email uji coba gagal dikirim: '.$error
            : $hint.' (Pesan asli: '.$error.')';
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
            'hero_overlay' => ['nullable', 'integer', 'min:0', 'max:90'],
            'hero_banner' => ['nullable', 'image', 'mimes:jpg,jpeg,png,webp', 'max:3072'],
            'faq' => ['nullable', 'string', 'max:8000'],

            'map_latitude' => ['nullable', 'numeric', 'between:-90,90'],
            'map_longitude' => ['nullable', 'numeric', 'between:-180,180'],
            'map_zoom' => ['nullable', 'integer', 'min:3', 'max:21'],
            'map_place_name' => ['nullable', 'string', 'max:150'],

            'maintenance_message' => ['nullable', 'string', 'max:500'],

            'logo' => ['nullable', 'image', 'mimes:jpg,jpeg,png,webp', 'max:1024'],

            'mail_mailer' => ['required', Rule::in(['smtp', 'log'])],
            // A hostname, not an address. Typing the account (smtp@gmail.com)
            // where the server belongs (smtp.gmail.com) fails much later with an
            // opaque DNS error, so it is rejected here instead.
            'mail_host' => ['nullable', 'string', 'max:150', 'regex:/^[A-Za-z0-9]([A-Za-z0-9\-\.]*[A-Za-z0-9])?$/'],
            'mail_port' => ['nullable', 'integer', 'min:1', 'max:65535'],
            'mail_username' => ['nullable', 'string', 'max:150'],
            'mail_password' => ['nullable', 'string', 'max:200'],
            'mail_encryption' => ['nullable', Rule::in(['tls', 'ssl', 'none'])],
            'mail_from_address' => ['nullable', 'email:rfc', 'max:150'],
            'mail_from_name' => ['nullable', 'string', 'max:150'],
        ], [
            'logo.max' => 'Ukuran logo maksimal 1 MB.',
            'hero_banner.max' => 'Ukuran banner maksimal 3 MB.',
            'school_website.url' => 'Alamat website harus lengkap, contoh: https://sekolah.sch.id',
            'mail_from_address.email' => 'Alamat pengirim harus berupa email yang valid.',
            'map_latitude.numeric' => 'Lintang harus berupa angka, contoh: -8.6529.',
            'map_longitude.numeric' => 'Bujur harus berupa angka, contoh: 116.5421.',
            'mail_host.regex' => 'Host SMTP adalah nama server, bukan alamat email. Contoh: smtp.gmail.com (bukan smtp@gmail.com).',
        ]);

        unset($validated['logo'], $validated['hero_banner']);

        // Checkboxes are absent from the payload when unchecked, so they are
        // read from the request rather than the validated set.
        $validated['mail_enabled'] = $request->boolean('mail_enabled') ? '1' : '0';
        $validated['mail_require_verification'] = $request->boolean('mail_require_verification') ? '1' : '0';

        $plainPassword = $validated['mail_password'] ?? null;
        unset($validated['mail_password']);

        foreach (self::GROUPS as $group => $keys) {
            $this->settings->setMany(
                collect($keys)->mapWithKeys(fn (string $key) => [$key => $validated[$key] ?? null])->all(),
                $group
            );
        }

        // Left blank means "keep the stored password": the form never echoes it
        // back, so an empty field must not wipe a working configuration.
        if (filled($plainPassword)) {
            $this->settings->set('mail_password', $this->mail->encrypt($plainPassword), 'mail');
        }

        if ($request->hasFile('logo')) {
            $this->settings->set(
                'school_logo',
                $this->images->replace($request->file('logo'), 'branding', $this->settings->raw('school_logo')),
                'school'
            );
        }

        if ($request->hasFile('hero_banner')) {
            $this->settings->set(
                'hero_banner',
                $this->images->replace($request->file('hero_banner'), 'branding', $this->settings->raw('hero_banner')),
                'homepage'
            );
        }

        // An explicit tick is the only way to clear a banner, since an empty
        // file input cannot mean "remove the one already there".
        if ($request->boolean('remove_hero_banner')) {
            $this->images->delete($this->settings->raw('hero_banner'));
            $this->settings->set('hero_banner', null, 'homepage');
        }

        $this->activity->log(ActivityLogger::CONFIGURATION_CHANGED, 'Pengaturan sistem diperbarui.');

        return back()->with('success', 'Pengaturan berhasil disimpan.');
    }
}
