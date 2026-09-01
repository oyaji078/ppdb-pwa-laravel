<?php

namespace App\Http\Controllers\Admin\Concerns;

use App\Models\AcademicYear;
use App\Models\AdmissionTrack;
use App\Models\Program;
use App\Models\Registration;
use App\Models\RegistrationWave;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Http\Request;

/**
 * Shared filter/search handling for every admin screen that lists
 * registrations. All filtering happens in SQL, never in PHP over a full table.
 */
trait FiltersRegistrations
{
    /**
     * @return Builder<Registration>
     */
    protected function filteredRegistrations(Request $request): Builder
    {
        $search = trim((string) $request->query('q'));

        return Registration::query()
            ->with(['applicant.previousSchool', 'academicYear', 'wave', 'admissionTrack', 'program', 'selectionResult'])
            ->submitted()
            ->when($request->filled('academic_year_id'),
                fn (Builder $q) => $q->where('academic_year_id', $request->integer('academic_year_id')))
            ->when($request->filled('registration_wave_id'),
                fn (Builder $q) => $q->where('registration_wave_id', $request->integer('registration_wave_id')))
            ->when($request->filled('admission_track_id'),
                fn (Builder $q) => $q->where('admission_track_id', $request->integer('admission_track_id')))
            ->when($request->filled('program_id'),
                fn (Builder $q) => $q->where('program_id', $request->integer('program_id')))
            ->when($request->filled('registration_status'),
                fn (Builder $q) => $q->where('registration_status', $request->string('registration_status')))
            ->when($request->filled('selection_status'),
                fn (Builder $q) => $q->where('selection_status', $request->string('selection_status')))
            ->when($request->filled('reregistration_status'),
                fn (Builder $q) => $q->where('reregistration_status', $request->string('reregistration_status')))
            ->when($search !== '', fn (Builder $q) => $q->where(function (Builder $query) use ($search): void {
                $query->whereLike('registration_number', "%{$search}%")
                    ->orWhereHas('applicant', fn (Builder $a) => $a
                        ->whereLike('full_name', "%{$search}%")
                        ->orWhereLike('nisn', "%{$search}%"))
                    ->orWhereHas('applicant.previousSchool', fn (Builder $s) => $s
                        ->whereLike('school_name', "%{$search}%"));
            }));
    }

    /**
     * Option lists for the filter bar, scoped to the selected academic year.
     *
     * @return array<string, mixed>
     */
    protected function filterOptions(Request $request): array
    {
        $years = AcademicYear::query()->orderByDesc('start_year')->get();
        $yearId = $request->integer('academic_year_id') ?: (AcademicYear::current()?->id);

        return [
            'academicYears' => $years,
            'waves' => RegistrationWave::query()
                ->when($yearId, fn (Builder $q) => $q->where('academic_year_id', $yearId))
                ->orderBy('code')->get(),
            'tracks' => AdmissionTrack::query()
                ->when($yearId, fn (Builder $q) => $q->where('academic_year_id', $yearId))
                ->orderBy('sort_order')->get(),
            'programs' => Program::query()
                ->when($yearId, fn (Builder $q) => $q->where('academic_year_id', $yearId))
                ->orderBy('sort_order')->get(),
            'selectedYearId' => $yearId,
        ];
    }

    /**
     * Rows per page, restricted to the offered choices.
     */
    protected function perPage(Request $request, int $default = 15): int
    {
        $perPage = $request->integer('per_page', $default);

        return in_array($perPage, [15, 25, 50, 100], true) ? $perPage : $default;
    }
}
