<?php

namespace App\Services;

use App\Enums\RegistrationStatus;
use App\Enums\SelectionStatus;
use App\Models\Registration;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Carbon;
use Illuminate\Support\Collection;

/**
 * Aggregates for the admin dashboard. Every figure comes from a query; nothing
 * on the dashboard is hardcoded.
 */
class DashboardService
{
    /**
     * Headline counters.
     *
     * @return array<string, int>
     */
    public function statistics(?int $academicYearId, ?int $waveId = null): array
    {
        $base = fn (): Builder => $this->scopedQuery($academicYearId, $waveId);

        return [
            'total' => $base()->count(),
            'verified' => $base()->where('registration_status', RegistrationStatus::Verified)->count(),
            'unverified' => $base()->whereIn('registration_status', [
                RegistrationStatus::Submitted->value,
                RegistrationStatus::UnderReview->value,
                RegistrationStatus::RevisionRequired->value,
            ])->count(),
            'accepted' => $base()->where('selection_status', SelectionStatus::Accepted)->count(),
            'rejected' => $base()->where('selection_status', SelectionStatus::Rejected)->count(),
            'reserve' => $base()->where('selection_status', SelectionStatus::Reserve)->count(),
        ];
    }

    /**
     * Daily submission counts for the last N days, ready for Chart.js.
     *
     * @return array{labels: array<int, string>, values: array<int, int>}
     */
    public function registrationTrend(?int $academicYearId, ?int $waveId = null, int $days = 14): array
    {
        $start = Carbon::today()->subDays($days - 1);

        $counts = $this->scopedQuery($academicYearId, $waveId)
            ->where('submitted_at', '>=', $start)
            ->selectRaw('DATE(submitted_at) as day, COUNT(*) as total')
            ->groupBy('day')
            ->pluck('total', 'day');

        $labels = [];
        $values = [];

        for ($i = 0; $i < $days; $i++) {
            $date = $start->copy()->addDays($i);
            $labels[] = $date->translatedFormat('d M');
            $values[] = (int) ($counts[$date->toDateString()] ?? 0);
        }

        return ['labels' => $labels, 'values' => $values];
    }

    /**
     * Registration status breakdown for the donut chart.
     *
     * @return array{labels: array<int, string>, values: array<int, int>, colors: array<int, string>}
     */
    public function verificationBreakdown(?int $academicYearId, ?int $waveId = null): array
    {
        $counts = $this->scopedQuery($academicYearId, $waveId)
            ->selectRaw('registration_status, COUNT(*) as total')
            ->groupBy('registration_status')
            ->pluck('total', 'registration_status');

        $labels = [];
        $values = [];
        $colors = [];

        $palette = [
            RegistrationStatus::Submitted->value => '#3b82f6',
            RegistrationStatus::UnderReview->value => '#6366f1',
            RegistrationStatus::RevisionRequired->value => '#f59e0b',
            RegistrationStatus::Verified->value => '#10b981',
        ];

        foreach ($palette as $status => $color) {
            $labels[] = RegistrationStatus::from($status)->label();
            $values[] = (int) ($counts[$status] ?? 0);
            $colors[] = $color;
        }

        return ['labels' => $labels, 'values' => $values, 'colors' => $colors];
    }

    /**
     * Submitted registrations grouped by program.
     *
     * @return array{labels: array<int, string>, values: array<int, int>}
     */
    public function programDistribution(?int $academicYearId, ?int $waveId = null): array
    {
        $rows = $this->scopedQuery($academicYearId, $waveId)
            ->with('program')
            ->get()
            ->groupBy(fn (Registration $registration) => $registration->program?->name ?? 'Belum Memilih')
            ->map->count()
            ->sortDesc();

        return [
            'labels' => $rows->keys()->all(),
            'values' => $rows->values()->map(fn ($value) => (int) $value)->all(),
        ];
    }

    /**
     * Submitted registrations grouped by admission track.
     *
     * @return array{labels: array<int, string>, values: array<int, int>}
     */
    public function trackDistribution(?int $academicYearId, ?int $waveId = null): array
    {
        $rows = $this->scopedQuery($academicYearId, $waveId)
            ->with('admissionTrack')
            ->get()
            ->groupBy(fn (Registration $registration) => $registration->admissionTrack?->name ?? '-')
            ->map->count()
            ->sortDesc();

        return [
            'labels' => $rows->keys()->all(),
            'values' => $rows->values()->map(fn ($value) => (int) $value)->all(),
        ];
    }

    /**
     * @return Collection<int, Registration>
     */
    public function latestApplicants(?int $academicYearId, ?int $waveId = null, int $limit = 8): Collection
    {
        return $this->scopedQuery($academicYearId, $waveId)
            ->with(['applicant.previousSchool', 'admissionTrack', 'program'])
            ->orderByDesc('submitted_at')
            ->limit($limit)
            ->get();
    }

    /**
     * Counts feeding the sidebar/quick-action badges.
     *
     * @return array<string, int>
     */
    public function pendingWork(?int $academicYearId): array
    {
        return [
            'documents_pending' => Registration::query()
                ->forYear($academicYearId)
                ->submitted()
                ->whereHas('documents', fn (Builder $q) => $q->where('verification_status', 'pending'))
                ->count(),
            'awaiting_selection' => Registration::query()
                ->forYear($academicYearId)
                ->where('registration_status', RegistrationStatus::Verified)
                ->whereDoesntHave('selectionResult')
                ->count(),
            'unpublished_results' => Registration::query()
                ->forYear($academicYearId)
                ->whereHas('selectionResult', fn (Builder $q) => $q->whereNull('published_at'))
                ->count(),
            'reregistration_pending' => Registration::query()
                ->forYear($academicYearId)
                ->where('reregistration_status', 'pending')
                ->count(),
        ];
    }

    /**
     * @return Builder<Registration>
     */
    private function scopedQuery(?int $academicYearId, ?int $waveId = null): Builder
    {
        return Registration::query()
            ->submitted()
            ->forYear($academicYearId)
            ->when($waveId, fn (Builder $query) => $query->where('registration_wave_id', $waveId));
    }
}
