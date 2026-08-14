<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\AcademicYear;
use App\Models\AdmissionTrack;
use App\Models\DocumentType;
use App\Services\ActivityLogger;
use Illuminate\Contracts\View\View;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Str;
use Illuminate\Validation\Rule;

class AdmissionTrackController extends Controller
{
    public function __construct(private readonly ActivityLogger $activity) {}

    public function index(Request $request): View
    {
        $yearId = $request->integer('academic_year_id') ?: AcademicYear::current()?->id;

        return view('admin.tracks.index', [
            'tracks' => AdmissionTrack::query()
                ->with('academicYear')
                ->withCount([
                    'registrations' => fn ($q) => $q->whereNotNull('submitted_at'),
                    'documentRequirements',
                ])
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
        return view('admin.tracks.form', [
            'track' => new AdmissionTrack([
                'academic_year_id' => AcademicYear::current()?->id,
                'is_active' => true,
            ]),
            'academicYears' => AcademicYear::query()->orderByDesc('start_year')->get(),
            'documentTypes' => collect(),
            'selectedRequirements' => [],
        ]);
    }

    public function store(Request $request): RedirectResponse
    {
        $track = AdmissionTrack::query()->create($this->validated($request));

        $this->activity->log(
            ActivityLogger::CONFIGURATION_CHANGED,
            sprintf('Jalur %s dibuat.', $track->name),
            $track
        );

        return redirect()->route('admin.tracks.edit', $track)
            ->with('success', 'Jalur berhasil dibuat. Silakan tentukan persyaratan berkasnya.');
    }

    public function edit(AdmissionTrack $track): View
    {
        $documentTypes = DocumentType::query()
            ->where('academic_year_id', $track->academic_year_id)
            ->active()
            ->orderBy('sort_order')
            ->get();

        return view('admin.tracks.form', [
            'track' => $track,
            'academicYears' => AcademicYear::query()->orderByDesc('start_year')->get(),
            'documentTypes' => $documentTypes,
            'selectedRequirements' => $track->documentTypes()
                ->pluck('admission_track_document_requirements.is_required', 'document_types.id')
                ->all(),
        ]);
    }

    public function update(Request $request, AdmissionTrack $track): RedirectResponse
    {
        $track->update($this->validated($request, $track));

        $this->activity->log(
            ActivityLogger::CONFIGURATION_CHANGED,
            sprintf('Jalur %s diperbarui.', $track->name),
            $track
        );

        return redirect()->route('admin.tracks.index')->with('success', 'Jalur berhasil diperbarui.');
    }

    /**
     * Set which document types this track needs, and which of them are
     * mandatory.
     */
    public function updateRequirements(Request $request, AdmissionTrack $track): RedirectResponse
    {
        $validated = $request->validate([
            'documents' => ['nullable', 'array'],
            'documents.*' => ['integer', 'exists:document_types,id'],
            'required' => ['nullable', 'array'],
            'required.*' => ['integer'],
        ]);

        $selected = $validated['documents'] ?? [];
        $required = $validated['required'] ?? [];

        // Only document types belonging to the same academic year may be
        // attached, so a track can never require a foreign year's document.
        $allowed = DocumentType::query()
            ->where('academic_year_id', $track->academic_year_id)
            ->whereIn('id', $selected)
            ->pluck('id');

        $sync = $allowed->mapWithKeys(fn (int $id) => [
            $id => ['is_required' => in_array($id, $required, true)],
        ])->all();

        $track->documentTypes()->sync($sync);

        $this->activity->log(
            ActivityLogger::CONFIGURATION_CHANGED,
            sprintf('Persyaratan berkas jalur %s diperbarui (%d berkas).', $track->name, count($sync)),
            $track
        );

        return back()->with('success', 'Persyaratan berkas jalur berhasil disimpan.');
    }

    public function destroy(AdmissionTrack $track): RedirectResponse
    {
        if ($track->registrations()->exists()) {
            return back()->with('error', 'Jalur yang sudah memiliki pendaftar tidak dapat dihapus. Nonaktifkan saja.');
        }

        $name = $track->name;
        $track->delete();

        $this->activity->log(ActivityLogger::CONFIGURATION_CHANGED, sprintf('Jalur %s dihapus.', $name));

        return redirect()->route('admin.tracks.index')->with('success', 'Jalur dihapus.');
    }

    /**
     * @return array<string, mixed>
     */
    private function validated(Request $request, ?AdmissionTrack $track = null): array
    {
        $validated = $request->validate([
            'academic_year_id' => ['required', 'integer', 'exists:academic_years,id'],
            'name' => ['required', 'string', 'max:100'],
            'code' => [
                'required', 'string', 'max:30', 'regex:/^[a-z0-9\-]+$/',
                Rule::unique('admission_tracks', 'code')
                    ->where('academic_year_id', $request->integer('academic_year_id'))
                    ->whereNull('deleted_at')
                    ->ignore($track),
            ],
            'description' => ['nullable', 'string', 'max:1000'],
            'quota' => ['nullable', 'integer', 'min:1', 'max:100000'],
            'sort_order' => ['nullable', 'integer', 'min:0', 'max:999'],
        ], [
            'code.regex' => 'Kode hanya boleh berisi huruf kecil, angka, dan tanda hubung.',
            'code.unique' => 'Kode jalur ini sudah dipakai pada tahun ajaran yang sama.',
        ]);

        $validated['code'] = Str::slug($validated['code']);
        $validated['is_active'] = $request->boolean('is_active');
        $validated['sort_order'] = $validated['sort_order'] ?? 0;

        return $validated;
    }
}
