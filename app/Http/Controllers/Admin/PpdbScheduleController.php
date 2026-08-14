<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\AcademicYear;
use App\Models\PpdbSchedule;
use App\Models\RegistrationWave;
use App\Services\ActivityLogger;
use Illuminate\Contracts\View\View;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;

class PpdbScheduleController extends Controller
{
    public function __construct(private readonly ActivityLogger $activity) {}

    public function index(Request $request): View
    {
        $yearId = $request->integer('academic_year_id') ?: AcademicYear::current()?->id;

        return view('admin.schedules.index', [
            'schedules' => PpdbSchedule::query()
                ->with(['academicYear', 'wave'])
                ->when($yearId, fn ($q) => $q->where('academic_year_id', $yearId))
                ->orderBy('sort_order')
                ->orderBy('start_at')
                ->paginate(20)
                ->withQueryString(),
            'academicYears' => AcademicYear::query()->orderByDesc('start_year')->get(),
            'selectedYearId' => $yearId,
        ]);
    }

    public function create(): View
    {
        $yearId = AcademicYear::current()?->id;

        return view('admin.schedules.form', [
            'schedule' => new PpdbSchedule(['academic_year_id' => $yearId, 'is_public' => true]),
            'academicYears' => AcademicYear::query()->orderByDesc('start_year')->get(),
            'waves' => RegistrationWave::query()->when($yearId, fn ($q) => $q->where('academic_year_id', $yearId))
                ->orderBy('code')->get(),
        ]);
    }

    public function store(Request $request): RedirectResponse
    {
        $schedule = PpdbSchedule::query()->create($this->validated($request));

        $this->activity->log(
            ActivityLogger::CONFIGURATION_CHANGED,
            sprintf('Jadwal "%s" dibuat.', $schedule->title),
            $schedule
        );

        return redirect()->route('admin.schedules.index')->with('success', 'Jadwal berhasil dibuat.');
    }

    public function edit(PpdbSchedule $schedule): View
    {
        return view('admin.schedules.form', [
            'schedule' => $schedule,
            'academicYears' => AcademicYear::query()->orderByDesc('start_year')->get(),
            'waves' => RegistrationWave::query()
                ->where('academic_year_id', $schedule->academic_year_id)
                ->orderBy('code')->get(),
        ]);
    }

    public function update(Request $request, PpdbSchedule $schedule): RedirectResponse
    {
        $schedule->update($this->validated($request));

        $this->activity->log(
            ActivityLogger::CONFIGURATION_CHANGED,
            sprintf('Jadwal "%s" diperbarui.', $schedule->title),
            $schedule
        );

        return redirect()->route('admin.schedules.index')->with('success', 'Jadwal berhasil diperbarui.');
    }

    public function destroy(PpdbSchedule $schedule): RedirectResponse
    {
        $title = $schedule->title;
        $schedule->delete();

        $this->activity->log(ActivityLogger::CONFIGURATION_CHANGED, sprintf('Jadwal "%s" dihapus.', $title));

        return redirect()->route('admin.schedules.index')->with('success', 'Jadwal dihapus.');
    }

    /**
     * @return array<string, mixed>
     */
    private function validated(Request $request): array
    {
        $validated = $request->validate([
            'academic_year_id' => ['required', 'integer', 'exists:academic_years,id'],
            'registration_wave_id' => ['nullable', 'integer', 'exists:registration_waves,id'],
            'title' => ['required', 'string', 'max:150'],
            'description' => ['nullable', 'string', 'max:1000'],
            'start_at' => ['required', 'date'],
            'end_at' => ['required', 'date', 'after_or_equal:start_at'],
            'sort_order' => ['nullable', 'integer', 'min:0', 'max:999'],
        ], [
            'end_at.after_or_equal' => 'Tanggal selesai tidak boleh sebelum tanggal mulai.',
        ]);

        $validated['is_public'] = $request->boolean('is_public');
        $validated['sort_order'] = $validated['sort_order'] ?? 0;

        return $validated;
    }
}
