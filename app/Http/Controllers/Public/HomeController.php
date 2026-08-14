<?php

namespace App\Http\Controllers\Public;

use App\Enums\AnnouncementAudience;
use App\Http\Controllers\Controller;
use App\Models\AcademicYear;
use App\Models\AdmissionTrack;
use App\Models\Announcement;
use App\Models\Facility;
use App\Models\News;
use App\Models\PpdbSchedule;
use App\Models\Registration;
use App\Models\SchoolProgram;
use Illuminate\Contracts\View\View;

class HomeController extends Controller
{
    public function index(): View
    {
        $year = AcademicYear::current();

        return view('public.home', [
            'academicYear' => $year,
            'tracks' => $year
                ? AdmissionTrack::query()->where('academic_year_id', $year->id)->active()
                    ->orderBy('sort_order')->get()
                : collect(),
            'schedules' => $year
                ? PpdbSchedule::query()->where('academic_year_id', $year->id)->public()
                    ->orderBy('sort_order')->orderBy('start_at')->limit(6)->get()
                : collect(),
            'openWaves' => $year ? $year->waves()->open()->orderBy('code')->get() : collect(),
            'schoolPrograms' => SchoolProgram::query()->active()->orderBy('sort_order')->limit(4)->get(),
            'facilities' => Facility::query()->active()->orderBy('sort_order')->limit(6)->get(),
            'latestNews' => News::query()->published()->latest('published_at')->limit(3)->get(),
            'announcements' => Announcement::query()
                ->visible()
                ->where('audience', AnnouncementAudience::Public)
                ->latest('published_at')
                ->limit(3)
                ->get(),
            'registeredCount' => $year
                ? Registration::query()->where('academic_year_id', $year->id)->submitted()->count()
                : 0,
            'faq' => settings()->faq(),
        ]);
    }
}
