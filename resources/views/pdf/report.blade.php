{{-- Landscape A4 report rendered by dompdf. --}}
<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="utf-8">
    <title>{{ $title }}</title>
    <style>
        @page { margin: 12mm 10mm; }

        body { font-family: DejaVu Sans, sans-serif; font-size: 8px; color: #1e293b; }

        .header { text-align: center; margin-bottom: 10px; }
        .school-name { font-size: 13px; font-weight: bold; color: #1e3a8a; }
        .school-meta { font-size: 8px; color: #475569; }
        .rule { border-bottom: 1.5px solid #1e3a8a; margin: 5px 0 8px; }
        .doc-title { font-size: 11px; font-weight: bold; text-transform: uppercase; }
        .doc-meta { font-size: 8px; color: #475569; margin-top: 2px; }

        table { width: 100%; border-collapse: collapse; }
        th {
            background: #eff6ff;
            border: 0.5px solid #94a3b8;
            padding: 4px 3px;
            font-size: 7.5px;
            text-align: left;
            text-transform: uppercase;
            color: #1e3a8a;
        }
        td { border: 0.5px solid #cbd5e1; padding: 3px; font-size: 7.5px; }
        td.center { text-align: center; }

        tr:nth-child(even) td { background: #f8fafc; }

        .empty { text-align: center; padding: 24px; color: #64748b; font-size: 9px; }

        .footer { margin-top: 10px; font-size: 7px; color: #64748b; text-align: right; }
    </style>
</head>
<body>

<div class="header">
    <div class="school-name">{{ $settings->schoolName() }}</div>
    <div class="school-meta">{{ $settings->get('school_address') }}</div>
    <div class="rule"></div>
    <div class="doc-title">Laporan {{ $title }}</div>
    <div class="doc-meta">
        {{ $settings->admissionName() }} &middot; Dicetak {{ $generatedAt->translatedFormat('d F Y H:i') }} WITA
        &middot; {{ number_format($registrations->count(), 0, ',', '.') }} data
    </div>
</div>

@if ($registrations->isEmpty())
    <p class="empty">Tidak ada data yang sesuai dengan filter laporan.</p>
@else
    <table>
        <thead>
            <tr>
                <th style="width: 3%;">No</th>
                <th style="width: 9%;">No. Pendaftaran</th>
                <th style="width: 15%;">Nama Lengkap</th>
                <th style="width: 8%;">NISN</th>
                <th style="width: 5%;">L/P</th>
                <th style="width: 15%;">Asal Sekolah</th>
                <th style="width: 8%;">Jalur</th>
                <th style="width: 11%;">Program</th>
                <th style="width: 9%;">Status</th>
                <th style="width: 9%;">Hasil Seleksi</th>
                <th style="width: 8%;">Tgl Daftar</th>
            </tr>
        </thead>
        <tbody>
            @foreach ($registrations as $registration)
                <tr>
                    <td class="center">{{ $loop->iteration }}</td>
                    <td>{{ $registration->registration_number }}</td>
                    <td>{{ $registration->applicant->full_name }}</td>
                    <td>{{ $registration->applicant->nisn ?: '-' }}</td>
                    <td class="center">{{ $registration->applicant->gender ?: '-' }}</td>
                    <td>{{ $registration->applicant->previousSchool?->school_name ?? '-' }}</td>
                    <td>{{ $registration->admissionTrack?->name ?? '-' }}</td>
                    <td>{{ $registration->program?->name ?? '-' }}</td>
                    <td>{{ $registration->registration_status->label() }}</td>
                    <td>{{ $registration->hasPublishedResult() ? $registration->selection_status->label() : 'Belum diumumkan' }}</td>
                    <td>{{ $registration->submitted_at?->format('d/m/Y') ?? '-' }}</td>
                </tr>
            @endforeach
        </tbody>
    </table>
@endif

<div class="footer">
    Dokumen dihasilkan otomatis oleh sistem {{ $settings->admissionName() }} {{ $settings->schoolName() }}.
</div>

</body>
</html>
