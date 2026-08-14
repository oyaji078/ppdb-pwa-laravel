<?php

namespace App\Http\Controllers\Public;

use App\Enums\AnnouncementAudience;
use App\Http\Controllers\Controller;
use App\Models\Announcement;
use Illuminate\Contracts\View\View;

class AnnouncementController extends Controller
{
    public function index(): View
    {
        return view('public.announcements.index', [
            'announcements' => Announcement::query()
                ->visible()
                ->where('audience', AnnouncementAudience::Public)
                ->latest('published_at')
                ->paginate(10),
        ]);
    }

    public function show(Announcement $announcement): View
    {
        // Only genuinely public announcements are reachable without a session;
        // applicant-only ones live behind the portal.
        abort_unless(
            $announcement->audience === AnnouncementAudience::Public && $announcement->is_published,
            404
        );

        return view('public.announcements.show', [
            'announcement' => $announcement,
        ]);
    }
}
