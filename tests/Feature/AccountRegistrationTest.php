<?php

namespace Tests\Feature;

use App\Enums\RegistrationStatus;
use App\Enums\UserRole;
use App\Models\Registration;
use App\Services\AccessCodeService;
use App\Services\ApplicantMailer;
use App\Services\RegistrationService;
use App\Support\MailConfigurator;
use App\Support\SettingsRepository;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Mail;
use Tests\TestCase;

/**
 * The account-first flow: an applicant registers, receives their registration
 * number and their own access code, then logs in and fills the form.
 */
class AccountRegistrationTest extends TestCase
{
    use RefreshDatabase;

    public function test_creating_an_account_issues_a_number_and_signs_the_applicant_in(): void
    {
        $this->fakePrivateDisk();
        $config = $this->createPpdbConfiguration();

        $this->post(route('registration.start.store'), $this->payload($config))
            ->assertRedirect(route('registration.biodata'))
            ->assertSessionHasNoErrors();

        $registration = Registration::query()->firstOrFail();

        $this->assertSame('2601000001', $registration->registration_number);
        $this->assertSame(RegistrationStatus::Draft, $registration->registration_status);
        $this->assertNull($registration->submitted_at);
        $this->assertTrue(app(AccessCodeService::class)->check($registration, self::ACCESS_CODE));

        $this->assertSame('Budi Santoso', $registration->applicant->full_name);
        $this->assertSame('1122334455', $registration->applicant->nisn);
        $this->assertSame('budi@example.test', $registration->applicant->email);

        // Already signed in: the form opens without a separate login.
        $this->get(route('registration.biodata'))->assertOk();
    }

    public function test_the_password_must_be_confirmed_and_long_enough(): void
    {
        $this->fakePrivateDisk();
        $config = $this->createPpdbConfiguration();

        $this->from(route('registration.start'))
            ->post(route('registration.start.store'), $this->payload($config, [
                'password' => 'Rahasia1',
                'password_confirmation' => 'Berbeda1',
            ]))
            ->assertSessionHasErrors('password');

        $this->from(route('registration.start'))
            ->post(route('registration.start.store'), $this->payload($config, [
                'password' => 'pendek',
                'password_confirmation' => 'pendek',
            ]))
            ->assertSessionHasErrors('password');

        $this->assertSame(0, Registration::query()->count());
    }

    public function test_a_returning_applicant_is_pointed_at_the_login_form(): void
    {
        $this->fakePrivateDisk();
        $config = $this->createPpdbConfiguration();

        $this->post(route('registration.start.store'), $this->payload($config));
        $this->post(route('logout'));

        $this->from(route('registration.start'))
            ->post(route('registration.start.store'), $this->payload($config))
            ->assertSessionHasErrors(['nisn' => 'NISN ini sudah memiliki akun pendaftaran. Silakan masuk memakai nomor pendaftaran dan kode akses Anda.']);
    }

    public function test_a_signed_in_applicant_visiting_step_zero_is_sent_back_to_the_form(): void
    {
        $this->fakePrivateDisk();
        $config = $this->createPpdbConfiguration();

        $this->post(route('registration.start.store'), $this->payload($config));

        $this->get(route('registration.start'))->assertRedirect(route('registration.resume'));
    }

    public function test_the_public_login_box_appears_on_the_home_page(): void
    {
        $this->createPpdbConfiguration();

        $this->get(route('home'))
            ->assertOk()
            ->assertSee('Masuk Pendaftar')
            ->assertSee('name="email"', false)
            ->assertSee('name="password"', false);
    }

    // -- E-mail verification --------------------------------------------------

    public function test_the_verification_link_confirms_the_address(): void
    {
        $this->fakePrivateDisk();
        $config = $this->createPpdbConfiguration();

        $this->post(route('registration.start.store'), $this->payload($config));

        $registration = Registration::query()->firstOrFail();
        $this->assertFalse($registration->applicant->hasVerifiedEmail());

        $this->get(app(ApplicantMailer::class)->verificationUrl($registration))
            ->assertRedirect();

        $this->assertTrue($registration->fresh()->applicant->hasVerifiedEmail());
    }

    public function test_an_unsigned_verification_link_is_refused(): void
    {
        $this->fakePrivateDisk();
        $config = $this->createPpdbConfiguration();

        $this->post(route('registration.start.store'), $this->payload($config));
        $registration = Registration::query()->firstOrFail();

        $this->get('/daftar/verifikasi-email/'.$registration->id)->assertForbidden();

        $this->assertFalse($registration->fresh()->applicant->hasVerifiedEmail());
    }

    public function test_submission_is_blocked_when_verification_is_required_and_missing(): void
    {
        $this->fakePrivateDisk();
        $config = $this->createPpdbConfiguration();

        $this->enableMail(['mail_require_verification' => '1']);

        $draft = $this->createSubmittableDraft($config);
        $draft->applicant->forceFill(['email_verified_at' => null])->save();

        $blockers = app(RegistrationService::class)->submissionBlockers($draft->fresh());

        $this->assertNotEmpty(array_filter(
            $blockers,
            fn (string $blocker) => str_contains($blocker, 'belum diverifikasi')
        ));
    }

    public function test_submission_is_not_blocked_when_verification_is_not_required(): void
    {
        $this->fakePrivateDisk();
        $config = $this->createPpdbConfiguration();

        $draft = $this->createSubmittableDraft($config);
        $draft->applicant->forceFill(['email_verified_at' => null])->save();

        $this->assertSame([], app(RegistrationService::class)->submissionBlockers($draft->fresh()));
    }

    /**
     * A school with broken SMTP must still be able to take registrations, so a
     * failing mailer may never surface as an error to the applicant.
     */
    public function test_a_failing_mailer_does_not_break_account_creation(): void
    {
        $this->fakePrivateDisk();
        $config = $this->createPpdbConfiguration();

        $this->enableMail();

        Mail::shouldReceive('send')->andThrow(new \RuntimeException('SMTP mati'));
        Mail::shouldReceive('purge')->andReturnNull();

        $this->post(route('registration.start.store'), $this->payload($config))
            ->assertRedirect(route('registration.biodata'))
            ->assertSessionHasNoErrors();

        $this->assertSame(1, Registration::query()->count());
    }

    // -- Admin mail settings --------------------------------------------------

    public function test_a_super_admin_can_store_smtp_credentials_encrypted(): void
    {
        $admin = $this->createAdmin(UserRole::SuperAdmin);

        $this->actingAs($admin)
            ->put(route('admin.settings.update'), [
                'school_name' => 'SMA Uji',
                'admission_name' => 'PPDB',
                'mail_enabled' => '1',
                'mail_mailer' => 'smtp',
                'mail_host' => 'smtp.example.test',
                'mail_port' => '587',
                'mail_username' => 'akun@example.test',
                'mail_password' => 'RahasiaSmtp',
                'mail_encryption' => 'tls',
                'mail_from_address' => 'ppdb@example.test',
                'mail_from_name' => 'PPDB Uji',
            ])
            ->assertRedirect()
            ->assertSessionHasNoErrors();

        $settings = app(SettingsRepository::class);
        $settings->flush();

        $this->assertSame('smtp.example.test', $settings->get('mail_host'));
        $this->assertNotSame('RahasiaSmtp', $settings->raw('mail_password'), 'kata sandi SMTP harus terenkripsi');
        $this->assertSame('RahasiaSmtp', app(MailConfigurator::class)->password());
    }

    /**
     * A stored password is never rendered back into the form, so the empty box
     * has to say plainly that one is on file — otherwise it reads as "my
     * password disappeared" and gets retyped on every save.
     */
    public function test_the_settings_screen_says_whether_a_password_is_stored(): void
    {
        $admin = $this->createAdmin(UserRole::SuperAdmin);

        $this->actingAs($admin)
            ->get(route('admin.settings.edit'))
            ->assertOk()
            ->assertSee('Belum ada kata sandi tersimpan');

        $this->actingAs($admin)->put(route('admin.settings.update'), [
            'school_name' => 'SMA Uji',
            'admission_name' => 'PPDB',
            'mail_mailer' => 'smtp',
            'mail_password' => 'RahasiaSmtp',
        ]);

        $this->actingAs($admin)
            ->get(route('admin.settings.edit'))
            ->assertOk()
            ->assertSee('Kata sandi tersimpan')
            ->assertDontSee('RahasiaSmtp');
    }

    public function test_the_settings_screen_explains_what_to_fill_in(): void
    {
        $this->actingAs($this->createAdmin(UserRole::SuperAdmin))
            ->get(route('admin.settings.edit'))
            ->assertOk()
            ->assertSee('Bingung diisi apa')
            ->assertSee('smtp.gmail.com')
            ->assertSee('App Password')
            ->assertSee('smtp-mail.outlook.com');
    }

    public function test_leaving_the_smtp_password_blank_keeps_the_stored_one(): void
    {
        $admin = $this->createAdmin(UserRole::SuperAdmin);

        $base = [
            'school_name' => 'SMA Uji',
            'admission_name' => 'PPDB',
            'mail_enabled' => '1',
            'mail_mailer' => 'smtp',
            'mail_host' => 'smtp.example.test',
            'mail_port' => '587',
        ];

        $this->actingAs($admin)->put(route('admin.settings.update'), $base + ['mail_password' => 'RahasiaSmtp']);
        $this->actingAs($admin)->put(route('admin.settings.update'), $base + ['mail_password' => '']);

        app(SettingsRepository::class)->flush();

        $this->assertSame('RahasiaSmtp', app(MailConfigurator::class)->password());
    }

    /**
     * Typing the mail account where the server belongs is the single most common
     * setup mistake, and SMTP only reports it much later as an opaque DNS error:
     * "getaddrinfo for smtp@gmail.com failed".
     */
    public function test_an_email_address_is_refused_as_an_smtp_host(): void
    {
        $admin = $this->createAdmin(UserRole::SuperAdmin);

        $this->actingAs($admin)
            ->from(route('admin.settings.edit'))
            ->put(route('admin.settings.update'), [
                'school_name' => 'SMA Uji',
                'admission_name' => 'PPDB',
                'mail_mailer' => 'smtp',
                'mail_host' => 'smtp@gmail.com',
                'mail_port' => '587',
            ])
            ->assertSessionHasErrors(['mail_host' => 'Host SMTP adalah nama server, bukan alamat email. Contoh: smtp.gmail.com (bukan smtp@gmail.com).']);

        $this->actingAs($admin)
            ->put(route('admin.settings.update'), [
                'school_name' => 'SMA Uji',
                'admission_name' => 'PPDB',
                'mail_mailer' => 'smtp',
                'mail_host' => 'smtp.gmail.com',
                'mail_port' => '587',
            ])
            ->assertSessionHasNoErrors();
    }

    /**
     * The raw transport error is kept, but prefixed with what to actually do
     * about it.
     */
    public function test_a_failed_test_mail_explains_the_likely_cause(): void
    {
        $admin = $this->createAdmin(UserRole::SuperAdmin);

        $this->enableMail();

        Mail::shouldReceive('send')->andThrow(new \RuntimeException(
            'Connection could not be established with host "smtp.example.test:587": '
            .'stream_socket_client(): php_network_getaddresses: getaddrinfo for smtp.example.test failed'
        ));
        Mail::shouldReceive('purge')->andReturnNull();

        $this->actingAs($admin)
            ->from(route('admin.settings.edit'))
            ->post(route('admin.settings.test-mail'), ['test_email' => 'panitia@example.test'])
            ->assertRedirect(route('admin.settings.edit'));

        $message = session('error');

        $this->assertStringContainsString('Host SMTP tidak ditemukan', $message);
        $this->assertStringContainsString('getaddrinfo', $message, 'pesan asli tetap ditampilkan');
    }

    public function test_a_rejected_smtp_password_is_explained(): void
    {
        $admin = $this->createAdmin(UserRole::SuperAdmin);

        $this->enableMail();

        Mail::shouldReceive('send')->andThrow(new \RuntimeException('535 Authentication failed'));
        Mail::shouldReceive('purge')->andReturnNull();

        $this->actingAs($admin)
            ->from(route('admin.settings.edit'))
            ->post(route('admin.settings.test-mail'), ['test_email' => 'panitia@example.test']);

        $this->assertStringContainsString('App Password', session('error'));
    }

    public function test_the_test_mail_button_reports_a_failure_instead_of_throwing(): void
    {
        $admin = $this->createAdmin(UserRole::SuperAdmin);

        $this->enableMail();

        Mail::shouldReceive('send')->andThrow(new \RuntimeException('Connection refused'));
        Mail::shouldReceive('purge')->andReturnNull();

        $this->actingAs($admin)
            ->from(route('admin.settings.edit'))
            ->post(route('admin.settings.test-mail'), ['test_email' => 'panitia@example.test'])
            ->assertRedirect(route('admin.settings.edit'))
            ->assertSessionHas('error');
    }

    public function test_only_a_super_admin_reaches_the_settings_screen(): void
    {
        $this->actingAs($this->createAdmin(UserRole::AdminPpdb))
            ->get(route('admin.settings.edit'))
            ->assertForbidden();
    }

    /**
     * @param  array<string, string>  $overrides
     */
    private function enableMail(array $overrides = []): void
    {
        $settings = app(SettingsRepository::class);

        $settings->setMany(array_merge([
            'mail_enabled' => '1',
            'mail_mailer' => 'smtp',
            'mail_host' => 'smtp.example.test',
            'mail_port' => '587',
        ], $overrides), 'mail');

        $settings->flush();
    }

    /**
     * @param  array<string, mixed>  $config
     * @param  array<string, mixed>  $overrides
     * @return array<string, mixed>
     */
    private function payload(array $config, array $overrides = []): array
    {
        return array_merge([
            'registration_wave_id' => $config['wave']->id,
            'admission_track_id' => $config['track']->id,
            'full_name' => 'Budi Santoso',
            'nisn' => '1122334455',
            'phone' => '081298765432',
            'email' => 'budi@example.test',
            'password' => self::ACCESS_CODE,
            'password_confirmation' => self::ACCESS_CODE,
        ], $overrides);
    }
}
