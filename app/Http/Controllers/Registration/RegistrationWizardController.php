<?php

namespace App\Http\Controllers\Registration;

use App\Http\Controllers\Controller;
use App\Http\Requests\Registration\AddressRequest;
use App\Http\Requests\Registration\BiodataRequest;
use App\Http\Requests\Registration\ParentGuardianRequest;
use App\Http\Requests\Registration\PreviousSchoolRequest;
use App\Http\Requests\Registration\ProgramSelectionRequest;
use App\Models\Registration;
use App\Services\ApplicantMailer;
use App\Services\PdfService;
use App\Services\RegistrationService;
use App\Support\ApplicantSession;
use Illuminate\Contracts\View\View;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Http\Response;
use Illuminate\Support\Facades\Storage;

/**
 * The seven-step registration form, filled in after the applicant has opened an
 * account and logged in.
 *
 * Each step persists immediately, so an applicant can close the browser and
 * pick up where they left off by logging back in.
 */
class RegistrationWizardController extends Controller
{
    /**
     * Maps the persisted step name back onto its route, so an applicant who
     * logs back in lands where they left off.
     */
    private const STEP_ROUTES = [
        'biodata' => 'registration.biodata',
        'alamat' => 'registration.address',
        'orang-tua' => 'registration.parents',
        'asal-sekolah' => 'registration.previous-school',
        'program' => 'registration.program',
        'berkas' => 'registration.documents',
        'review' => 'registration.review',
    ];

    public function __construct(
        private readonly ApplicantSession $session,
        private readonly RegistrationService $registrations,
        private readonly PdfService $pdf,
        private readonly ApplicantMailer $mailer,
    ) {}

    // -- Step 1: Biodata ------------------------------------------------------

    public function biodata(): View|RedirectResponse
    {
        $draft = $this->requireDraft();

        return $draft instanceof RedirectResponse ? $draft : view('registration.biodata', [
            'registration' => $draft,
            'applicant' => $draft->applicant,
        ]);
    }

    public function storeBiodata(BiodataRequest $request): RedirectResponse
    {
        $draft = $this->requireDraft();

        if ($draft instanceof RedirectResponse) {
            return $draft;
        }

        $applicant = $draft->applicant;
        $previousEmail = $applicant->email;

        $applicant->update($request->validated());

        // A changed address has not been proved yet, so the old confirmation
        // cannot carry over — drop it and send a fresh link.
        if ($applicant->email !== $previousEmail) {
            $applicant->forceFill(['email_verified_at' => null])->save();
            $this->mailer->sendEmailVerification($draft);
        }

        $this->registrations->advanceStep($draft, 'alamat');

        return redirect()->route('registration.address')
            ->with('success', 'Biodata tersimpan.');
    }

    // -- Step 3: Alamat -------------------------------------------------------

    public function address(): View|RedirectResponse
    {
        $draft = $this->requireDraft();

        return $draft instanceof RedirectResponse ? $draft : view('registration.address', [
            'registration' => $draft,
            'address' => $draft->applicant->address,
        ]);
    }

    public function storeAddress(AddressRequest $request): RedirectResponse
    {
        $draft = $this->requireDraft();

        if ($draft instanceof RedirectResponse) {
            return $draft;
        }

        $draft->applicant->address()->updateOrCreate([], $request->validated());
        $this->registrations->advanceStep($draft, 'orang-tua');

        return redirect()->route('registration.parents')
            ->with('success', 'Data alamat tersimpan.');
    }

    // -- Step 4: Orang Tua / Wali --------------------------------------------

    public function parents(): View|RedirectResponse
    {
        $draft = $this->requireDraft();

        return $draft instanceof RedirectResponse ? $draft : view('registration.parents', [
            'registration' => $draft,
            'father' => $draft->applicant->father(),
            'mother' => $draft->applicant->mother(),
            'guardian' => $draft->applicant->guardian(),
        ]);
    }

    public function storeParents(ParentGuardianRequest $request): RedirectResponse
    {
        $draft = $this->requireDraft();

        if ($draft instanceof RedirectResponse) {
            return $draft;
        }

        $applicant = $draft->applicant;

        foreach (['father', 'mother', 'guardian'] as $relationship) {
            $data = $request->validated()[$relationship] ?? [];

            // The guardian block is optional: an empty name means "no guardian".
            if ($relationship === 'guardian' && blank($data['name'] ?? null)) {
                $applicant->parentGuardians()->where('relationship', 'guardian')->delete();

                continue;
            }

            $applicant->parentGuardians()->updateOrCreate(
                ['relationship' => $relationship],
                [
                    'name' => $data['name'] ?? null,
                    'nik' => $data['nik'] ?? null,
                    'birth_date' => $data['birth_date'] ?? null,
                    'education' => $data['education'] ?? null,
                    'occupation' => $data['occupation'] ?? null,
                    'monthly_income' => $data['monthly_income'] ?? null,
                    'phone' => $data['phone'] ?? null,
                    'address' => $data['address'] ?? null,
                    // The guardian block has no "still living" box; only the
                    // parents do, and their checkbox always reports its state.
                    'is_alive' => $relationship === 'guardian'
                        ? true
                        : (bool) ($data['is_alive'] ?? false),
                ]
            );
        }

        $this->registrations->advanceStep($draft, 'asal-sekolah');

        return redirect()->route('registration.previous-school')
            ->with('success', 'Data orang tua/wali tersimpan.');
    }

    // -- Step 5: Asal Sekolah -------------------------------------------------

    public function previousSchool(): View|RedirectResponse
    {
        $draft = $this->requireDraft();

        return $draft instanceof RedirectResponse ? $draft : view('registration.previous-school', [
            'registration' => $draft,
            'school' => $draft->applicant->previousSchool,
        ]);
    }

    public function storePreviousSchool(PreviousSchoolRequest $request): RedirectResponse
    {
        $draft = $this->requireDraft();

        if ($draft instanceof RedirectResponse) {
            return $draft;
        }

        $draft->applicant->previousSchool()->updateOrCreate([], $request->validated());
        $this->registrations->advanceStep($draft, 'program');

        return redirect()->route('registration.program')
            ->with('success', 'Data asal sekolah tersimpan.');
    }

    // -- Step 6: Program ------------------------------------------------------

    public function program(): View|RedirectResponse
    {
        $draft = $this->requireDraft();

        if ($draft instanceof RedirectResponse) {
            return $draft;
        }

        $programs = $this->registrations->availablePrograms($draft);

        return view('registration.program', [
            'registration' => $draft,
            'programs' => $programs,
            'remainingQuota' => $programs->mapWithKeys(
                fn ($program) => [$program->id => $this->registrations->programRemainingQuota($program)]
            ),
        ]);
    }

    public function storeProgram(ProgramSelectionRequest $request): RedirectResponse
    {
        $draft = $this->requireDraft();

        if ($draft instanceof RedirectResponse) {
            return $draft;
        }

        $draft->update(['program_id' => $request->integer('program_id')]);
        $this->registrations->advanceStep($draft, 'berkas');

        return redirect()->route('registration.documents')
            ->with('success', 'Program pilihan tersimpan.');
    }

    // -- Step 8: Review & submit ---------------------------------------------

    public function review(): View|RedirectResponse
    {
        $draft = $this->requireDraft();

        if ($draft instanceof RedirectResponse) {
            return $draft;
        }

        $this->registrations->advanceStep($draft, 'review');

        return view('registration.review', [
            'registration' => $draft,
            'applicant' => $draft->applicant,
            'blockers' => $this->registrations->submissionBlockers($draft),
            'documentTypes' => $draft->applicableDocumentTypes(),
        ]);
    }

    public function submit(Request $request): RedirectResponse
    {
        $draft = $this->requireDraft();

        if ($draft instanceof RedirectResponse) {
            return $draft;
        }

        $this->registrations->submit($draft, $request->boolean('statement_agreed'));

        return redirect()->route('registration.success');
    }

    /**
     * Shown right after submitting. The applicant is still logged in, so the
     * registration comes from the session rather than a one-shot flash — the
     * page can be reloaded, and there is no access code to reveal because the
     * applicant chose it themselves at sign-up.
     */
    public function success(): View|RedirectResponse
    {
        $registration = $this->session->registration();

        if ($registration === null) {
            return redirect()->route('login');
        }

        if ($registration->isDraft()) {
            return redirect()->route('registration.resume');
        }

        return view('registration.success', [
            'registration' => $registration,
            'statusUrl' => $this->pdf->statusUrl($registration),
        ]);
    }

    /**
     * Receipt download offered on the success page.
     */
    public function downloadReceipt(): Response|RedirectResponse
    {
        $registration = $this->session->registration();

        if ($registration === null || $registration->isDraft()) {
            return redirect()->route('login')
                ->with('error', 'Sesi Anda sudah berakhir. Masuk dengan nomor pendaftaran dan kode akses untuk mengunduh bukti.');
        }

        $document = $registration->generatedDocuments()
            ->where('type', 'registration_receipt')
            ->latest('generated_at')
            ->first();

        $disk = Storage::disk(config('ppdb.storage.disk'));

        if ($document === null || ! $disk->exists($document->storage_path)) {
            $document = $this->pdf->generateReceipt($registration);
        }

        return response()->make(
            $disk->get($document->storage_path),
            200,
            [
                'Content-Type' => 'application/pdf',
                'Content-Disposition' => 'attachment; filename="'.$this->pdf->receiptFileName($registration).'"',
            ]
        );
    }

    /**
     * The form is filled in behind the applicant session, so the draft comes
     * from whoever is logged in. The route middleware has already guaranteed a
     * session; what is checked here is that it still belongs to a draft — once
     * submitted, the registration is read-only and lives in the portal.
     */
    private function requireDraft(): Registration|RedirectResponse
    {
        $registration = $this->session->registration();

        if ($registration === null) {
            return redirect()->route('login')
                ->with('warning', 'Sesi Anda telah berakhir. Silakan masuk kembali.');
        }

        if (! $registration->isDraft()) {
            return redirect()->route('applicant.dashboard')
                ->with('info', 'Pendaftaran Anda sudah dikirim dan tidak dapat diubah lagi.');
        }

        $registration->loadMissing([
            'applicant.address', 'applicant.parentGuardians', 'applicant.previousSchool',
            'academicYear', 'wave', 'admissionTrack', 'program', 'documents.documentType',
        ]);

        return $registration;
    }

    /**
     * Sends a logged-in applicant back to whichever step they had reached.
     */
    public function resume(): RedirectResponse
    {
        $registration = $this->session->registration();

        if ($registration === null) {
            return redirect()->route('login');
        }

        if (! $registration->isDraft()) {
            return redirect()->route('applicant.dashboard');
        }

        return redirect()->route(self::STEP_ROUTES[$registration->current_step] ?? 'registration.biodata');
    }
}
