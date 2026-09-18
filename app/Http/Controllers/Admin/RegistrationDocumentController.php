<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\RegistrationDocument;
use App\Services\DocumentService;
use Symfony\Component\HttpFoundation\StreamedResponse;

/**
 * Serves applicant uploads to authorized staff. Files never have a public URL;
 * every byte passes through this policy check first.
 */
class RegistrationDocumentController extends Controller
{
    public function __construct(private readonly DocumentService $documents) {}

    public function preview(RegistrationDocument $document): StreamedResponse
    {
        $this->authorize('view', $document);

        $response = $this->documents->response($document);

        abort_if($response === null, 404, 'Berkas tidak ditemukan pada penyimpanan.');

        return $response;
    }

    public function download(RegistrationDocument $document): StreamedResponse
    {
        $this->authorize('download', $document);

        $response = $this->documents->response($document, 'attachment');

        abort_if($response === null, 404, 'Berkas tidak ditemukan pada penyimpanan.');

        return $response;
    }
}
