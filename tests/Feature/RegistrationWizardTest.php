<?php

namespace Tests\Feature;

use App\Enums\DocumentStatus;
use App\Enums\RegistrationStatus;
use App\Enums\ReregistrationStatus;
use App\Enums\SelectionStatus;
use App\Enums\UserRole;
use App\Models\AcademicYear;
use App\Models\AdmissionTrack;
use App\Models\DocumentType;
use App\Models\Program;
use App\Models\Registration;
use App\Models\RegistrationWave;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Illuminate\Testing\TestResponse;
use Tests\TestCase;

/**
 * Walks the whole system the way a real intake does, from an empty database to
 * a finished report — configuration, registration, verification, revision,
 * selection, publication and reregistration, with no direct database edits.
 */
class RegistrationWizardTest extends TestCase
{
    use RefreshDatabase;

    public function test_the_wizard_saves_each_step_as_a_draft(): void
    {
        $this->fakePrivateDisk();
        $config = $this->createPpdbConfiguration();

        $this->get(route('registration.start'))->assertOk();

        $this->openAccount($config)->assertRedirect(route('registration.biodata'));

        $draft = Registration::query()->firstOrFail();

        $this->assertSame(RegistrationStatus::Draft, $draft->registration_status);
        // Credentials are issued up front so the applicant can log back in.
        $this->assertNotNull($draft->registration_number);
        $this->assertNotNull($draft->user, 'akun pendaftar dibuat bersama pendaftaran');
        $this->assertTrue($draft->user->isApplicant());

        $this->post(route('registration.biodata.store'), $this->biodata())
            ->assertRedirect(route('registration.address'))
            ->assertSessionHasNoErrors();

        $this->assertSame('Ahmad Fauzi', $draft->fresh()->applicant->full_name);

        $this->post(route('registration.address.store'), $this->address())
            ->assertRedirect(route('registration.parents'));

        $this->post(route('registration.parents.store'), $this->parents())
            ->assertRedirect(route('registration.previous-school'));

        $this->post(route('registration.previous-school.store'), $this->school())
            ->assertRedirect(route('registration.program'));

        $this->post(route('registration.program.store'), ['program_id' => $config['program']->id])
            ->assertRedirect(route('registration.documents'));

        $draft->refresh();

        $this->assertSame($config['program']->id, $draft->program_id);
        $this->assertNotNull($draft->applicant->address);
        $this->assertCount(2, $draft->applicant->parentGuardians);
        $this->assertNotNull($draft->applicant->previousSchool);
    }

    public function test_the_biodata_step_rejects_an_invalid_nisn(): void
    {
        $this->fakePrivateDisk();
        $config = $this->createPpdbConfiguration();

        $this->openAccount($config);

        $this->from(route('registration.biodata'))
            ->post(route('registration.biodata.store'), array_merge($this->biodata(), ['nisn' => '123']))
            ->assertSessionHasErrors('nisn');
    }

    public function test_the_review_step_lists_what_is_still_missing(): void
    {
        $this->fakePrivateDisk();
        $config = $this->createPpdbConfiguration();

        $this->openAccount($config);
        $this->post(route('registration.biodata.store'), $this->biodata());

        $this->get(route('registration.review'))
            ->assertOk()
            ->assertSee('Pendaftaran belum dapat dikirim')
            ->assertSee('Data alamat belum lengkap.');
    }

    public function test_submitting_without_the_statement_is_refused(): void
    {
        $this->fakePrivateDisk();
        $config = $this->createPpdbConfiguration();

        $this->completeWizard($config);

        $this->from(route('registration.review'))
            ->post(route('registration.submit'), [])
            ->assertSessionHasErrors('statement_agreed');

        $this->assertNull(Registration::query()->firstOrFail()->submitted_at);
    }

    /**
     * The form now lives behind the applicant session, so a visitor without one
     * is sent to the login page rather than back to step one.
     */
    public function test_the_form_cannot_be_opened_without_signing_in(): void
    {
        $this->createPpdbConfiguration();

        $this->get(route('registration.biodata'))->assertRedirect(route('login'));
        $this->get(route('registration.review'))->assertRedirect(route('login'));
        $this->get(route('registration.documents'))->assertRedirect(route('login'));
    }

    public function test_a_second_account_cannot_be_opened_for_the_same_nisn(): void
    {
        $this->fakePrivateDisk();
        $config = $this->createPpdbConfiguration();

        $this->openAccount($config)->assertRedirect(route('registration.biodata'));

        $this->post(route('logout'));

        $this->from(route('registration.start'))
            ->openAccount($config)
            ->assertSessionHasErrors('nisn');

        $this->assertSame(1, Registration::query()->count());
    }

    public function test_registration_is_blocked_when_the_year_is_closed(): void
    {
        $config = $this->createPpdbConfiguration();
        $config['year']->update(['registration_open' => false]);

        $this->get(route('registration.start'))
            ->assertRedirect(route('ppdb.index'));
    }

    public function test_the_full_admission_cycle_works_end_to_end(): void
    {
        $this->fakePrivateDisk();

        // --- 1. Admin signs in and builds the configuration -----------------
        $superAdmin = $this->createAdmin(UserRole::SuperAdmin);

        $this->post(route('login.store'), [
            'email' => $superAdmin->email,
            'password' => 'password',
        ])->assertRedirect(route('admin.dashboard'));

        $this->post(route('admin.academic-years.store'), [
            'name' => '2026/2027',
            'start_year' => 2026,
            'end_year' => 2027,
            'is_active' => '1',
            'registration_open' => '1',
        ])->assertSessionHasNoErrors();

        $year = AcademicYear::query()->where('name', '2026/2027')->firstOrFail();

        $this->post(route('admin.waves.store'), [
            'academic_year_id' => $year->id,
            'name' => 'Gelombang 1',
            'code' => '01',
            'start_at' => now()->subDay()->format('Y-m-d\TH:i'),
            'end_at' => now()->addMonth()->format('Y-m-d\TH:i'),
            'quota' => 100,
            'is_active' => '1',
        ])->assertSessionHasNoErrors();

        $this->post(route('admin.tracks.store'), [
            'academic_year_id' => $year->id,
            'name' => 'Reguler',
            'code' => 'reguler',
            'description' => 'Jalur umum.',
            'is_active' => '1',
        ])->assertSessionHasNoErrors();

        $this->post(route('admin.programs.store'), [
            'academic_year_id' => $year->id,
            'name' => 'MIPA',
            'code' => 'mipa',
            'quota' => 60,
            'is_active' => '1',
        ])->assertSessionHasNoErrors();

        $this->post(route('admin.document-types.store'), [
            'academic_year_id' => $year->id,
            'name' => 'Kartu Keluarga',
            'code' => 'kk',
            'allowed_extensions' => ['pdf'],
            'max_size_kb' => 2048,
            'is_required' => '1',
            'requires_verification' => '1',
            'is_active' => '1',
        ])->assertSessionHasNoErrors();

        $this->post(route('admin.schedules.store'), [
            'academic_year_id' => $year->id,
            'title' => 'Pendaftaran Gelombang 1',
            'start_at' => now()->subDay()->format('Y-m-d\TH:i'),
            'end_at' => now()->addMonth()->format('Y-m-d\TH:i'),
            'is_public' => '1',
        ])->assertSessionHasNoErrors();

        $wave = RegistrationWave::query()->firstOrFail();
        $track = AdmissionTrack::query()->firstOrFail();
        $program = Program::query()->firstOrFail();
        $documentType = DocumentType::query()->firstOrFail();

        // Attach the document requirement to the track.
        $this->put(route('admin.tracks.requirements', $track), [
            'documents' => [$documentType->id],
            'required' => [$documentType->id],
        ])->assertSessionHasNoErrors();

        $this->assertSame(1, $track->fresh()->documentTypes()->count());

        $this->post(route('logout'));

        // --- 2. An applicant registers --------------------------------------
        $this->get(route('home'))->assertOk()->assertSee('Daftar Sekarang');

        $this->openAccount(['wave' => $wave, 'track' => $track])
            ->assertRedirect(route('registration.biodata'));

        $this->post(route('registration.biodata.store'), $this->biodata());
        $this->post(route('registration.address.store'), $this->address());
        $this->post(route('registration.parents.store'), $this->parents());
        $this->post(route('registration.previous-school.store'), $this->school());
        $this->post(route('registration.program.store'), ['program_id' => $program->id]);

        $this->post(route('registration.documents.store', $documentType), [
            'file' => UploadedFile::fake()->create('kk.pdf', 150, 'application/pdf'),
        ])->assertSessionHasNoErrors();

        $this->get(route('registration.review'))->assertOk()->assertSee('Data Anda sudah lengkap');

        // --- 3. Submit -------------------------------------------------------
        $this->post(route('registration.submit'), ['statement_agreed' => '1'])
            ->assertRedirect(route('registration.success'))
            ->assertSessionHasNoErrors();

        $registration = Registration::query()->firstOrFail();

        $this->assertSame('2601000001', $registration->registration_number);
        $this->assertSame(RegistrationStatus::Submitted, $registration->registration_status);

        // The applicant chose the access code at sign-up, so the success page
        // shows the number only and can be reloaded safely.
        $accessCode = self::ACCESS_CODE;

        $this->get(route('registration.success'))
            ->assertOk()
            ->assertSee($registration->registration_number)
            ->assertDontSee($accessCode)
            ->assertSee('Cara masuk kembali');

        $this->get(route('registration.success'))
            ->assertOk()
            ->assertSee($registration->registration_number)
            ->assertDontSee($accessCode);

        $this->get(route('registration.success.receipt'))
            ->assertOk()
            ->assertHeader('Content-Type', 'application/pdf');

        // --- 4. Applicant signs in with number + code ------------------------
        $this->post(route('logout'));

        $this->post(route('login.store'), [
            'email' => $registration->user->email,
            'password' => $accessCode,
        ])->assertRedirect(route('applicant.dashboard'));

        $this->get(route('applicant.dashboard'))
            ->assertOk()
            ->assertSee('Ahmad Fauzi')
            ->assertSee('Menunggu Verifikasi');

        // --- 5. Admin asks for a revision ------------------------------------
        $verifier = $this->createAdmin(UserRole::Verifier);
        $document = $registration->documents()->firstOrFail();

        $this->actingAs($verifier)
            ->post(route('admin.verification.decide', $document), [
                'verification_status' => DocumentStatus::RevisionRequired->value,
                'verification_note' => 'Kartu keluarga kurang jelas, mohon unggah ulang.',
            ])
            ->assertSessionHasNoErrors();

        $this->assertSame(RegistrationStatus::RevisionRequired, $registration->fresh()->registration_status);

        // --- 6. Applicant sees the note and re-uploads ------------------------
        $this->actingAsApplicant($registration->fresh())
            ->get(route('applicant.documents.index'))
            ->assertOk()
            ->assertSee('Kartu keluarga kurang jelas, mohon unggah ulang.');

        $this->actingAsApplicant($registration->fresh())
            ->post(route('applicant.documents.store', $documentType), [
                'file' => UploadedFile::fake()->create('kk-jelas.pdf', 200, 'application/pdf'),
            ])
            ->assertSessionHasNoErrors();

        $this->assertSame(DocumentStatus::Pending, $document->fresh()->verification_status);

        // --- 7. Admin verifies ------------------------------------------------
        $this->actingAs($verifier)->post(route('admin.verification.decide', $document->fresh()), [
            'verification_status' => DocumentStatus::Verified->value,
        ]);

        $this->actingAs($verifier)
            ->post(route('admin.verification.complete', $registration))
            ->assertSessionHasNoErrors();

        $this->assertSame(RegistrationStatus::Verified, $registration->fresh()->registration_status);

        // --- 8. Selection: decided, then published ---------------------------
        $this->actingAs($superAdmin)
            ->post(route('admin.selection.store', $registration), [
                'status' => SelectionStatus::Accepted->value,
                'score' => 91,
                'rank' => 1,
                'note' => 'Selamat, Anda diterima.',
            ])
            ->assertSessionHasNoErrors();

        // Not visible before publication.
        $this->assertFalse($registration->fresh()->hasPublishedResult());

        $this->actingAsApplicant($registration->fresh())
            ->get(route('applicant.dashboard'))
            ->assertOk()
            ->assertSee('Hasil seleksi belum diumumkan');

        $this->actingAs($superAdmin)
            ->post(route('admin.selection.publish', $registration))
            ->assertSessionHasNoErrors();

        $this->actingAsApplicant($registration->fresh())
            ->get(route('applicant.dashboard'))
            ->assertOk()
            ->assertSee('Hasil Seleksi: Diterima');

        // --- 9. Reregistration -------------------------------------------------
        $this->assertSame(ReregistrationStatus::Pending, $registration->fresh()->reregistration_status);

        $this->actingAs($superAdmin)
            ->post(route('admin.reregistration.update', $registration), [
                'status' => ReregistrationStatus::Completed->value,
                'notes' => 'Daftar ulang selesai.',
            ])
            ->assertSessionHasNoErrors();

        $registration->refresh();

        $this->assertSame(ReregistrationStatus::Completed, $registration->reregistration_status);
        $this->assertNotNull($registration->reregistered_at);

        // --- 10. Reports --------------------------------------------------------
        $this->actingAs($superAdmin)
            ->get(route('admin.reports.index', ['preset' => 'diterima']))
            ->assertOk()
            ->assertSee($registration->registration_number);

        $csv = $this->actingAs($superAdmin)
            ->get(route('admin.reports.export', ['preset' => 'diterima', 'format' => 'csv']))
            ->assertOk();

        $this->assertStringContainsString(
            $registration->registration_number,
            $csv->streamedContent()
        );

        $this->actingAs($superAdmin)
            ->get(route('admin.reports.export', ['preset' => 'semua', 'format' => 'pdf']))
            ->assertOk()
            ->assertHeader('Content-Type', 'application/pdf');

        // streamedContent() forces the download callback to run; asserting the
        // status alone would pass even if the writer threw mid-stream.
        $xlsx = $this->actingAs($superAdmin)
            ->get(route('admin.reports.export', ['preset' => 'semua', 'format' => 'xlsx']))
            ->assertOk()
            ->streamedContent();

        $this->assertStringStartsWith('PK', $xlsx, 'berkas XLSX harus berupa arsip zip yang valid');
    }

    /**
     * @return array<string, string>
     */
    private function biodata(): array
    {
        return [
            'nisn' => '1234567890',
            'nik' => '5203010101100001',
            'family_card_number' => '5203010101100002',
            'full_name' => 'Ahmad Fauzi',
            'gender' => 'L',
            'birth_place' => 'Selong',
            'birth_date' => '2010-05-17',
            'religion' => 'Islam',
            'child_order' => '1',
            'siblings_count' => '2',
            'phone' => '081234567890',
            'email' => 'ahmad@example.test',
        ];
    }

    /**
     * @return array<string, string>
     */
    private function address(): array
    {
        return [
            'province' => 'Nusa Tenggara Barat',
            'regency' => 'Lombok Timur',
            'district' => 'Peneda',
            'village' => 'Peneda Gandor',
            'postal_code' => '83651',
            'address' => 'Jl. Pendidikan No. 12, Dusun Peneda, RT 003 / RW 001',
        ];
    }

    /**
     * @return array<string, mixed>
     */
    private function parents(): array
    {
        return [
            'father' => [
                'name' => 'Muhammad Yusuf',
                'nik' => '5203010101800001',
                'birth_date' => '1980-04-12',
                'education' => 'SMA/Sederajat',
                'occupation' => 'Petani',
                'monthly_income' => 'Rp1.000.000 - Rp2.000.000',
                'phone' => '081234567891',
                'is_alive' => '1',
            ],
            'mother' => [
                'name' => 'Siti Aminah',
                'nik' => '5203010101850001',
                'birth_date' => '1985-09-30',
                'education' => 'SMP/Sederajat',
                'occupation' => 'Ibu Rumah Tangga',
                'monthly_income' => 'Tidak Berpenghasilan',
                'phone' => '081234567892',
                'is_alive' => '1',
            ],
            'guardian' => ['name' => ''],
        ];
    }

    /**
     * @return array<string, string>
     */
    private function school(): array
    {
        return [
            'school_name' => 'SMP Negeri 1 Peneda',
            'npsn' => '50201234',
            'school_type' => 'SMP',
            'school_status' => 'Negeri',
            'province' => 'Nusa Tenggara Barat',
            'regency' => 'Lombok Timur',
            'graduation_year' => (string) now()->year,
        ];
    }

    /**
     * Step zero of the real flow: open the account and land logged in on the
     * first form step. Identity matches biodata() so the two never disagree.
     *
     * @param  array<string, mixed>  $config
     * @param  array<string, mixed>  $overrides
     */
    private function openAccount(array $config, array $overrides = []): TestResponse
    {
        return $this->post(route('registration.start.store'), array_merge([
            'registration_wave_id' => $config['wave']->id,
            'admission_track_id' => $config['track']->id,
            'full_name' => 'Ahmad Fauzi',
            'nisn' => '1234567890',
            'phone' => '081234567890',
            'email' => 'ahmad@example.test',
            'password' => self::ACCESS_CODE,
            'password_confirmation' => self::ACCESS_CODE,
        ], $overrides));
    }

    /**
     * @param  array<string, mixed>  $config
     */
    private function completeWizard(array $config): void
    {
        $this->openAccount($config);
        $this->post(route('registration.biodata.store'), $this->biodata());
        $this->post(route('registration.address.store'), $this->address());
        $this->post(route('registration.parents.store'), $this->parents());
        $this->post(route('registration.previous-school.store'), $this->school());
        $this->post(route('registration.program.store'), ['program_id' => $config['program']->id]);

        foreach ($config['documentTypes'] as $type) {
            if ($type->is_required) {
                $this->post(route('registration.documents.store', $type), [
                    'file' => UploadedFile::fake()->create($type->code.'.pdf', 120, 'application/pdf'),
                ]);
            }
        }
    }
}
