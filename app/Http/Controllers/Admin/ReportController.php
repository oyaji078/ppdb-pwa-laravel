<?php

namespace App\Http\Controllers\Admin;

use App\Enums\RegistrationStatus;
use App\Enums\ReregistrationStatus;
use App\Enums\SelectionStatus;
use App\Http\Controllers\Admin\Concerns\FiltersRegistrations;
use App\Http\Controllers\Controller;
use App\Models\Registration;
use App\Services\ActivityLogger;
use App\Support\SettingsRepository;
use Barryvdh\DomPDF\Facade\Pdf;
use Illuminate\Contracts\View\View;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Http\Response;
use Illuminate\Support\Collection;
use OpenSpout\Common\Entity\Row;
use OpenSpout\Common\Entity\Style\Style;
use OpenSpout\Writer\XLSX\Writer as XlsxWriter;
use Symfony\Component\HttpFoundation\StreamedResponse;

class ReportController extends Controller
{
    use FiltersRegistrations;

    /**
     * Report presets. Each one narrows the base filtered query further.
     */
    private const PRESETS = [
        'semua' => 'Semua Pendaftar',
        'verifikasi' => 'Status Verifikasi',
        'diterima' => 'Pendaftar Diterima',
        'cadangan' => 'Pendaftar Cadangan',
        'ditolak' => 'Pendaftar Tidak Diterima',
        'daftar-ulang' => 'Daftar Ulang',
    ];

    public function __construct(
        private readonly SettingsRepository $settings,
        private readonly ActivityLogger $activity,
    ) {}

    public function index(Request $request): View
    {
        $this->authorize('viewAny', Registration::class);

        $preset = $this->preset($request);

        $registrations = $this->reportQuery($request)
            ->orderBy('registration_number')
            ->paginate($this->perPage($request, 25))
            ->withQueryString();

        $scope = $this->reportQuery($request);

        return view('admin.reports.index', array_merge(
            $this->filterOptions($request),
            [
                'registrations' => $registrations,
                'presets' => self::PRESETS,
                'preset' => $preset,
                'presetLabel' => self::PRESETS[$preset],
                'registrationStatuses' => RegistrationStatus::options(),
                'selectionStatuses' => SelectionStatus::options(),
                'reregistrationStatuses' => ReregistrationStatus::options(),
                'summary' => [
                    'total' => (clone $scope)->count(),
                    'verified' => (clone $scope)->where('registration_status', RegistrationStatus::Verified)->count(),
                    'accepted' => (clone $scope)->where('selection_status', SelectionStatus::Accepted)->count(),
                    'reserve' => (clone $scope)->where('selection_status', SelectionStatus::Reserve)->count(),
                    'rejected' => (clone $scope)->where('selection_status', SelectionStatus::Rejected)->count(),
                    'reregistered' => (clone $scope)->where('reregistration_status', ReregistrationStatus::Completed)->count(),
                ],
            ]
        ));
    }

    /**
     * Export the current report as PDF, CSV or XLSX.
     */
    public function export(Request $request): Response|StreamedResponse|RedirectResponse
    {
        $this->authorize('viewAny', Registration::class);

        $format = $request->string('format')->lower()->toString();

        if (! in_array($format, ['pdf', 'csv', 'xlsx'], true)) {
            return back()->with('error', 'Format ekspor tidak dikenali.');
        }

        $preset = $this->preset($request);
        $registrations = $this->reportQuery($request)->orderBy('registration_number')->get();

        $this->activity->log(
            ActivityLogger::REPORT_EXPORTED,
            sprintf('Laporan "%s" diekspor sebagai %s (%d baris).', self::PRESETS[$preset], strtoupper($format), $registrations->count())
        );

        $filename = sprintf(
            'Laporan-%s-%s',
            str_replace(' ', '-', self::PRESETS[$preset]),
            now()->format('Ymd-His')
        );

        return match ($format) {
            'pdf' => $this->exportPdf($registrations, $preset, $filename),
            'csv' => $this->exportCsv($registrations, $filename),
            'xlsx' => $this->exportXlsx($registrations, $filename),
        };
    }

    /**
     * @return Builder<Registration>
     */
    private function reportQuery(Request $request): Builder
    {
        $query = $this->filteredRegistrations($request)
            ->with(['applicant.address', 'applicant.parentGuardians', 'reregistration']);

        $query = match ($this->preset($request)) {
            'verifikasi' => $query->whereIn('registration_status', [
                RegistrationStatus::Submitted->value,
                RegistrationStatus::UnderReview->value,
                RegistrationStatus::RevisionRequired->value,
                RegistrationStatus::Verified->value,
            ]),
            'diterima' => $query->where('selection_status', SelectionStatus::Accepted),
            'cadangan' => $query->where('selection_status', SelectionStatus::Reserve),
            'ditolak' => $query->where('selection_status', SelectionStatus::Rejected),
            'daftar-ulang' => $query->whereNot('reregistration_status', ReregistrationStatus::NotRequired),
            default => $query,
        };

        // Period filter, applied on top of any preset.
        return $query
            ->when($request->filled('from'), fn (Builder $q) => $q->whereDate('submitted_at', '>=', $request->date('from')))
            ->when($request->filled('to'), fn (Builder $q) => $q->whereDate('submitted_at', '<=', $request->date('to')));
    }

    private function preset(Request $request): string
    {
        $preset = (string) $request->query('preset', 'semua');

        return array_key_exists($preset, self::PRESETS) ? $preset : 'semua';
    }

    /**
     * Column headings shared by every tabular export.
     *
     * @return array<int, string>
     */
    private function headings(): array
    {
        return [
            'No', 'Nomor Pendaftaran', 'Nama Lengkap', 'NISN', 'Jenis Kelamin',
            'Tempat Lahir', 'Tanggal Lahir', 'Asal Sekolah', 'Jalur', 'Program',
            'Gelombang', 'Tahun Ajaran', 'Nomor HP', 'Alamat',
            'Status Pendaftaran', 'Status Seleksi', 'Status Daftar Ulang', 'Tanggal Daftar',
        ];
    }

    /**
     * @param  Collection<int, Registration>  $registrations
     * @return array<int, array<int, string>>
     */
    private function rows(Collection $registrations): array
    {
        return $registrations->values()->map(function (Registration $registration, int $index): array {
            $applicant = $registration->applicant;

            return [
                (string) ($index + 1),
                (string) $registration->registration_number,
                (string) $applicant->full_name,
                (string) ($applicant->nisn ?? '-'),
                $applicant->genderLabel(),
                (string) ($applicant->birth_place ?? '-'),
                $applicant->birth_date?->format('d/m/Y') ?? '-',
                (string) ($applicant->previousSchool?->school_name ?? '-'),
                (string) ($registration->admissionTrack?->name ?? '-'),
                (string) ($registration->program?->name ?? '-'),
                (string) ($registration->wave?->name ?? '-'),
                (string) ($registration->academicYear?->name ?? '-'),
                (string) ($applicant->phone ?? '-'),
                $applicant->address?->fullAddress() ?? '-',
                $registration->registration_status->label(),
                // Unpublished results must not leak into an exported file.
                $registration->hasPublishedResult() ? $registration->selection_status->label() : 'Belum diumumkan',
                $registration->reregistration_status->label(),
                $registration->submitted_at?->format('d/m/Y H:i') ?? '-',
            ];
        })->all();
    }

    /**
     * @param  Collection<int, Registration>  $registrations
     */
    private function exportPdf(Collection $registrations, string $preset, string $filename): Response
    {
        $pdf = Pdf::loadView('pdf.report', [
            'registrations' => $registrations,
            'title' => self::PRESETS[$preset],
            'settings' => $this->settings,
            'generatedAt' => now(),
        ])->setPaper('a4', 'landscape');

        return $pdf->download($filename.'.pdf');
    }

    /**
     * @param  Collection<int, Registration>  $registrations
     */
    private function exportCsv(Collection $registrations, string $filename): StreamedResponse
    {
        $headings = $this->headings();
        $rows = $this->rows($registrations);

        return response()->streamDownload(function () use ($headings, $rows): void {
            $handle = fopen('php://output', 'wb');

            // BOM so Excel opens the file as UTF-8.
            fwrite($handle, "\xEF\xBB\xBF");

            fputcsv($handle, $headings, ';');

            foreach ($rows as $row) {
                fputcsv($handle, $row, ';');
            }

            fclose($handle);
        }, $filename.'.csv', ['Content-Type' => 'text/csv; charset=UTF-8']);
    }

    /**
     * @param  Collection<int, Registration>  $registrations
     */
    private function exportXlsx(Collection $registrations, string $filename): StreamedResponse
    {
        $headings = $this->headings();
        $rows = $this->rows($registrations);

        return response()->streamDownload(function () use ($headings, $rows): void {
            $writer = new XlsxWriter;
            $writer->openToFile('php://output');

            $writer->addRow(Row::fromValuesWithStyle($headings, (new Style)->withFontBold(true)));

            foreach ($rows as $row) {
                $writer->addRow(Row::fromValues($row));
            }

            $writer->close();
        }, $filename.'.xlsx', [
            'Content-Type' => 'application/vnd.openxmlformats-officedocument.spreadsheetml.sheet',
        ]);
    }
}
