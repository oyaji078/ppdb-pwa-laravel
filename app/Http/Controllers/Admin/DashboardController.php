<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\AcademicYear;
use App\Models\Announcement;
use App\Models\RegistrationWave;
use App\Services\DashboardService;
use Illuminate\Contracts\View\View;
use Illuminate\Http\Request;

class DashboardController extends Controller
{
    public function __construct(private readonly DashboardService $dashboard) {}

    public function index(Request $request): View
    {
        $years = AcademicYear::query()->orderByDesc('start_year')->get();
        $yearId = $request->integer('academic_year_id') ?: AcademicYear::current()?->id;
        $waveId = $request->integer('registration_wave_id') ?: null;

        return view('admin.dashboard', [
            'academicYears' => $years,
            'waves' => RegistrationWave::query()
                ->when($yearId, fn ($q) => $q->where('academic_year_id', $yearId))
                ->orderBy('code')->get(),
            'selectedYearId' => $yearId,
            'selectedWaveId' => $waveId,
            'statistics' => $this->dashboard->statistics($yearId, $waveId),
            'trend' => $this->dashboard->registrationTrend($yearId, $waveId),
            'verificationChart' => $this->dashboard->verificationBreakdown($yearId, $waveId),
            'programChart' => $this->dashboard->programDistribution($yearId, $waveId),
            'latestApplicants' => $this->dashboard->latestApplicants($yearId, $waveId),
            'pendingWork' => $this->dashboard->pendingWork($yearId),
            'announcements' => Announcement::query()
                ->visible()
                ->latest('published_at')
                ->limit(4)
                ->get(),
        ]);
    }
}
