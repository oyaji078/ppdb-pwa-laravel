<?php

namespace App\Http\Controllers\Registration;

use App\Http\Controllers\Controller;
use App\Models\DocumentType;
use App\Models\Registration;
use App\Models\RegistrationDocument;
use App\Services\DocumentService;
use App\Services\RegistrationService;
use App\Support\RegistrationDraft;
use Illuminate\Contracts\View\View;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Http\Response;
use Illuminate\Validation\ValidationException;

/**
 * Step 7 of the wizard: uploading the documents the chosen track requires.
 */
class RegistrationDocumentController extends Controller
{
    public function __construct(
        private readonly RegistrationDraft $draft,
        private readonly DocumentService $documents,
        private readonly RegistrationService $registrations,
    ) {}

    public function index(): View|RedirectResponse
    {
        $draft = $this->draft->current();

        if ($draft === null) {
            return $this->noDraft();
        }

        return view('registration.documents', [
            'registration' => $draft,
            'documentTypes' => $draft->admissionTrack
                ->documentTypes()
                ->where('document_types.is_active', true)
                ->get(),
            'uploaded' => $draft->documents->keyBy('document_type_id'),
        ]);
    }

    public function store(Request $request, DocumentType $documentType): RedirectResponse
    {
        $draft = $this->draft->current();

        if ($draft === null) {
            return $this->noDraft();
        }

        $this->assertTypeBelongsToTrack($draft, $documentType);

        $request->validate(
            ['file' => ['required', 'file']],
            ['file.required' => 'Silakan pilih berkas yang akan diunggah.']
        );

        // DocumentService performs the real extension/MIME/size checks.
        $this->documents->store($draft, $documentType, $request->file('file'));

        $this->registrations->advanceStep($draft, 'berkas');

        return back()->with('success', sprintf('%s berhasil diunggah.', $documentType->name));
    }

    public function destroy(RegistrationDocument $document): RedirectResponse
    {
        $draft = $this->draft->current();

        if ($draft === null) {
            return $this->noDraft();
        }

        abort_unless($document->registration_id === $draft->id, 403);

        $name = $document->documentType->name;
        $this->documents->delete($document);

        return back()->with('success', sprintf('%s dihapus.', $name));
    }

    /**
     * Inline preview of the visitor's own upload, streamed from private
     * storage. Ownership is checked against the session's draft.
     */
    public function preview(RegistrationDocument $document): Response|RedirectResponse
    {
        $draft = $this->draft->current();

        if ($draft === null) {
            return $this->noDraft();
        }

        abort_unless($document->registration_id === $draft->id, 403);

        $path = $this->documents->absolutePath($document);

        abort_if($path === null, 404, 'Berkas tidak ditemukan.');

        return response()->file($path, [
            'Content-Type' => $document->mime_type,
            'Content-Disposition' => 'inline; filename="'.addslashes($document->original_name).'"',
            'Cache-Control' => 'no-store, private',
        ]);
    }

    /**
     * Guards against a crafted request uploading against a document type that
     * the selected track does not use.
     */
    private function assertTypeBelongsToTrack(Registration $draft, DocumentType $type): void
    {
        $allowed = $draft->admissionTrack
            ->documentTypes()
            ->where('document_types.is_active', true)
            ->pluck('document_types.id');

        if (! $allowed->contains($type->id)) {
            throw ValidationException::withMessages([
                'file' => 'Jenis berkas ini tidak diperlukan untuk jalur yang Anda pilih.',
            ]);
        }
    }

    private function noDraft(): RedirectResponse
    {
        return redirect()->route('registration.start')
            ->with('warning', 'Sesi pendaftaran belum dimulai atau sudah berakhir. Silakan mulai dari langkah pertama.');
    }
}
