<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\AcademicYear;
use App\Models\RegistrationWave;
use App\Services\ActivityLogger;
use Illuminate\Contracts\View\View;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;

class RegistrationWaveController extends Controller
{
    public function __construct(private readonly ActivityLogger $activity) {}

    public function index(Request $request): View
    {
        $yearId = $request->integer('academic_year_id') ?: AcademicYear::current()?->id;

        return view('admin.waves.index', [
            'waves' => RegistrationWave::query()
                ->with('academicYear')
                ->withCount(['registrations' => fn ($q) => $q->whereNotNull('submitted_at')])
                ->when($yearId, fn ($q) => $q->where('academic_year_id', $yearId))
                ->orderByDesc('academic_year_id')
                ->orderBy('code')
                ->paginate(15)
                ->withQueryString(),
            'academicYears' => AcademicYear::query()->orderByDesc('start_year')->get(),
            'selectedYearId' => $yearId,
        ]);
    }

    public function create(): View
    {
        return view('admin.waves.form', [
            'wave' => new RegistrationWave([
                'academic_year_id' => AcademicYear::current()?->id,
                'is_active' => true,
            ]),
            'academicYears' => AcademicYear::query()->orderByDesc('start_year')->get(),
        ]);
    }

    public function store(Request $request): RedirectResponse
    {
        $wave = RegistrationWave::query()->create($this->validated($request));

        $this->activity->log(
            ActivityLogger::CONFIGURATION_CHANGED,
            sprintf('Gelombang %s (%s) dibuat.', $wave->name, $wave->code),
            $wave
        );

        return redirect()->route('admin.waves.index')->with('success', 'Gelombang berhasil dibuat.');
    }

    public function edit(RegistrationWave $wave): View
    {
        return view('admin.waves.form', [
            'wave' => $wave,
            'academicYears' => AcademicYear::query()->orderByDesc('start_year')->get(),
        ]);
    }

    public function update(Request $request, RegistrationWave $wave): RedirectResponse
    {
        $wave->update($this->validated($request, $wave));

        $this->activity->log(
            ActivityLogger::CONFIGURATION_CHANGED,
            sprintf('Gelombang %s diperbarui.', $wave->name),
            $wave
        );

        return redirect()->route('admin.waves.index')->with('success', 'Gelombang berhasil diperbarui.');
    }

    public function destroy(RegistrationWave $wave): RedirectResponse
    {
        if ($wave->registrations()->exists()) {
            return back()->with('error', 'Gelombang yang sudah memiliki pendaftar tidak dapat dihapus. Nonaktifkan saja.');
        }

        $name = $wave->name;
        $wave->delete();

        $this->activity->log(ActivityLogger::CONFIGURATION_CHANGED, sprintf('Gelombang %s dihapus.', $name));

        return redirect()->route('admin.waves.index')->with('success', 'Gelombang dihapus.');
    }

    /**
     * @return array<string, mixed>
     */
    private function validated(Request $request, ?RegistrationWave $wave = null): array
    {
        $validated = $request->validate([
            'academic_year_id' => ['required', 'integer', 'exists:academic_years,id'],
            'name' => ['required', 'string', 'max:100'],
            // Two digits because the value becomes the GG part of every
            // registration number issued for this wave.
            'code' => [
                'required', 'string', 'digits:2',
                Rule::unique('registration_waves', 'code')
                    ->where('academic_year_id', $request->integer('academic_year_id'))
                    ->ignore($wave),
            ],
            'start_at' => ['required', 'date'],
            'end_at' => ['required', 'date', 'after:start_at'],
            'quota' => ['nullable', 'integer', 'min:1', 'max:100000'],
        ], [
            'code.digits' => 'Kode gelombang harus 2 angka, contoh: 01.',
            'code.unique' => 'Kode gelombang ini sudah dipakai pada tahun ajaran yang sama.',
            'end_at.after' => 'Tanggal selesai harus setelah tanggal mulai.',
        ]);

        $validated['is_active'] = $request->boolean('is_active');

        return $validated;
    }
}
