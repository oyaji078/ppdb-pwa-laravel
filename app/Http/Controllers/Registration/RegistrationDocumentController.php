<?php

namespace App\Http\Controllers\Registration;

use App\Http\Controllers\Controller;
use App\Models\DocumentType;
use App\Models\Registration;
use App\Models\RegistrationDocument;
use App\Services\DocumentService;
use App\Services\RegistrationService;
use App\Support\ApplicantSession;
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
        private readonly ApplicantSession $session,
        private readonly DocumentService $documents,
        private readonly RegistrationService $registrations,
    ) {}

    public function index(): View|RedirectResponse
    {
        $draft = $this->currentDraft();

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
        $draft = $this->currentDraft();

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
        $draft = $this->currentDraft();

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
        $draft = $this->currentDraft();

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

    /**
     * The logged-in applicant's draft, or null once it has been submitted —
     * uploads are only editable while the registration is still a draft.
     */
    private function currentDraft(): ?Registration
    {
        $registration = $this->session->registration();

        if ($registration === null || ! $registration->isDraft()) {
            return null;
        }

        return $registration->loadMissing(['admissionTrack', 'documents.documentType']);
    }

    private function noDraft(): RedirectResponse
    {
        if ($this->session->check()) {
            return redirect()->route('applicant.dashboard')
                ->with('info', 'Pendaftaran Anda sudah dikirim. Berkas dikelola dari portal pendaftar.');
        }

        return redirect()->route('login')
            ->with('warning', 'Sesi Anda telah berakhir. Silakan masuk kembali.');
    }
}
