<?php

namespace App\Http\Controllers\Applicant;

use App\Http\Controllers\Controller;
use App\Models\DocumentType;
use App\Models\RegistrationDocument;
use App\Services\DocumentService;
use App\Support\ApplicantSession;
use Illuminate\Contracts\View\View;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Validation\ValidationException;
use Symfony\Component\HttpFoundation\StreamedResponse;

class DocumentController extends Controller
{
    public function __construct(
        private readonly ApplicantSession $session,
        private readonly DocumentService $documents,
    ) {}

    public function index(): View
    {
        $registration = $this->session->registration()->load('documents.documentType');

        return view('applicant.documents', [
            'registration' => $registration,
            'documentTypes' => $registration->admissionTrack
                ->documentTypes()
                ->where('document_types.is_active', true)
                ->get(),
            'uploaded' => $registration->documents->keyBy('document_type_id'),
        ]);
    }

    /**
     * Re-upload after a revision request. A document already marked verified
     * cannot be replaced without the committee reopening it first.
     */
    public function store(Request $request, DocumentType $documentType): RedirectResponse
    {
        $registration = $this->session->registration();

        $this->assertTypeIsApplicable($documentType);

        $request->validate(
            ['file' => ['required', 'file']],
            ['file.required' => 'Silakan pilih berkas yang akan diunggah.']
        );

        $existing = $registration->documents()->where('document_type_id', $documentType->id)->first();

        if ($existing !== null && ! $existing->verification_status->isReplaceable()) {
            throw ValidationException::withMessages([
                'file' => 'Berkas ini sudah diverifikasi dan tidak dapat diganti. Hubungi panitia bila perlu perubahan.',
            ]);
        }

        $this->documents->store($registration, $documentType, $request->file('file'));

        return back()->with('success', sprintf('%s berhasil diunggah ulang dan menunggu verifikasi.', $documentType->name));
    }

    public function preview(RegistrationDocument $document): StreamedResponse
    {
        $this->assertOwnership($document);

        $response = $this->documents->response($document);

        abort_if($response === null, 404, 'Berkas tidak ditemukan.');

        return $response;
    }

    public function download(RegistrationDocument $document): StreamedResponse
    {
        $this->assertOwnership($document);

        $response = $this->documents->response($document, 'attachment');

        abort_if($response === null, 404, 'Berkas tidak ditemukan.');

        return $response;
    }

    /**
     * An applicant may only ever touch documents attached to their own
     * registration. Anything else is a 403, never a 404 leak.
     */
    private function assertOwnership(RegistrationDocument $document): void
    {
        abort_unless($document->registration_id === $this->session->id(), 403);
    }

    private function assertTypeIsApplicable(DocumentType $type): void
    {
        $applicable = $this->session->registration()
            ->admissionTrack
            ->documentTypes()
            ->where('document_types.is_active', true)
            ->pluck('document_types.id');

        if (! $applicable->contains($type->id)) {
            abort(403, 'Jenis berkas ini tidak diperlukan untuk jalur pendaftaran Anda.');
        }
    }
}
