<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\RegistrationDocument;
use App\Services\DocumentService;
use Symfony\Component\HttpFoundation\BinaryFileResponse;

/**
 * Serves applicant uploads to authorized staff. Files never have a public URL;
 * every byte passes through this policy check first.
 */
class RegistrationDocumentController extends Controller
{
    public function __construct(private readonly DocumentService $documents) {}

    public function preview(RegistrationDocument $document): BinaryFileResponse
    {
        $this->authorize('view', $document);

        $path = $this->documents->absolutePath($document);

        abort_if($path === null, 404, 'Berkas tidak ditemukan pada penyimpanan.');

        return response()->file($path, [
            'Content-Type' => $document->mime_type,
            'Content-Disposition' => 'inline; filename="'.addslashes($document->original_name).'"',
            'Cache-Control' => 'no-store, private',
        ]);
    }

    public function download(RegistrationDocument $document): BinaryFileResponse
    {
        $this->authorize('download', $document);

        $path = $this->documents->absolutePath($document);

        abort_if($path === null, 404, 'Berkas tidak ditemukan pada penyimpanan.');

        return response()->download($path, $document->original_name, [
            'Cache-Control' => 'no-store, private',
        ]);
    }
}
