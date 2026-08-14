<?php

namespace Tests\Feature;

use App\Enums\UserRole;
use App\Services\RegistrationService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\File;
use Tests\TestCase;

class SecurityHardeningTest extends TestCase
{
    use RefreshDatabase;

    public function test_security_headers_are_sent_on_every_web_response(): void
    {
        $this->createPpdbConfiguration();

        $this->get(route('home'))
            ->assertOk()
            ->assertHeader('X-Content-Type-Options', 'nosniff')
            ->assertHeader('X-Frame-Options', 'SAMEORIGIN')
            ->assertHeader('Referrer-Policy', 'strict-origin-when-cross-origin');
    }

    public function test_the_registration_wizard_is_never_cached(): void
    {
        $this->createPpdbConfiguration();

        $this->get(route('registration.start'))->assertHeaderContains('Cache-Control', 'no-store');
        $this->get(route('status.form'))->assertHeaderContains('Cache-Control', 'no-store');
    }

    public function test_forms_carry_a_csrf_token(): void
    {
        $this->createPpdbConfiguration();

        $this->get(route('status.form'))
            ->assertOk()
            ->assertSee('name="_token"', false);

        $this->get(route('admin.login'))
            ->assertOk()
            ->assertSee('name="_token"', false);
    }

    public function test_identity_numbers_are_masked_on_list_and_summary_views(): void
    {
        $this->fakePrivateDisk();
        $config = $this->createPpdbConfiguration();
        $registration = $this->createSubmittableDraft($config, ['nik' => '5203010101100099']);
        app(RegistrationService::class)->submit($registration, statementAgreed: true);

        $this->actingAsApplicant($registration->fresh())
            ->get(route('applicant.biodata'))
            ->assertOk()
            ->assertDontSee('5203010101100099')
            ->assertSee('5203********0099');
    }

    public function test_a_verifier_sees_masked_identity_numbers_on_the_detail_page(): void
    {
        $this->fakePrivateDisk();
        $config = $this->createPpdbConfiguration();
        $registration = $this->createSubmittableDraft($config, ['nik' => '5203010101100077']);
        app(RegistrationService::class)->submit($registration, statementAgreed: true);

        $verifier = $this->createAdmin(UserRole::Verifier);

        $this->actingAs($verifier)
            ->get(route('admin.registrations.show', $registration->fresh()))
            ->assertOk()
            ->assertDontSee('5203010101100077')
            ->assertSee('5203********0077');
    }

    public function test_an_admin_ppdb_may_see_full_identity_numbers(): void
    {
        $this->fakePrivateDisk();
        $config = $this->createPpdbConfiguration();
        $registration = $this->createSubmittableDraft($config, ['nik' => '5203010101100066']);
        app(RegistrationService::class)->submit($registration, statementAgreed: true);

        $this->actingAs($this->createAdmin(UserRole::AdminPpdb))
            ->get(route('admin.registrations.show', $registration->fresh()))
            ->assertOk()
            ->assertSee('5203010101100066');
    }

    public function test_the_access_code_hash_is_hidden_from_model_serialisation(): void
    {
        $this->fakePrivateDisk();
        $config = $this->createPpdbConfiguration();
        $registration = $this->createSubmittableDraft($config);
        app(RegistrationService::class)->submit($registration, statementAgreed: true);

        $serialised = $registration->fresh()->toArray();

        $this->assertArrayNotHasKey('access_code_hash', $serialised);
    }

    public function test_the_private_disk_has_no_public_url(): void
    {
        $disk = config('filesystems.disks.'.config('ppdb.storage.disk'));

        $this->assertFalse($disk['serve'] ?? true, 'disk privat tidak boleh melayani berkas lewat route bawaan');
        $this->assertArrayNotHasKey('url', $disk, 'disk privat tidak boleh punya URL publik');
        $this->assertSame('private', $disk['visibility'] ?? null);
    }

    public function test_uploads_live_outside_the_public_directory(): void
    {
        $root = config('filesystems.disks.'.config('ppdb.storage.disk').'.root');

        $this->assertStringNotContainsString(
            DIRECTORY_SEPARATOR.'public',
            $root,
            'berkas pendaftar tidak boleh berada di dalam direktori public'
        );
    }

    public function test_the_env_file_is_not_reachable_over_http(): void
    {
        $this->get('/.env')->assertNotFound();
        $this->get('/storage/../.env')->assertNotFound();
    }

    public function test_the_public_directory_contains_no_applicant_files(): void
    {
        $publicFiles = File::exists(public_path('storage'))
            ? File::allFiles(public_path('storage'))
            : [];

        foreach ($publicFiles as $file) {
            $this->assertStringNotContainsString(
                'ppdb'.DIRECTORY_SEPARATOR,
                $file->getRelativePathname(),
                'berkas pendaftar tidak boleh ada di storage publik'
            );
        }

        $this->assertTrue(true);
    }

    public function test_passwords_are_hashed_not_stored(): void
    {
        $admin = $this->createAdmin();

        $this->assertNotSame('password', $admin->password);
        $this->assertStringStartsWith('$2y$', $admin->password);
    }

    public function test_the_admin_area_rejects_an_applicant_session(): void
    {
        $this->fakePrivateDisk();
        $config = $this->createPpdbConfiguration();
        $registration = $this->createSubmittableDraft($config);
        app(RegistrationService::class)->submit($registration, statementAgreed: true);

        // An applicant session must never satisfy an admin route.
        $this->actingAsApplicant($registration->fresh())
            ->get(route('admin.dashboard'))
            ->assertRedirect(route('admin.login'));
    }

    public function test_an_admin_session_does_not_grant_the_applicant_portal(): void
    {
        $this->actingAs($this->createAdmin())
            ->get(route('applicant.dashboard'))
            ->assertRedirect(route('status.form'));
    }
}
