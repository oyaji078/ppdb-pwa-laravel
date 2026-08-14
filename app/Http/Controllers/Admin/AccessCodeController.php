<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Registration;
use App\Services\AccessCodeService;
use App\Services\ActivityLogger;
use App\Services\NotificationService;
use App\Services\PdfService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Http\Response;

/**
 * Access code reset.
 *
 * The old code is never visible to anyone, including admins — only its hash was
 * stored. Resetting issues a new code, bumps session_version so any live
 * applicant session stops working, and produces a one-time recovery sheet.
 */
class AccessCodeController extends Controller
{
    private const RECOVERY_SESSION_KEY = 'ppdb_access_code_recovery';

    public function __construct(
        private readonly AccessCodeService $accessCodes,
        private readonly PdfService $pdf,
        private readonly NotificationService $notifications,
        private readonly ActivityLogger $activity,
    ) {}

    public function reset(Registration $registration, Request $request): RedirectResponse
    {
        $this->authorize('resetAccessCode', $registration);

        $request->validate([
            'reason' => ['required', 'string', 'max:500'],
        ], [
            'reason.required' => 'Alasan reset kode akses wajib diisi.',
        ]);

        $newCode = $this->accessCodes->reset($registration);

        $this->activity->log(
            ActivityLogger::ACCESS_CODE_RESET,
            sprintf(
                'Kode akses %s direset. Alasan: %s',
                $registration->registration_number,
                $request->string('reason')
            ),
            $registration
        );

        $this->notifications->accessCodeReset($registration);

        // Held in the session for a single download, never written to the
        // database in plaintext.
        $request->session()->put(self::RECOVERY_SESSION_KEY, [
            'registration_id' => $registration->id,
            'code' => $newCode,
        ]);

        return back()->with('success', sprintf(
            'Kode akses baru berhasil dibuat: %s — unduh dokumen pemulihan sekarang, kode ini tidak dapat ditampilkan lagi.',
            $newCode
        ));
    }

    /**
     * One-time download of the recovery sheet. The code is removed from the
     * session as soon as the PDF is produced.
     */
    public function downloadRecovery(Registration $registration, Request $request): Response
    {
        $this->authorize('resetAccessCode', $registration);

        $recovery = $request->session()->get(self::RECOVERY_SESSION_KEY);

        abort_if(
            ! is_array($recovery) || ($recovery['registration_id'] ?? null) !== $registration->id,
            410,
            'Dokumen pemulihan hanya dapat diunduh satu kali, tepat setelah kode akses direset.'
        );

        $request->session()->forget(self::RECOVERY_SESSION_KEY);

        $content = $this->pdf->generateAccessCodeRecovery($registration, $recovery['code']);

        return response()->make($content, 200, [
            'Content-Type' => 'application/pdf',
            'Content-Disposition' => 'attachment; filename="Kode-Akses-'.$registration->registration_number.'.pdf"',
            'Cache-Control' => 'no-store, private',
        ]);
    }
}
