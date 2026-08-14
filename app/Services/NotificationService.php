<?php

namespace App\Services;

use App\Models\Notification;
use App\Models\Registration;

/**
 * Creates the applicant-facing notifications shown in the portal bell menu.
 */
class NotificationService
{
    public function push(
        Registration $registration,
        string $type,
        string $title,
        string $message,
        ?string $url = null,
    ): Notification {
        return Notification::query()->create([
            'registration_id' => $registration->id,
            'type' => $type,
            'title' => $title,
            'message' => $message,
            'url' => $url,
        ]);
    }

    public function registrationSubmitted(Registration $registration): void
    {
        $this->push(
            $registration,
            Notification::TYPE_REGISTRATION_SUBMITTED,
            'Pendaftaran Diterima',
            'Pendaftaran Anda telah kami terima dan sedang menunggu verifikasi berkas oleh panitia.',
            route('applicant.dashboard'),
        );
    }

    public function documentRevisionRequired(Registration $registration, string $documentName, string $note): void
    {
        $this->push(
            $registration,
            Notification::TYPE_DOCUMENT_REVISION,
            'Berkas Perlu Perbaikan',
            sprintf('%s perlu diunggah ulang. Catatan panitia: %s', $documentName, $note),
            route('applicant.documents.index'),
        );
    }

    public function documentRejected(Registration $registration, string $documentName, string $note): void
    {
        $this->push(
            $registration,
            Notification::TYPE_DOCUMENT_REVISION,
            'Berkas Ditolak',
            sprintf('%s ditolak. Catatan panitia: %s', $documentName, $note),
            route('applicant.documents.index'),
        );
    }

    public function verificationCompleted(Registration $registration): void
    {
        $this->push(
            $registration,
            Notification::TYPE_VERIFICATION_COMPLETE,
            'Verifikasi Selesai',
            'Seluruh dokumen Anda telah diverifikasi. Pendaftaran Anda dinyatakan terverifikasi dan akan masuk tahap seleksi.',
            route('applicant.dashboard'),
        );
    }

    public function selectionPublished(Registration $registration): void
    {
        $this->push(
            $registration,
            Notification::TYPE_SELECTION_PUBLISHED,
            'Hasil Seleksi Tersedia',
            'Hasil seleksi Anda telah diumumkan. Silakan membuka dashboard untuk melihat hasilnya.',
            route('applicant.dashboard'),
        );
    }

    public function reregistrationOpened(Registration $registration): void
    {
        $this->push(
            $registration,
            Notification::TYPE_REREGISTRATION,
            'Daftar Ulang Dibuka',
            'Selamat, Anda dinyatakan diterima. Silakan menyelesaikan proses daftar ulang sesuai jadwal.',
            route('applicant.dashboard'),
        );
    }

    public function reregistrationCompleted(Registration $registration): void
    {
        $this->push(
            $registration,
            Notification::TYPE_REREGISTRATION,
            'Daftar Ulang Selesai',
            'Proses daftar ulang Anda telah dinyatakan selesai oleh panitia.',
            route('applicant.dashboard'),
        );
    }

    public function accessCodeReset(Registration $registration): void
    {
        $this->push(
            $registration,
            Notification::TYPE_ACCESS_CODE_RESET,
            'Kode Akses Direset',
            'Kode akses Anda telah direset oleh panitia. Gunakan kode akses baru yang diberikan panitia untuk masuk.',
        );
    }
}
