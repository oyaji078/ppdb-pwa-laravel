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
use Illuminate\Foundation\Testing\TestCase as BaseTestCase;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Storage;

abstract class TestCase extends BaseTestCase
{
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

        $registration = app(RegistrationService::class)
            ->startDraft($config['year'], $config['wave'], $config['track']);

        $registration->applicant->update(array_merge([
            'nisn' => str_pad((string) (1000000000 + $sequence), 10, '0', STR_PAD_LEFT),
            'nik' => str_pad((string) (5203000000000000 + $sequence), 16, '0', STR_PAD_LEFT),
            'full_name' => 'Calon Siswa '.$sequence,
            'gender' => 'L',
            'birth_place' => 'Selong',
            'birth_date' => '2010-05-17',
            'religion' => 'Islam',
            'phone' => '081234567890',
        ], $applicantOverrides));

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
     * Authenticate as an applicant without going through the status form.
     */
    protected function actingAsApplicant(Registration $registration): static
    {
        $this->withSession([
            config('ppdb.applicant_session.key') => $registration->id,
            config('ppdb.applicant_session.token_key') => $registration->session_version,
        ]);

        return $this;
    }
}
