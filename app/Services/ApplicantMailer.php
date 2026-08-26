<?php

namespace App\Services;

use App\Models\Registration;
use App\Support\MailConfigurator;
use App\Support\SettingsRepository;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Mail;
use Illuminate\Support\Facades\URL;
use Throwable;

/**
 * Sends the applicant-facing e-mails.
 *
 * Every send is best effort. A school with a wrong SMTP password must still be
 * able to accept registrations, so a failure here is logged and swallowed
 * rather than surfaced as a 500 in the middle of the form.
 */
class ApplicantMailer
{
    public function __construct(
        private readonly MailConfigurator $mail,
        private readonly SettingsRepository $settings,
    ) {}

    /**
     * Signed link the applicant follows to confirm their address. Seven days is
     * long enough to survive a weekend and a spam folder.
     */
    public function verificationUrl(Registration $registration): string
    {
        return URL::temporarySignedRoute(
            'registration.email.verify',
            now()->addDays(7),
            ['registration' => $registration->getKey()],
        );
    }

    public function sendEmailVerification(Registration $registration): bool
    {
        $applicant = $registration->applicant;

        if ($applicant === null || blank($applicant->email) || $applicant->hasVerifiedEmail()) {
            return false;
        }

        return $this->send($applicant->email, 'mail.verify-email', [
            'registration' => $registration,
            'applicant' => $applicant,
            'verificationUrl' => $this->verificationUrl($registration),
            'schoolName' => $this->settings->schoolName(),
            'admissionName' => $this->settings->admissionName(),
        ], sprintf('Verifikasi Email Pendaftaran %s', $registration->registration_number));
    }

    /**
     * Mirrors an in-app notification to e-mail. Only verified addresses are
     * written to, so a typo cannot turn the school into a spam source.
     */
    public function sendStatusNotification(Registration $registration, string $title, string $body): bool
    {
        $applicant = $registration->applicant;

        if ($applicant === null || ! $applicant->hasVerifiedEmail()) {
            return false;
        }

        return $this->send($applicant->email, 'mail.status-notification', [
            'registration' => $registration,
            'applicant' => $applicant,
            'title' => $title,
            'body' => $body,
            'portalUrl' => route('login'),
            'schoolName' => $this->settings->schoolName(),
            'admissionName' => $this->settings->admissionName(),
        ], $title.' — '.$registration->registration_number);
    }

    /**
     * Used by the "send test e-mail" button so an admin can prove the
     * credentials work without waiting for a real applicant.
     *
     * @throws Throwable the caller wants to show the real reason it failed.
     */
    public function sendTest(string $recipient): void
    {
        $this->mail->apply();

        Mail::send('mail.test', [
            'schoolName' => $this->settings->schoolName(),
            'admissionName' => $this->settings->admissionName(),
        ], function ($message) use ($recipient): void {
            $message->to($recipient)->subject('Uji Coba Pengiriman Email PPDB');
        });
    }

    /**
     * @param  array<string, mixed>  $data
     */
    private function send(string $recipient, string $view, array $data, string $subject): bool
    {
        if (! $this->mail->enabled()) {
            return false;
        }

        try {
            $this->mail->apply();

            Mail::send($view, $data, function ($message) use ($recipient, $subject): void {
                $message->to($recipient)->subject($subject);
            });

            return true;
        } catch (Throwable $e) {
            Log::warning('Gagal mengirim email pendaftar.', [
                'recipient' => $recipient,
                'subject' => $subject,
                'error' => $e->getMessage(),
            ]);

            return false;
        }
    }
}
