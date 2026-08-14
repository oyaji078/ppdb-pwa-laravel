<?php

namespace App\Http\Controllers\Admin;

use App\Enums\RegistrationStatus;
use App\Enums\ReregistrationStatus;
use App\Enums\SelectionStatus;
use App\Http\Controllers\Admin\Concerns\FiltersRegistrations;
use App\Http\Controllers\Controller;
use App\Models\GeneratedDocument;
use App\Models\Registration;
use App\Services\ActivityLogger;
use App\Services\PdfService;
use Illuminate\Contracts\View\View;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Http\Response;
use Illuminate\Support\Facades\Storage;

class RegistrationController extends Controller
{
    use FiltersRegistrations;

    public function __construct(
        private readonly PdfService $pdf,
        private readonly ActivityLogger $activity,
    ) {}

    public function index(Request $request): View
    {
        $this->authorize('viewAny', Registration::class);

        $registrations = $this->filteredRegistrations($request)
            ->latest('submitted_at')
            ->paginate($this->perPage($request))
            ->withQueryString();

        return view('admin.registrations.index', array_merge(
            $this->filterOptions($request),
            [
                'registrations' => $registrations,
                'registrationStatuses' => RegistrationStatus::options(),
                'selectionStatuses' => SelectionStatus::options(),
                'reregistrationStatuses' => ReregistrationStatus::options(),
            ]
        ));
    }

    public function show(Registration $registration): View
    {
        $this->authorize('view', $registration);

        $registration->load([
            'applicant.address', 'applicant.parentGuardians', 'applicant.previousSchool',
            'academicYear', 'wave', 'admissionTrack', 'program',
            'documents.documentType', 'documents.verifier',
            'selectionResult.decidedBy', 'reregistration.verifier',
            'verifier', 'notifications',
        ]);

        return view('admin.registrations.show', [
            'registration' => $registration,
            'applicant' => $registration->applicant,
            'documentTypes' => $registration->applicableDocumentTypes(),
            'canSeeSensitive' => request()->user()->can('viewSensitiveData', $registration),
        ]);
    }

    /**
     * Soft delete. Super admin only; the record remains restorable.
     */
    public function destroy(Registration $registration): RedirectResponse
    {
        $this->authorize('delete', $registration);

        $number = $registration->registration_number;
        $registration->delete();

        $this->activity->log(
            ActivityLogger::REGISTRATION_STATUS_CHANGED,
            sprintf('Pendaftaran %s dihapus (soft delete).', $number),
            $registration
        );

        return redirect()->route('admin.registrations.index')
            ->with('success', sprintf('Pendaftaran %s dipindahkan ke arsip.', $number));
    }

    public function restore(Registration $registration): RedirectResponse
    {
        $this->authorize('restore', $registration);

        $registration->restore();

        $this->activity->log(
            ActivityLogger::REGISTRATION_STATUS_CHANGED,
            sprintf('Pendaftaran %s dipulihkan.', $registration->registration_number),
            $registration
        );

        return back()->with('success', 'Pendaftaran dipulihkan.');
    }

    /**
     * Download the applicant's receipt, regenerating it if the file is missing.
     */
    public function receipt(Registration $registration): Response
    {
        $this->authorize('view', $registration);

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
