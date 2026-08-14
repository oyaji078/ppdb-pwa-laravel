<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\AcademicYear;
use App\Models\DocumentType;
use App\Services\ActivityLogger;
use Illuminate\Contracts\View\View;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Str;
use Illuminate\Validation\Rule;

class DocumentTypeController extends Controller
{
    public function __construct(private readonly ActivityLogger $activity) {}

    public function index(Request $request): View
    {
        $yearId = $request->integer('academic_year_id') ?: AcademicYear::current()?->id;

        return view('admin.document-types.index', [
            'documentTypes' => DocumentType::query()
                ->with('academicYear')
                ->withCount(['admissionTracks', 'registrationDocuments'])
                ->when($yearId, fn ($q) => $q->where('academic_year_id', $yearId))
                ->orderBy('sort_order')
                ->paginate(20)
                ->withQueryString(),
            'academicYears' => AcademicYear::query()->orderByDesc('start_year')->get(),
            'selectedYearId' => $yearId,
        ]);
    }

    public function create(): View
    {
        return view('admin.document-types.form', [
            'documentType' => new DocumentType([
                'academic_year_id' => AcademicYear::current()?->id,
                'is_active' => true,
                'is_required' => true,
                'requires_verification' => true,
                'max_size_kb' => config('ppdb.uploads.default_max_size_kb'),
                'allowed_extensions' => config('ppdb.uploads.allowed_extensions'),
            ]),
            'academicYears' => AcademicYear::query()->orderByDesc('start_year')->get(),
            'availableExtensions' => config('ppdb.uploads.allowed_extensions'),
        ]);
    }

    public function store(Request $request): RedirectResponse
    {
        $documentType = DocumentType::query()->create($this->validated($request));

        $this->activity->log(
            ActivityLogger::CONFIGURATION_CHANGED,
            sprintf('Jenis berkas %s dibuat.', $documentType->name),
            $documentType
        );

        return redirect()->route('admin.document-types.index')
            ->with('success', 'Jenis berkas berhasil dibuat. Tambahkan ke jalur pendaftaran pada menu Jalur.');
    }

    public function edit(DocumentType $documentType): View
    {
        return view('admin.document-types.form', [
            'documentType' => $documentType,
            'academicYears' => AcademicYear::query()->orderByDesc('start_year')->get(),
            'availableExtensions' => config('ppdb.uploads.allowed_extensions'),
        ]);
    }

    public function update(Request $request, DocumentType $documentType): RedirectResponse
    {
        $documentType->update($this->validated($request, $documentType));

        $this->activity->log(
            ActivityLogger::CONFIGURATION_CHANGED,
            sprintf('Jenis berkas %s diperbarui.', $documentType->name),
            $documentType
        );

        return redirect()->route('admin.document-types.index')
            ->with('success', 'Jenis berkas berhasil diperbarui.');
    }

    public function destroy(DocumentType $documentType): RedirectResponse
    {
        if ($documentType->registrationDocuments()->exists()) {
            return back()->with('error', 'Jenis berkas yang sudah diunggah pendaftar tidak dapat dihapus. Nonaktifkan saja.');
        }

        $name = $documentType->name;
        $documentType->delete();

        $this->activity->log(ActivityLogger::CONFIGURATION_CHANGED, sprintf('Jenis berkas %s dihapus.', $name));

        return redirect()->route('admin.document-types.index')->with('success', 'Jenis berkas dihapus.');
    }

    /**
     * @return array<string, mixed>
     */
    private function validated(Request $request, ?DocumentType $documentType = null): array
    {
        $validated = $request->validate([
            'academic_year_id' => ['required', 'integer', 'exists:academic_years,id'],
            'name' => ['required', 'string', 'max:120'],
            'code' => [
                'required', 'string', 'max:40', 'regex:/^[a-z0-9\-]+$/',
                Rule::unique('document_types', 'code')
                    ->where('academic_year_id', $request->integer('academic_year_id'))
                    ->whereNull('deleted_at')
                    ->ignore($documentType),
            ],
            'description' => ['nullable', 'string', 'max:1000'],
            'allowed_extensions' => ['required', 'array', 'min:1'],
            'allowed_extensions.*' => ['string', 'in:'.implode(',', config('ppdb.uploads.allowed_extensions'))],
            'max_size_kb' => ['required', 'integer', 'min:100', 'max:'.config('ppdb.uploads.absolute_max_size_kb')],
            'sort_order' => ['nullable', 'integer', 'min:0', 'max:999'],
        ], [
            'code.regex' => 'Kode hanya boleh berisi huruf kecil, angka, dan tanda hubung.',
            'code.unique' => 'Kode jenis berkas ini sudah dipakai pada tahun ajaran yang sama.',
            'allowed_extensions.required' => 'Pilih minimal satu format berkas yang diizinkan.',
            'max_size_kb.max' => 'Ukuran maksimal tidak boleh melebihi :max KB.',
        ]);

        $validated['code'] = Str::slug($validated['code']);
        $validated['is_required'] = $request->boolean('is_required');
        $validated['requires_verification'] = $request->boolean('requires_verification');
        $validated['is_active'] = $request->boolean('is_active');
        $validated['sort_order'] = $validated['sort_order'] ?? 0;

        return $validated;
    }
}
