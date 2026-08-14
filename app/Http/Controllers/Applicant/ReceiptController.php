<?php

namespace App\Http\Controllers\Applicant;

use App\Http\Controllers\Controller;
use App\Models\GeneratedDocument;
use App\Services\PdfService;
use App\Support\ApplicantSession;
use Illuminate\Http\Response;
use Illuminate\Support\Facades\Storage;

class ReceiptController extends Controller
{
    public function __construct(
        private readonly ApplicantSession $session,
        private readonly PdfService $pdf,
    ) {}

    /**
     * Streams the applicant's own receipt from private storage, regenerating
     * it if the stored file has gone missing.
     */
    public function download(): Response
    {
        $registration = $this->session->registration();

        abort_if($registration->registration_number === null, 404, 'Bukti pendaftaran belum tersedia.');

        $disk = Storage::disk(config('ppdb.storage.disk'));

        $document = $registration->generatedDocuments()
            ->where('type', GeneratedDocument::TYPE_RECEIPT)
            ->latest('generated_at')
            ->first();

        if ($document === null || ! $disk->exists($document->storage_path)) {
            $document = $this->pdf->generateReceipt($registration);
        }

        return response()->make($disk->get($document->storage_path), 200, [
            'Content-Type' => 'application/pdf',
            'Content-Disposition' => 'attachment; filename="'.$this->pdf->receiptFileName($registration).'"',
            'Cache-Control' => 'no-store, private',
        ]);
    }
}
