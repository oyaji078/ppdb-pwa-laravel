<?php

namespace App\Http\Controllers\Admin;

use App\Enums\DocumentStatus;
use App\Enums\RegistrationStatus;
use App\Http\Controllers\Admin\Concerns\FiltersRegistrations;
use App\Http\Controllers\Controller;
use App\Models\Registration;
use App\Models\RegistrationDocument;
use App\Services\VerificationService;
use Illuminate\Contracts\View\View;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;

class VerificationController extends Controller
{
    use FiltersRegistrations;

    public function __construct(private readonly VerificationService $verification) {}

    public function index(Request $request): View
    {
        $this->authorize('viewAny', Registration::class);

        $registrations = $this->filteredRegistrations($request)
            ->withCount([
                'documents',
                'documents as pending_documents_count' => fn (Builder $q) => $q
                    ->where('verification_status', DocumentStatus::Pending->value),
                'documents as verified_documents_count' => fn (Builder $q) => $q
                    ->where('verification_status', DocumentStatus::Verified->value),
            ])
            // Registrations still needing attention first.
            ->when(! $request->filled('registration_status'), fn (Builder $q) => $q->whereIn('registration_status', [
                RegistrationStatus::Submitted->value,
                RegistrationStatus::UnderReview->value,
                RegistrationStatus::RevisionRequired->value,
            ]))
            // CASE rather than MySQL's FIELD() so the ordering also works on
            // SQLite. Values are literals from the enum, never user input.
            ->orderByRaw(
                'CASE registration_status'
                ." WHEN '".RegistrationStatus::Submitted->value."' THEN 1"
                ." WHEN '".RegistrationStatus::UnderReview->value."' THEN 2"
                ." WHEN '".RegistrationStatus::RevisionRequired->value."' THEN 3"
                ." WHEN '".RegistrationStatus::Verified->value."' THEN 4"
                .' ELSE 5 END'
            )
            ->orderBy('submitted_at')
            ->paginate($this->perPage($request))
            ->withQueryString();

        return view('admin.verification.index', array_merge(
            $this->filterOptions($request),
            [
                'registrations' => $registrations,
                'registrationStatuses' => RegistrationStatus::options(),
            ]
        ));
    }

    public function show(Registration $registration): View
    {
        $this->authorize('verify', $registration);

        $registration->load([
            'applicant.address', 'applicant.parentGuardians', 'applicant.previousSchool',
            'academicYear', 'wave', 'admissionTrack', 'program',
            'documents.documentType', 'documents.verifier', 'documents.verificationLogs.admin',
        ]);

        return view('admin.verification.show', [
            'registration' => $registration,
            'applicant' => $registration->applicant,
            'documentTypes' => $registration->applicableDocumentTypes(),
            'blockers' => $this->verification->verificationBlockers($registration),
            'statusOptions' => DocumentStatus::verificationOptions(),
        ]);
    }

    /**
     * Verify, request a revision, or reject one document.
     */
    public function decide(Request $request, RegistrationDocument $document): RedirectResponse
    {
        $this->authorize('verify', $document);

        $validated = $request->validate([
            'verification_status' => ['required', 'string', 'in:'.implode(',', array_keys(DocumentStatus::verificationOptions()))],
            'verification_note' => ['nullable', 'string', 'max:1000'],
        ]);

        $status = DocumentStatus::from($validated['verification_status']);

        // VerificationService enforces the "note required on revision/reject"
        // rule and writes the audit log.
        $this->verification->decide(
            $document,
            $status,
            $request->user(),
            $validated['verification_note'] ?? null
        );

        return back()->with('success', sprintf('%s ditandai sebagai %s.', $document->documentType->name, $status->label()));
    }

    /**
     * Approves every document still awaiting a decision, in one press.
     *
     * When a set of scans is simply fine — which is the common case — deciding
     * them one at a time means one page reload per document. Anything already
     * marked for revision or rejected is left alone: clearing those is a real
     * judgement and has to stay deliberate.
     */
    public function approveAll(Registration $registration, Request $request): RedirectResponse
    {
        // Both relations are loaded up front: VerificationService writes an
        // activity line naming the document type and syncs the registration's
        // own status, and lazy loading is off outside production.
        $pending = $registration->documents()
            ->where('verification_status', DocumentStatus::Pending->value)
            ->with(['documentType', 'registration'])
            ->get();

        foreach ($pending as $document) {
            $this->authorize('verify', $document);
        }

        foreach ($pending as $document) {
            $this->verification->decide($document, DocumentStatus::Verified, $request->user(), null);
        }

        if ($pending->isEmpty()) {
            return back()->with('info', 'Tidak ada berkas yang menunggu verifikasi.');
        }

        return back()->with('success', sprintf(
            '%d berkas ditandai terverifikasi.',
            $pending->count()
        ));
    }

    public function complete(Registration $registration, Request $request): RedirectResponse
    {
        $this->authorize('completeVerification', $registration);

        $this->verification->completeVerification($registration, $request->user());

        return back()->with('success', sprintf(
            'Pendaftaran %s dinyatakan terverifikasi.',
            $registration->registration_number
        ));
    }

    public function reopen(Registration $registration, Request $request): RedirectResponse
    {
        $this->authorize('reopenVerification', $registration);

        $validated = $request->validate([
            'reason' => ['required', 'string', 'max:500'],
        ], [
            'reason.required' => 'Alasan membuka kembali verifikasi wajib diisi.',
        ]);

        $this->verification->reopenVerification($registration, $request->user(), $validated['reason']);

        return back()->with('success', 'Verifikasi dibuka kembali.');
    }
}
