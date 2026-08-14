<?php

namespace App\Http\Controllers\Applicant;

use App\Http\Controllers\Controller;
use App\Models\Announcement;
use App\Support\ApplicantSession;
use Illuminate\Contracts\View\View;

class AnnouncementController extends Controller
{
    public function __construct(private readonly ApplicantSession $session) {}

    public function index(): View
    {
        $registration = $this->session->registration();

        return view('applicant.announcements.index', [
            'registration' => $registration,
            'announcements' => Announcement::query()
                ->visible()
                ->forApplicant($this->isAccepted())
                ->latest('published_at')
                ->paginate(10),
        ]);
    }

    public function show(Announcement $announcement): View
    {
        abort_unless($announcement->is_published, 404);

        // Accepted-only announcements stay hidden until this applicant is
        // actually accepted and the result has been published.
        abort_unless(
            Announcement::query()
                ->visible()
                ->forApplicant($this->isAccepted())
                ->whereKey($announcement->id)
                ->exists(),
            403
        );

        return view('applicant.announcements.show', [
            'registration' => $this->session->registration(),
            'announcement' => $announcement,
        ]);
    }

    private function isAccepted(): bool
    {
        $registration = $this->session->registration();

        return $registration->isAccepted() && $registration->hasPublishedResult();
    }
}
