<?php

namespace App\Services;

use App\Models\AcademicYear;
use App\Models\RegistrationCounter;
use App\Models\RegistrationWave;
use Illuminate\Database\UniqueConstraintViolationException;
use Illuminate\Support\Facades\DB;
use RuntimeException;

/**
 * Issues the 10 digit registration number: YYGGNNNNNN.
 *
 *   YY     last two digits of the intake year
 *   GG     two digit wave code
 *   NNNNNN zero padded sequence, restarting at 1 for each wave
 */
class RegistrationNumberService
{
    /**
     * Reserve the next number for a wave.
     *
     * Must run inside a transaction: the counter row is held with
     * lockForUpdate() so two concurrent submissions cannot read the same
     * sequence. Never uses MAX()+1.
     */
    public function generate(AcademicYear $academicYear, RegistrationWave $wave): string
    {
        if (DB::transactionLevel() === 0) {
            throw new RuntimeException('Nomor pendaftaran harus dibuat di dalam transaksi database.');
        }

        $counter = $this->lockCounter($academicYear->id, $wave->id);

        $sequence = $counter->last_sequence + 1;
        $counter->last_sequence = $sequence;
        $counter->save();

        return $this->format($academicYear, $wave, $sequence);
    }

    /**
     * Compose a number without touching the counter (used by tests and for
     * previewing a format).
     */
    public function format(AcademicYear $academicYear, RegistrationWave $wave, int $sequence): string
    {
        $digits = config('ppdb.registration_number.sequence_digits');

        if ($sequence > (10 ** $digits) - 1) {
            throw new RuntimeException('Nomor urut pendaftaran untuk gelombang ini sudah habis.');
        }

        return $academicYear->yearCode()
            .str_pad($wave->code, 2, '0', STR_PAD_LEFT)
            .str_pad((string) $sequence, $digits, '0', STR_PAD_LEFT);
    }

    /**
     * Fetch the counter row with a write lock, creating it if this is the
     * first submission for the wave.
     */
    private function lockCounter(int $academicYearId, int $waveId): RegistrationCounter
    {
        $counter = RegistrationCounter::query()
            ->where('academic_year_id', $academicYearId)
            ->where('registration_wave_id', $waveId)
            ->lockForUpdate()
            ->first();

        if ($counter !== null) {
            return $counter;
        }

        // Another request may have inserted the row between the SELECT above
        // and this INSERT; fall back to re-reading it under the same lock.
        try {
            RegistrationCounter::query()->create([
                'academic_year_id' => $academicYearId,
                'registration_wave_id' => $waveId,
                'last_sequence' => 0,
            ]);
        } catch (UniqueConstraintViolationException) {
            // Row now exists, re-read below.
        }

        return RegistrationCounter::query()
            ->where('academic_year_id', $academicYearId)
            ->where('registration_wave_id', $waveId)
            ->lockForUpdate()
            ->firstOrFail();
    }
}
