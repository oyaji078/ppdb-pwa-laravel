<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\AcademicYear;
use App\Services\ActivityLogger;
use Illuminate\Contracts\View\View;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\Rule;

class AcademicYearController extends Controller
{
    public function __construct(private readonly ActivityLogger $activity) {}

    public function index(): View
    {
        return view('admin.academic-years.index', [
            'academicYears' => AcademicYear::query()
                ->withCount(['waves', 'admissionTracks', 'programs', 'registrations'])
                ->orderByDesc('start_year')
                ->paginate(15),
        ]);
    }

    public function create(): View
    {
        return view('admin.academic-years.form', [
            'academicYear' => new AcademicYear(['start_year' => now()->year, 'end_year' => now()->year + 1]),
        ]);
    }

    public function store(Request $request): RedirectResponse
    {
        $data = $this->validated($request);

        $academicYear = AcademicYear::query()->create($data);

        $this->syncActiveFlag($academicYear, $request->boolean('is_active'));

        $this->activity->log(
            ActivityLogger::CONFIGURATION_CHANGED,
            sprintf('Tahun ajaran %s dibuat.', $academicYear->name),
            $academicYear
        );

        return redirect()->route('admin.academic-years.index')
            ->with('success', 'Tahun ajaran berhasil dibuat.');
    }

    public function edit(AcademicYear $academicYear): View
    {
        return view('admin.academic-years.form', ['academicYear' => $academicYear]);
    }

    public function update(Request $request, AcademicYear $academicYear): RedirectResponse
    {
        $academicYear->update($this->validated($request, $academicYear));

        $this->syncActiveFlag($academicYear, $request->boolean('is_active'));

        $this->activity->log(
            ActivityLogger::CONFIGURATION_CHANGED,
            sprintf('Tahun ajaran %s diperbarui.', $academicYear->name),
            $academicYear
        );

        return redirect()->route('admin.academic-years.index')
            ->with('success', 'Tahun ajaran berhasil diperbarui.');
    }

    /**
     * Make this year the active one, deactivating the rest.
     */
    public function activate(AcademicYear $academicYear): RedirectResponse
    {
        $this->syncActiveFlag($academicYear, true);

        $this->activity->log(
            ActivityLogger::CONFIGURATION_CHANGED,
            sprintf('Tahun ajaran aktif diubah menjadi %s.', $academicYear->name),
            $academicYear
        );

        return back()->with('success', sprintf('Tahun ajaran %s sekarang aktif.', $academicYear->name));
    }

    public function destroy(AcademicYear $academicYear): RedirectResponse
    {
        if ($academicYear->registrations()->exists()) {
            return back()->with('error', 'Tahun ajaran yang sudah memiliki pendaftar tidak dapat dihapus.');
        }

        $name = $academicYear->name;
        $academicYear->delete();

        $this->activity->log(ActivityLogger::CONFIGURATION_CHANGED, sprintf('Tahun ajaran %s dihapus.', $name));

        return redirect()->route('admin.academic-years.index')
            ->with('success', 'Tahun ajaran dihapus.');
    }

    /**
     * @return array<string, mixed>
     */
    private function validated(Request $request, ?AcademicYear $academicYear = null): array
    {
        return $request->validate([
            'name' => ['required', 'string', 'max:20', Rule::unique('academic_years', 'name')->ignore($academicYear)],
            'start_year' => ['required', 'integer', 'min:2000', 'max:2100'],
            'end_year' => ['required', 'integer', 'min:2000', 'max:2100', 'gt:start_year'],
            'registration_open' => ['nullable', 'boolean'],
        ], [
            'end_year.gt' => 'Tahun selesai harus lebih besar dari tahun mulai.',
            'name.unique' => 'Tahun ajaran dengan nama ini sudah ada.',
        ]) + ['registration_open' => $request->boolean('registration_open')];
    }

    /**
     * Exactly one academic year may be active at a time.
     */
    private function syncActiveFlag(AcademicYear $academicYear, bool $shouldBeActive): void
    {
        if (! $shouldBeActive) {
            $academicYear->update(['is_active' => false]);

            return;
        }

        DB::transaction(function () use ($academicYear): void {
            AcademicYear::query()->whereKeyNot($academicYear->id)->update(['is_active' => false]);
            $academicYear->update(['is_active' => true]);
        });
    }
}
