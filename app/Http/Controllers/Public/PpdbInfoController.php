<?php

namespace App\Http\Controllers\Public;

use App\Enums\AnnouncementAudience;
use App\Http\Controllers\Controller;
use App\Models\AcademicYear;
use App\Models\AdmissionTrack;
use App\Models\Announcement;
use App\Models\DocumentType;
use App\Models\PpdbSchedule;
use App\Models\Program;
use Illuminate\Contracts\View\View;

class PpdbInfoController extends Controller
{
    public function index(): View
    {
        $year = AcademicYear::current();

        return view('public.ppdb.index', [
            'academicYear' => $year,
            'waves' => $year ? $year->waves()->where('is_active', true)->orderBy('code')->get() : collect(),
            'tracks' => $year
                ? AdmissionTrack::query()->where('academic_year_id', $year->id)->active()->orderBy('sort_order')->get()
                : collect(),
            'programs' => $year
                ? Program::query()->where('academic_year_id', $year->id)->active()->orderBy('sort_order')->get()
                : collect(),
            'schedules' => $year
                ? PpdbSchedule::query()->where('academic_year_id', $year->id)->public()
                    ->orderBy('sort_order')->orderBy('start_at')->get()
                : collect(),
            'announcements' => Announcement::query()
                ->visible()
                ->where('audience', AnnouncementAudience::Public)
                ->when($year, fn ($query) => $query->where(
                    fn ($q) => $q->where('academic_year_id', $year->id)->orWhereNull('academic_year_id')
                ))
                ->latest('published_at')
                ->limit(5)
                ->get(),
        ]);
    }

    public function schedule(): View
    {
        $year = AcademicYear::current();

        return view('public.ppdb.schedule', [
            'academicYear' => $year,
            'schedules' => $year
                ? PpdbSchedule::query()
                    ->where('academic_year_id', $year->id)
                    ->public()
                    ->with('wave')
                    ->orderBy('sort_order')
                    ->orderBy('start_at')
                    ->get()
                : collect(),
        ]);
    }

    public function requirements(): View
    {
        $year = AcademicYear::current();

        $tracks = $year
            ? AdmissionTrack::query()
                ->where('academic_year_id', $year->id)
                ->active()
                ->with(['documentTypes' => fn ($query) => $query->where('document_types.is_active', true)])
                ->orderBy('sort_order')
                ->get()
            : collect();

        return view('public.ppdb.requirements', [
            'academicYear' => $year,
            'tracks' => $tracks,
            'allDocumentTypes' => $year
                ? DocumentType::query()->where('academic_year_id', $year->id)->active()->orderBy('sort_order')->get()
                : collect(),
        ]);
    }
}
