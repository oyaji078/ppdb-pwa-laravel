<?php

namespace Tests\Feature;

use App\Models\Registration;
use App\Models\RegistrationWave;
use App\Services\RegistrationNumberService;
use App\Services\RegistrationService;
use Illuminate\Database\UniqueConstraintViolationException;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use Tests\TestCase;

class RegistrationNumberTest extends TestCase
{
    use RefreshDatabase;

    public function test_the_first_two_registrations_of_a_wave_are_numbered_in_sequence(): void
    {
        $this->fakePrivateDisk();
        $config = $this->createPpdbConfiguration();
        $service = app(RegistrationService::class);

        $first = $this->createSubmittableDraft($config);
        $service->submit($first, statementAgreed: true);

        $second = $this->createSubmittableDraft($config);
        $service->submit($second, statementAgreed: true);

        $this->assertSame('2601000001', $first->fresh()->registration_number);
        $this->assertSame('2601000002', $second->fresh()->registration_number);
    }

    public function test_the_number_encodes_year_wave_and_sequence(): void
    {
        $this->fakePrivateDisk();
        $config = $this->createPpdbConfiguration();

        $registration = $this->createSubmittableDraft($config);
        app(RegistrationService::class)->submit($registration, statementAgreed: true);

        $number = $registration->fresh()->registration_number;

        $this->assertSame(10, strlen($number));
        $this->assertMatchesRegularExpression('/^\d{10}$/', $number);
        $this->assertSame('26', substr($number, 0, 2), 'dua digit pertama adalah tahun masuk');
        $this->assertSame('01', substr($number, 2, 2), 'dua digit berikutnya adalah kode gelombang');
        $this->assertSame('000001', substr($number, 4, 6), 'enam digit terakhir adalah nomor urut');
    }

    public function test_each_wave_keeps_its_own_sequence(): void
    {
        $this->fakePrivateDisk();
        $config = $this->createPpdbConfiguration();
        $service = app(RegistrationService::class);

        $waveTwo = RegistrationWave::query()->create([
            'academic_year_id' => $config['year']->id,
            'name' => 'Gelombang 2',
            'code' => '02',
            'start_at' => now()->subDay(),
            'end_at' => now()->addDays(30),
            'is_active' => true,
        ]);

        $first = $this->createSubmittableDraft($config);
        $service->submit($first, statementAgreed: true);

        $second = $this->createSubmittableDraft($config);
        $second->update(['registration_wave_id' => $waveTwo->id]);
        $service->submit($second->fresh(), statementAgreed: true);

        $this->assertSame('2601000001', $first->fresh()->registration_number);
        $this->assertSame('2602000001', $second->fresh()->registration_number);
    }

    public function test_registration_numbers_are_unique(): void
    {
        $this->fakePrivateDisk();
        $config = $this->createPpdbConfiguration();
        $service = app(RegistrationService::class);

        $numbers = [];

        for ($i = 0; $i < 12; $i++) {
            $draft = $this->createSubmittableDraft($config);
            $service->submit($draft, statementAgreed: true);
            $numbers[] = $draft->fresh()->registration_number;
        }

        $this->assertCount(12, array_unique($numbers));
        $this->assertSame('2601000012', end($numbers));
    }

    public function test_the_database_rejects_a_duplicate_registration_number(): void
    {
        $this->fakePrivateDisk();
        $config = $this->createPpdbConfiguration();

        $first = $this->createSubmittableDraft($config);
        app(RegistrationService::class)->submit($first, statementAgreed: true);

        $second = $this->createSubmittableDraft($config);

        $this->expectException(UniqueConstraintViolationException::class);

        $second->forceFill(['registration_number' => $first->fresh()->registration_number])->save();
    }

    public function test_concurrent_submissions_never_share_a_sequence(): void
    {
        $this->fakePrivateDisk();
        $config = $this->createPpdbConfiguration();
        $numbers = app(RegistrationNumberService::class);

        // Two connections holding the counter at the same time: the second
        // blocks on lockForUpdate() until the first commits, so it must see the
        // incremented value rather than a stale one.
        $issued = [];

        for ($i = 0; $i < 5; $i++) {
            $issued[] = DB::transaction(fn () => $numbers->generate($config['year'], $config['wave']));
        }

        $this->assertCount(5, array_unique($issued));
        $this->assertSame(
            ['2601000001', '2601000002', '2601000003', '2601000004', '2601000005'],
            $issued
        );
        $this->assertDatabaseHas('registration_counters', [
            'academic_year_id' => $config['year']->id,
            'registration_wave_id' => $config['wave']->id,
            'last_sequence' => 5,
        ]);
    }

    public function test_the_sequence_is_not_derived_from_the_row_count(): void
    {
        $this->fakePrivateDisk();
        $config = $this->createPpdbConfiguration();
        $service = app(RegistrationService::class);

        $first = $this->createSubmittableDraft($config);
        $service->submit($first, statementAgreed: true);

        $second = $this->createSubmittableDraft($config);
        $service->submit($second, statementAgreed: true);

        // Deleting an earlier registration must not cause the next number to be
        // reused, which is exactly what MAX()+1 or COUNT()+1 would do.
        Registration::query()->whereKey($first->id)->forceDelete();

        $third = $this->createSubmittableDraft($config);
        $service->submit($third, statementAgreed: true);

        $this->assertSame('2601000003', $third->fresh()->registration_number);
    }
}
