<?php

namespace Tests;

use App\Enums\UserRole;
use App\Models\AcademicYear;
use App\Models\AdmissionTrack;
use App\Models\Applicant;
use App\Models\DocumentType;
use App\Models\Program;
use App\Models\Registration;
use App\Models\RegistrationWave;
use App\Models\User;
use App\Services\DocumentService;
use App\Services\RegistrationService;
use App\Support\SettingsRepository;
use Illuminate\Contracts\Auth\Authenticatable;
use Illuminate\Foundation\Testing\TestCase as BaseTestCase;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Storage;

abstract class TestCase extends BaseTestCase
{
    /**
     * Access code every registration built by createSubmittableDraft() is given,
     * so tests can log in through the real form.
     */
    public const ACCESS_CODE = 'KodeUji#123';

    /**
     * Counts createPpdbConfiguration() calls within a single test so repeated
     * calls produce distinct academic years.
     */
    private int $configSequence = 0;

    protected function setUp(): void
    {
        parent::setUp();

        // Settings are cached forever; a stale cache would leak between tests.
        app(SettingsRepository::class)->flush();
    }

    /**
     * Start a clean session whenever the acting user changes.
     *
     * AuthenticateSession keeps the signed-in password hash in the session so a
     * password change signs that account out everywhere. A test that swaps roles
     * mid-run would otherwise carry the previous user's hash into the next
     * request and be signed straight back out. A real browser never does this —
     * switching users there means signing out first, which clears the session.
     */
    public function actingAs(Authenticatable $user, $guard = null): static
    {
        $this->flushSession();

        return parent::actingAs($user, $guard);
    }

    /**
     * Point the private disk at a fake so uploads never touch real storage.
     */
    protected function fakePrivateDisk(): void
    {
        Storage::fake(config('ppdb.storage.disk'));
    }

    protected function createAdmin(UserRole $role = UserRole::SuperAdmin, array $attributes = []): User
    {
        static $sequence = 0;
        $sequence++;

        $user = User::query()->create(array_merge([
            'username' => 'admin'.$sequence,
            'name' => 'Admin '.$sequence,
            'email' => "admin{$sequence}@ppdb.test",
            'password' => 'password',
            'role' => $role,
            'is_active' => true,
        ], $attributes));

        // Re-read so every column (including remember_token) is hydrated, the
        // way a user loaded from a real session would be.
        return $user->fresh();
    }

    /**
     * A complete, open academic year with one wave, one track, one program and
     * the document types that track requires.
     *
     * @return array{year: AcademicYear, wave: RegistrationWave, track: AdmissionTrack, program: Program, documentTypes: Collection<string, DocumentType>}
     */
    protected function createPpdbConfiguration(array $overrides = []): array
    {
        // Each call within a test gets its own intake year so the unique
        // constraint on academic_years.name never collides.
        $startYear = 2026 + $this->configSequence++;

        $year = AcademicYear::query()->create(array_merge([
            'name' => sprintf('%d/%d', $startYear, $startYear + 1),
            'start_year' => $startYear,
            'end_year' => $startYear + 1,
            'is_active' => true,
            'registration_open' => true,
        ], $overrides['year'] ?? []));

        $wave = RegistrationWave::query()->create(array_merge([
            'academic_year_id' => $year->id,
            'name' => 'Gelombang 1',
            'code' => '01',
            'start_at' => now()->subDays(7),
            'end_at' => now()->addDays(30),
            'quota' => 100,
            'is_active' => true,
        ], $overrides['wave'] ?? []));

        $track = AdmissionTrack::query()->create(array_merge([
            'academic_year_id' => $year->id,
            'name' => 'Reguler',
            'code' => 'reguler',
            'is_active' => true,
            'sort_order' => 1,
        ], $overrides['track'] ?? []));

        $program = Program::query()->create(array_merge([
            'academic_year_id' => $year->id,
            'name' => 'MIPA',
            'code' => 'mipa',
            'quota' => 50,
            'is_active' => true,
        ], $overrides['program'] ?? []));

        $documentTypes = collect([
            'kk' => ['Kartu Keluarga', true, ['pdf', 'jpg', 'jpeg', 'png']],
            'akta' => ['Akta Kelahiran', true, ['pdf', 'jpg', 'jpeg', 'png']],
            'sertifikat' => ['Sertifikat Prestasi', false, ['pdf']],
        ])->mapWithKeys(function (array $definition, string $code) use ($year, $track) {
            [$name, $required, $extensions] = $definition;

            $type = DocumentType::query()->create([
                'academic_year_id' => $year->id,
                'name' => $name,
                'code' => $code,
                'is_required' => $required,
                'requires_verification' => true,
                'allowed_extensions' => $extensions,
                'max_size_kb' => 2048,
                'is_active' => true,
            ]);

            $track->documentTypes()->attach($type->id, ['is_required' => $required]);

            return [$code => $type];
        });

        return compact('year', 'wave', 'track', 'program', 'documentTypes');
    }

    /**
     * A draft registration with every field filled and all required documents
     * uploaded, i.e. ready to submit.
     *
     * @param  array<string, mixed>  $config  Result of createPpdbConfiguration()
     */
    protected function createSubmittableDraft(array $config, array $applicantOverrides = []): Registration
    {
        static $sequence = 0;
        $sequence++;

        $nisn = str_pad((string) (1000000000 + $sequence), 10, '0', STR_PAD_LEFT);
        $email = 'calon'.$sequence.'@example.test';

        // Mirrors the real flow: the account (user, number, password) exists
        // before any of the form is filled in.
        $registration = app(RegistrationService::class)->registerAccount(
            $config['year'],
            $config['wave'],
            $config['track'],
            [
                'full_name' => 'Calon Siswa '.$sequence,
                'nisn' => $nisn,
                'phone' => '081234567890',
                'email' => $email,
            ],
            self::ACCESS_CODE,
        );

        $registration->applicant->update(array_merge([
            'nisn' => $nisn,
            'nik' => str_pad((string) (5203000000000000 + $sequence), 16, '0', STR_PAD_LEFT),
            'full_name' => 'Calon Siswa '.$sequence,
            'gender' => 'L',
            'birth_place' => 'Selong',
            'birth_date' => '2010-05-17',
            'religion' => 'Islam',
            'phone' => '081234567890',
            'email' => $email,
        ], $applicantOverrides));

        $registration->applicant->markEmailAsVerified();

        $registration->applicant->address()->create([
            'province' => 'Nusa Tenggara Barat',
            'regency' => 'Lombok Timur',
            'district' => 'Peneda',
            'village' => 'Peneda Gandor',
            'postal_code' => '83651',
            'address' => 'Jl. Pendidikan No. 1',
        ]);

        $registration->applicant->parentGuardians()->create([
            'relationship' => 'father',
            'name' => 'Ayah Calon Siswa',
            'phone' => '081234567891',
            'is_alive' => true,
        ]);

        $registration->applicant->previousSchool()->create([
            'school_name' => 'SMP Negeri 1 Peneda',
            'school_type' => 'SMP',
            'school_status' => 'Negeri',
            'graduation_year' => 2026,
        ]);

        $registration->update(['program_id' => $config['program']->id]);

        foreach ($config['documentTypes'] as $type) {
            if (! $type->is_required) {
                continue;
            }

            app(DocumentService::class)->store(
                $registration,
                $type,
                UploadedFile::fake()->create($type->code.'.pdf', 120, 'application/pdf')
            );
        }

        return $registration->fresh([
            'applicant.address', 'applicant.parentGuardians', 'applicant.previousSchool',
            'academicYear', 'wave', 'admissionTrack', 'program', 'documents',
        ]);
    }

    /**
     * An empty applicant account: the registration number and access code exist,
     * but none of the form has been filled in yet. This is the state a real
     * applicant is in immediately after signing up.
     *
     * @param  array<string, mixed>  $config  Result of createPpdbConfiguration()
     */
    protected function createAccountFor(array $config, array $identityOverrides = []): Registration
    {
        static $sequence = 0;
        $sequence++;

        return app(RegistrationService::class)->registerAccount(
            $config['year'],
            $config['wave'],
            $config['track'],
            array_merge([
                'full_name' => 'Akun Baru '.$sequence,
                'nisn' => str_pad((string) (2000000000 + $sequence), 10, '0', STR_PAD_LEFT),
                'phone' => '081200000000',
                'email' => 'akun'.$sequence.'@example.test',
            ], $identityOverrides),
            self::ACCESS_CODE,
        );
    }

    /**
     * Authenticate as the applicant who owns this registration, without walking
     * the login form. Applicants are ordinary users now, so this is the same
     * actingAs() every other role uses.
     */
    protected function actingAsApplicant(Registration $registration): static
    {
        $user = $registration->user ?? $registration->fresh()->user;

        if ($user !== null) {
            $this->actingAs($user);
        }

        return $this;
    }
}
