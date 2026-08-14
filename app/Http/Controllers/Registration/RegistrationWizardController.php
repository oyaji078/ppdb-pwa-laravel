<?php

namespace App\Http\Controllers\Registration;

use App\Http\Controllers\Controller;
use App\Http\Requests\Registration\AddressRequest;
use App\Http\Requests\Registration\BiodataRequest;
use App\Http\Requests\Registration\ParentGuardianRequest;
use App\Http\Requests\Registration\PreviousSchoolRequest;
use App\Http\Requests\Registration\ProgramSelectionRequest;
use App\Http\Requests\Registration\StartRegistrationRequest;
use App\Models\AcademicYear;
use App\Models\AdmissionTrack;
use App\Models\Registration;
use App\Models\RegistrationWave;
use App\Services\PdfService;
use App\Services\RegistrationService;
use App\Support\RegistrationDraft;
use Illuminate\Contracts\View\View;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Http\Response;
use Illuminate\Support\Facades\Storage;

/**
 * The eight-step public registration form.
 *
 * Each step persists immediately, so a visitor can close the browser and pick
 * up where they left off as long as the session survives.
 */
class RegistrationWizardController extends Controller
{
    public function __construct(
        private readonly RegistrationDraft $draft,
        private readonly RegistrationService $registrations,
        private readonly PdfService $pdf,
    ) {}

    // -- Step 1: Data Pendaftaran --------------------------------------------

    public function start(): View|RedirectResponse
    {
        $year = AcademicYear::current();
        $draft = $this->draft->current();

        if ($year === null || ! $year->registration_open) {
            return redirect()->route('ppdb.index')
                ->with('warning', 'Pendaftaran sedang tidak dibuka. Silakan periksa jadwal penerimaan.');
        }

        return view('registration.start', [
            'academicYear' => $year,
            'draft' => $draft,
            'waves' => $year->waves()->open()->orderBy('code')->get(),
            'tracks' => AdmissionTrack::query()
                ->where('academic_year_id', $year->id)->active()->orderBy('sort_order')->get(),
        ]);
    }

    public function storeStart(StartRegistrationRequest $request): RedirectResponse
    {
        $year = AcademicYear::current();
        $wave = RegistrationWave::query()->findOrFail($request->integer('registration_wave_id'));
        $track = AdmissionTrack::query()->findOrFail($request->integer('admission_track_id'));

        $draft = $this->draft->current();

        if ($draft === null) {
            $draft = $this->registrations->startDraft($year, $wave, $track);
            $this->draft->remember($draft);
        } else {
            $this->registrations->updateDraftChoice($draft, $wave, $track);
        }

        return redirect()->route('registration.biodata');
    }

    // -- Step 2: Biodata ------------------------------------------------------

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

        $draft->applicant->update($request->validated());
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
                    'birth_year' => $data['birth_year'] ?? null,
                    'education' => $data['education'] ?? null,
                    'occupation' => $data['occupation'] ?? null,
                    'monthly_income' => $data['monthly_income'] ?? null,
                    'phone' => $data['phone'] ?? null,
                    'address' => $data['address'] ?? null,
                    'is_alive' => (bool) ($data['is_alive'] ?? true),
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

        $accessCode = $this->registrations->submit($draft, $request->boolean('statement_agreed'));

        $this->draft->markSubmitted($draft);

        // Plaintext code is flashed, never stored: this is the only time it can
        // be shown to the applicant.
        $request->session()->flash('ppdb_plain_access_code', $accessCode);

        return redirect()->route('registration.success');
    }

    public function success(Request $request): View|RedirectResponse
    {
        $registration = $this->draft->submitted();

        if ($registration === null) {
            return redirect()->route('registration.start');
        }

        return view('registration.success', [
            'registration' => $registration,
            'accessCode' => $request->session()->get('ppdb_plain_access_code'),
            'statusUrl' => $this->pdf->statusUrl($registration),
        ]);
    }

    /**
     * Receipt download offered on the success page, before the applicant has
     * logged into the portal.
     */
    public function downloadReceipt(): Response|RedirectResponse
    {
        $registration = $this->draft->submitted();

        if ($registration === null) {
            return redirect()->route('status.form')
                ->with('error', 'Sesi pendaftaran sudah berakhir. Masuk dengan nomor pendaftaran dan kode akses untuk mengunduh bukti.');
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
     * Every step past the first needs a draft; without one the visitor is sent
     * back to the beginning.
     */
    private function requireDraft(): Registration|RedirectResponse
    {
        $draft = $this->draft->current();

        if ($draft === null) {
            return redirect()->route('registration.start')
                ->with('warning', 'Sesi pendaftaran belum dimulai atau sudah berakhir. Silakan mulai dari langkah pertama.');
        }

        return $draft;
    }
}
