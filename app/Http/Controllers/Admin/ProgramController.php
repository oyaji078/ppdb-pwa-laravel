<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\AcademicYear;
use App\Models\Program;
use App\Services\ActivityLogger;
use Illuminate\Contracts\View\View;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Str;
use Illuminate\Validation\Rule;

class ProgramController extends Controller
{
    public function __construct(private readonly ActivityLogger $activity) {}

    public function index(Request $request): View
    {
        $yearId = $request->integer('academic_year_id') ?: AcademicYear::current()?->id;

        return view('admin.programs.index', [
            'programs' => Program::query()
                ->with('academicYear')
                ->withCount(['registrations' => fn ($q) => $q->whereNotNull('submitted_at')])
                ->when($yearId, fn ($q) => $q->where('academic_year_id', $yearId))
                ->orderBy('sort_order')
                ->paginate(15)
                ->withQueryString(),
            'academicYears' => AcademicYear::query()->orderByDesc('start_year')->get(),
            'selectedYearId' => $yearId,
        ]);
    }

    public function create(): View
    {
        return view('admin.programs.form', [
            'program' => new Program([
                'academic_year_id' => AcademicYear::current()?->id,
                'is_active' => true,
            ]),
            'academicYears' => AcademicYear::query()->orderByDesc('start_year')->get(),
        ]);
    }

    public function store(Request $request): RedirectResponse
    {
        $program = Program::query()->create($this->validated($request));

        $this->activity->log(
            ActivityLogger::CONFIGURATION_CHANGED,
            sprintf('Program %s dibuat.', $program->name),
            $program
        );

        return redirect()->route('admin.programs.index')->with('success', 'Program berhasil dibuat.');
    }

    public function edit(Program $program): View
    {
        return view('admin.programs.form', [
            'program' => $program,
            'academicYears' => AcademicYear::query()->orderByDesc('start_year')->get(),
        ]);
    }

    public function update(Request $request, Program $program): RedirectResponse
    {
        $program->update($this->validated($request, $program));

        $this->activity->log(
            ActivityLogger::CONFIGURATION_CHANGED,
            sprintf('Program %s diperbarui.', $program->name),
            $program
        );

        return redirect()->route('admin.programs.index')->with('success', 'Program berhasil diperbarui.');
    }

    public function destroy(Program $program): RedirectResponse
    {
        if ($program->registrations()->exists()) {
            return back()->with('error', 'Program yang sudah dipilih pendaftar tidak dapat dihapus. Nonaktifkan saja.');
        }

        $name = $program->name;
        $program->delete();

        $this->activity->log(ActivityLogger::CONFIGURATION_CHANGED, sprintf('Program %s dihapus.', $name));

        return redirect()->route('admin.programs.index')->with('success', 'Program dihapus.');
    }

    /**
     * @return array<string, mixed>
     */
    private function validated(Request $request, ?Program $program = null): array
    {
        $validated = $request->validate([
            'academic_year_id' => ['required', 'integer', 'exists:academic_years,id'],
            'name' => ['required', 'string', 'max:120'],
            'code' => [
                'required', 'string', 'max:30', 'regex:/^[a-z0-9\-]+$/',
                Rule::unique('programs', 'code')
                    ->where('academic_year_id', $request->integer('academic_year_id'))
                    ->whereNull('deleted_at')
                    ->ignore($program),
            ],
            'description' => ['nullable', 'string', 'max:1000'],
            'quota' => ['nullable', 'integer', 'min:1', 'max:100000'],
            'sort_order' => ['nullable', 'integer', 'min:0', 'max:999'],
        ], [
            'code.regex' => 'Kode hanya boleh berisi huruf kecil, angka, dan tanda hubung.',
            'code.unique' => 'Kode program ini sudah dipakai pada tahun ajaran yang sama.',
        ]);

        $validated['code'] = Str::slug($validated['code']);
        $validated['is_active'] = $request->boolean('is_active');
        $validated['sort_order'] = $validated['sort_order'] ?? 0;

        return $validated;
    }
}
