<?php

namespace App\Services;

use App\Models\ActivityLog;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Request;

/**
 * Append-only audit trail for administrator actions.
 */
class ActivityLogger
{
    public const LOGIN = 'admin_login';

    public const LOGOUT = 'admin_logout';

    public const DOCUMENT_VERIFIED = 'document_verified';

    public const REGISTRATION_STATUS_CHANGED = 'registration_status_changed';

    public const SELECTION_DECIDED = 'selection_decided';

    public const SELECTION_PUBLISHED = 'selection_published';

    public const ACCESS_CODE_RESET = 'access_code_reset';

    public const CONFIGURATION_CHANGED = 'configuration_changed';

    public const ANNOUNCEMENT_PUBLISHED = 'announcement_published';

    public const REREGISTRATION_UPDATED = 'reregistration_updated';

    public const REPORT_EXPORTED = 'report_exported';

    public function log(string $action, ?string $description = null, ?Model $subject = null): ActivityLog
    {
        return ActivityLog::query()->create([
            'user_id' => Auth::id(),
            'action' => $action,
            'subject_type' => $subject !== null ? $subject::class : null,
            'subject_id' => $subject?->getKey(),
            'description' => $description,
            'ip_address' => Request::ip(),
            'user_agent' => substr((string) Request::userAgent(), 0, 255),
        ]);
    }

    /**
     * Human label for an action code, used by the activity log screen.
     */
    public static function label(string $action): string
    {
        return match ($action) {
            self::LOGIN => 'Login Admin',
            self::LOGOUT => 'Logout Admin',
            self::DOCUMENT_VERIFIED => 'Verifikasi Berkas',
            self::REGISTRATION_STATUS_CHANGED => 'Perubahan Status Pendaftaran',
            self::SELECTION_DECIDED => 'Penetapan Hasil Seleksi',
            self::SELECTION_PUBLISHED => 'Publikasi Hasil Seleksi',
            self::ACCESS_CODE_RESET => 'Reset Kode Akses',
            self::CONFIGURATION_CHANGED => 'Perubahan Konfigurasi',
            self::ANNOUNCEMENT_PUBLISHED => 'Publikasi Pengumuman',
            self::REREGISTRATION_UPDATED => 'Perubahan Daftar Ulang',
            self::REPORT_EXPORTED => 'Ekspor Laporan',
            default => ucfirst(str_replace('_', ' ', $action)),
        };
    }

    /**
     * @return array<string, string>
     */
    public static function actionOptions(): array
    {
        $actions = [
            self::LOGIN, self::LOGOUT, self::DOCUMENT_VERIFIED, self::REGISTRATION_STATUS_CHANGED,
            self::SELECTION_DECIDED, self::SELECTION_PUBLISHED, self::ACCESS_CODE_RESET,
            self::CONFIGURATION_CHANGED, self::ANNOUNCEMENT_PUBLISHED, self::REREGISTRATION_UPDATED,
            self::REPORT_EXPORTED,
        ];

        $options = [];

        foreach ($actions as $action) {
            $options[$action] = self::label($action);
        }

        return $options;
    }
}
