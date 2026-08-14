<?php

namespace Tests\Feature;

use App\Models\Registration;
use App\Services\RegistrationService;
use Illuminate\Database\UniqueConstraintViolationException;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Validation\ValidationException;
use Tests\TestCase;

class DuplicateApplicantTest extends TestCase
{
    use RefreshDatabase;

    public function test_the_same_nisn_cannot_be_submitted_twice_in_one_academic_year(): void
    {
        $this->fakePrivateDisk();
        $config = $this->createPpdbConfiguration();
        $service = app(RegistrationService::class);

        $first = $this->createSubmittableDraft($config, ['nisn' => '1234567890']);
        $service->submit($first, statementAgreed: true);

        $second = $this->createSubmittableDraft($config, ['nisn' => '1234567890']);

        $this->expectException(ValidationException::class);

        try {
            $service->submit($second, statementAgreed: true);
        } finally {
            $this->assertNull($second->fresh()->registration_number);
            $this->assertSame(1, Registration::query()->whereNotNull('submitted_at')->count());
        }
    }

    public function test_the_service_reports_a_taken_nisn_before_submission(): void
    {
        $this->fakePrivateDisk();
        $config = $this->createPpdbConfiguration();
        $service = app(RegistrationService::class);

        $first = $this->createSubmittableDraft($config, ['nisn' => '9876543210']);
        $service->submit($first, statementAgreed: true);

        $this->assertTrue($service->nisnTaken('9876543210', $config['year']->id));
        $this->assertFalse($service->nisnTaken('1111111111', $config['year']->id));
    }

    public function test_an_abandoned_draft_does_not_block_the_same_nisn(): void
    {
        $this->fakePrivateDisk();
        $config = $this->createPpdbConfiguration();
        $service = app(RegistrationService::class);

        // A draft that was never submitted must not reserve the NISN.
        $this->createSubmittableDraft($config, ['nisn' => '5555555555']);

        $this->assertFalse($service->nisnTaken('5555555555', $config['year']->id));

        $second = $this->createSubmittableDraft($config, ['nisn' => '5555555555']);
        $service->submit($second, statementAgreed: true);

        $this->assertNotNull($second->fresh()->registration_number);
    }

    public function test_the_same_nisn_may_register_again_in_a_different_academic_year(): void
    {
        $this->fakePrivateDisk();
        $service = app(RegistrationService::class);

        $firstYear = $this->createPpdbConfiguration();
        $first = $this->createSubmittableDraft($firstYear, ['nisn' => '2222222222']);
        $service->submit($first, statementAgreed: true);

        $secondYear = $this->createPpdbConfiguration([
            'year' => ['name' => '2027/2028', 'start_year' => 2027, 'end_year' => 2028, 'is_active' => false],
        ]);

        $second = $this->createSubmittableDraft($secondYear, ['nisn' => '2222222222']);
        $service->submit($second, statementAgreed: true);

        $this->assertSame('2701000001', $second->fresh()->registration_number);
    }

    public function test_one_applicant_record_cannot_hold_two_registrations_in_a_year(): void
    {
        $config = $this->createPpdbConfiguration();
        $service = app(RegistrationService::class);

        $first = $service->startDraft($config['year'], $config['wave'], $config['track']);

        $this->expectException(UniqueConstraintViolationException::class);

        Registration::query()->create([
            'applicant_id' => $first->applicant_id,
            'academic_year_id' => $config['year']->id,
            'registration_wave_id' => $config['wave']->id,
            'admission_track_id' => $config['track']->id,
        ]);
    }
}
