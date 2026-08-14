<?php

namespace App\View\Composers;

use App\Support\ApplicantSession;
use Illuminate\View\View;

/**
 * Supplies the applicant layout with the signed-in registration and its
 * unread notifications, so no controller has to pass them explicitly.
 */
class ApplicantComposer
{
    public function __construct(private readonly ApplicantSession $session) {}

    public function compose(View $view): void
    {
        $registration = $this->session->registration();

        $view->with('registration', $registration);
        $view->with(
            'notifications',
            $registration?->notifications()->latest('created_at')->limit(10)->get() ?? collect()
        );
        $view->with(
            'unreadCount',
            $registration?->notifications()->unread()->count() ?? 0
        );
    }
}
