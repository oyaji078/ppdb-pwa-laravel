<?php

namespace App\Services;

use App\Models\GeneratedDocument;
use App\Models\Registration;
use App\Support\SettingsRepository;
use Barryvdh\DomPDF\Facade\Pdf;
use Endroid\QrCode\Builder\Builder;
use Endroid\QrCode\Encoding\Encoding;
use Endroid\QrCode\ErrorCorrectionLevel;
use Endroid\QrCode\Writer\PngWriter;
use Illuminate\Contracts\Filesystem\Filesystem;
use Illuminate\Support\Facades\Storage;

/**
 * Renders the A4 registration receipt and stores it on the private disk.
 */
class PdfService
{
    public function __construct(private readonly SettingsRepository $settings) {}

    private function disk(): Filesystem
    {
        return Storage::disk(config('ppdb.storage.disk'));
    }

    /**
     * Build the receipt PDF and record it in generated_documents.
     *
     * @param  string|null  $accessCode  Plaintext code, available only at the
     *                                   moment of submission or a reset.
     */
    public function generateReceipt(Registration $registration, ?string $accessCode = null): GeneratedDocument
    {
        $registration->loadMissing([
            'applicant.address', 'applicant.parentGuardians', 'applicant.previousSchool',
            'academicYear', 'wave', 'admissionTrack', 'program',
            'documents.documentType',
        ]);

        $pdf = Pdf::loadView('pdf.registration-receipt', [
            'registration' => $registration,
            'applicant' => $registration->applicant,
            'accessCode' => $accessCode,
            'settings' => $this->settings,
            'logoData' => $this->logoDataUri(),
            'qrData' => $this->qrDataUri($registration),
            'documents' => $registration->applicableDocumentTypes(),
        ])->setPaper('a4');

        $content = $pdf->output();

        $path = $this->receiptPath($registration);
        $this->disk()->put($path, $content);

        return GeneratedDocument::query()->updateOrCreate(
            [
                'registration_id' => $registration->id,
                'type' => GeneratedDocument::TYPE_RECEIPT,
            ],
            [
                'document_number' => $registration->registration_number,
                'storage_path' => $path,
                'checksum' => hash('sha256', $content),
                'generated_at' => now(),
            ]
        );
    }

    /**
     * One-time recovery sheet produced when an admin resets an access code.
     */
    public function generateAccessCodeRecovery(Registration $registration, string $accessCode): string
    {
        $registration->loadMissing(['applicant', 'academicYear', 'wave']);

        $pdf = Pdf::loadView('pdf.access-code-recovery', [
            'registration' => $registration,
            'applicant' => $registration->applicant,
            'accessCode' => $accessCode,
            'settings' => $this->settings,
            'logoData' => $this->logoDataUri(),
            'qrData' => $this->qrDataUri($registration),
        ])->setPaper('a4');

        return $pdf->output();
    }

    public function receiptFileName(Registration $registration): string
    {
        return 'Bukti-Pendaftaran-'.$registration->registration_number.'.pdf';
    }

    public function receiptPath(Registration $registration): string
    {
        return sprintf(
            '%s/%s/%s/bukti/%s',
            config('ppdb.storage.root_folder'),
            $registration->academicYear->start_year,
            $registration->registration_number,
            $this->receiptFileName($registration)
        );
    }

    /**
     * QR encodes the public status-check URL only. It never carries the access
     * code, so a photographed receipt does not leak credentials.
     */
    public function statusUrl(Registration $registration): string
    {
        return route('status.form', ['registration' => $registration->registration_number]);
    }

    private function qrDataUri(Registration $registration): ?string
    {
        try {
            $result = (new Builder(
                writer: new PngWriter,
                data: $this->statusUrl($registration),
                encoding: new Encoding('UTF-8'),
                errorCorrectionLevel: ErrorCorrectionLevel::Medium,
                size: 220,
                margin: 8,
            ))->build();

            return $result->getDataUri();
        } catch (\Throwable) {
            return null;
        }
    }

    private function logoDataUri(): ?string
    {
        $path = $this->settings->logoPath();

        if ($path === null || ! is_readable($path)) {
            return null;
        }

        $mime = @mime_content_type($path) ?: 'image/png';

        return 'data:'.$mime.';base64,'.base64_encode((string) file_get_contents($path));
    }
}
