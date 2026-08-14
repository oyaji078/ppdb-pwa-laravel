<?php

namespace App\Http\Controllers\Applicant;

use App\Http\Controllers\Controller;
use App\Models\Notification;
use App\Support\ApplicantSession;
use Illuminate\Http\RedirectResponse;

class NotificationController extends Controller
{
    public function __construct(private readonly ApplicantSession $session) {}

    public function markAsRead(Notification $notification): RedirectResponse
    {
        abort_unless($notification->registration_id === $this->session->id(), 403);

        if ($notification->isUnread()) {
            $notification->forceFill(['read_at' => now()])->save();
        }

        return $notification->url
            ? redirect()->to($notification->url)
            : redirect()->route('applicant.dashboard');
    }

    public function markAllAsRead(): RedirectResponse
    {
        $this->session->registration()
            ->notifications()
            ->unread()
            ->update(['read_at' => now()]);

        return back()->with('success', 'Semua notifikasi ditandai sudah dibaca.');
    }
}
