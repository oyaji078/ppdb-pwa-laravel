{{-- Rendered by dompdf: plain CSS only, no external stylesheets or web fonts. --}}
<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="utf-8">
    <title>Bukti Pendaftaran {{ $registration->registration_number }}</title>
    <style>
        @page { margin: 18mm 15mm; }

        body {
            font-family: DejaVu Sans, sans-serif;
            font-size: 10px;
            color: #1e293b;
            line-height: 1.5;
        }

        table { width: 100%; border-collapse: collapse; }
        td, th { vertical-align: top; }

        .header td { padding-bottom: 6px; }
        .logo { width: 62px; }
        .logo img { width: 58px; height: 58px; }

        .school-name { font-size: 15px; font-weight: bold; color: #1e3a8a; }
        .school-meta { font-size: 9px; color: #475569; }

        .rule { border-bottom: 2px solid #1e3a8a; margin: 6px 0 12px; }

        .doc-title {
            text-align: center;
            font-size: 13px;
            font-weight: bold;
            letter-spacing: 0.5px;
            text-transform: uppercase;
        }
        .doc-subtitle { text-align: center; font-size: 9.5px; color: #475569; margin-top: 2px; }

        .credential {
            border: 1.5px dashed #1d4ed8;
            background: #eff6ff;
            padding: 9px 12px;
            margin-top: 12px;
        }
        .credential-label {
            font-size: 8px;
            text-transform: uppercase;
            letter-spacing: 0.6px;
            color: #1e40af;
            font-weight: bold;
        }
        .credential-value {
            font-size: 20px;
            font-weight: bold;
            letter-spacing: 2px;
            color: #1e3a8a;
        }
        .credential-warning { border-color: #d97706; background: #fffbeb; }
        .credential-warning .credential-label { color: #92400e; }
        .credential-warning .credential-value { color: #92400e; }

        .qr { text-align: center; }
        .qr img { width: 88px; height: 88px; }
        .qr-caption { font-size: 7.5px; color: #64748b; margin-top: 2px; }

        h2.section {
            font-size: 10px;
            font-weight: bold;
            text-transform: uppercase;
            letter-spacing: 0.5px;
            color: #1e3a8a;
            background: #f1f5f9;
            padding: 4px 8px;
            margin: 14px 0 6px;
        }

        table.data td { padding: 2.5px 0; }
        table.data td.label { width: 33%; color: #475569; }
        table.data td.sep { width: 10px; }
        table.data td.value { font-weight: bold; }

        table.docs { margin-top: 2px; }
        table.docs th {
            text-align: left;
            font-size: 8.5px;
            text-transform: uppercase;
            color: #475569;
            border-bottom: 1px solid #cbd5e1;
            padding: 4px 6px;
        }
        table.docs td { padding: 4px 6px; border-bottom: 1px solid #e2e8f0; }
        table.docs td.center { text-align: center; }

        .status-badge {
            display: inline-block;
            padding: 2px 8px;
            font-size: 9px;
            font-weight: bold;
            background: #dbeafe;
            color: #1e40af;
            border-radius: 8px;
        }

        .note {
            margin-top: 12px;
            border: 1px solid #fcd34d;
            background: #fffbeb;
            padding: 8px 10px;
            font-size: 8.5px;
            color: #78350f;
        }
        .note strong { display: block; margin-bottom: 2px; }

        .footer {
            margin-top: 16px;
            border-top: 1px solid #cbd5e1;
            padding-top: 6px;
            font-size: 7.5px;
            color: #64748b;
            text-align: center;
        }
    </style>
</head>
<body>

<table class="header">
    <tr>
        @if ($logoData)
            <td class="logo"><img src="{{ $logoData }}" alt=""></td>
        @endif
        <td>
            <div class="school-name">{{ $settings->schoolName() }}</div>
            <div class="school-meta">
                {{ $settings->get('school_address') }}<br>
                @if ($settings->get('school_phone')) Telp. {{ $settings->get('school_phone') }} @endif
                @if ($settings->get('school_email')) &middot; {{ $settings->get('school_email') }} @endif
            </div>
        </td>
    </tr>
</table>

<div class="rule"></div>

<div class="doc-title">Bukti Pendaftaran {{ $settings->admissionName() }}</div>
<div class="doc-subtitle">
    Tahun Ajaran {{ $registration->academicYear->name }} &middot; {{ $registration->wave->name }}
    &middot; Jalur {{ $registration->admissionTrack->name }}
</div>

<table style="margin-top: 6px;">
    <tr>
        <td style="width: 68%; padding-right: 10px;">
            <div class="credential">
                <div class="credential-label">Nomor Pendaftaran</div>
                <div class="credential-value">{{ $registration->registration_number }}</div>
            </div>

        </td>
        <td class="qr">
            @if ($qrData)
                <img src="{{ $qrData }}" alt="QR cek status">
            @endif
            <div class="qr-caption">Pindai untuk<br>cek status</div>
        </td>
    </tr>
</table>

<h2 class="section">Data Calon Peserta Didik</h2>
<table class="data">
    <tr><td class="label">Nama Lengkap</td><td class="sep">:</td><td class="value">{{ $applicant->full_name }}</td></tr>
    <tr><td class="label">NISN</td><td class="sep">:</td><td class="value">{{ $applicant->nisn ?: '-' }}</td></tr>
    <tr><td class="label">Tempat, Tanggal Lahir</td><td class="sep">:</td><td class="value">{{ $applicant->birthInfo() }}</td></tr>
    <tr><td class="label">Jenis Kelamin</td><td class="sep">:</td><td class="value">{{ $applicant->genderLabel() }}</td></tr>
    <tr><td class="label">Agama</td><td class="sep">:</td><td class="value">{{ $applicant->religion ?: '-' }}</td></tr>
    <tr><td class="label">Alamat</td><td class="sep">:</td><td class="value">{{ $applicant->address?->fullAddress() ?? '-' }}</td></tr>
    <tr><td class="label">Nomor HP</td><td class="sep">:</td><td class="value">{{ $applicant->phone ?: '-' }}</td></tr>
    <tr><td class="label">Asal Sekolah</td><td class="sep">:</td><td class="value">{{ $applicant->previousSchool?->school_name ?? '-' }}</td></tr>
    <tr><td class="label">Program Pilihan</td><td class="sep">:</td><td class="value">{{ $registration->program?->name ?? '-' }}</td></tr>
</table>

<h2 class="section">Data Orang Tua / Wali</h2>
<table class="data">
    @forelse ($applicant->parentGuardians as $parent)
        <tr>
            <td class="label">{{ $parent->relationshipLabel() }}</td>
            <td class="sep">:</td>
            <td class="value">
                {{ $parent->name ?: '-' }}@if ($parent->occupation) &mdash; {{ $parent->occupation }} @endif
                @if ($parent->phone) ({{ $parent->phone }}) @endif
            </td>
        </tr>
    @empty
        <tr><td class="label">Data</td><td class="sep">:</td><td class="value">-</td></tr>
    @endforelse
</table>

<h2 class="section">Checklist Berkas</h2>
<table class="docs">
    <thead>
        <tr>
            <th style="width: 26px;">No</th>
            <th>Jenis Berkas</th>
            <th style="width: 62px;">Wajib</th>
            <th style="width: 74px;">Status</th>
        </tr>
    </thead>
    <tbody>
        @php $uploaded = $registration->documents->keyBy('document_type_id'); @endphp

        @forelse ($documents as $type)
            @php $file = $uploaded[$type->id] ?? null; @endphp
            <tr>
                <td class="center">{{ $loop->iteration }}</td>
                <td>{{ $type->name }}</td>
                <td class="center">{{ $type->pivot->is_required ? 'Wajib' : 'Opsional' }}</td>
                <td class="center">{{ $file ? 'Terunggah' : 'Belum' }}</td>
            </tr>
        @empty
            <tr><td colspan="4" class="center">Tidak ada persyaratan berkas.</td></tr>
        @endforelse
    </tbody>
</table>

<h2 class="section">Status &amp; Waktu Pendaftaran</h2>
<table class="data">
    <tr>
        <td class="label">Status Awal</td>
        <td class="sep">:</td>
        <td><span class="status-badge">{{ Str::upper($registration->registration_status->label()) }}</span></td>
    </tr>
    <tr>
        <td class="label">Tanggal &amp; Jam Daftar</td>
        <td class="sep">:</td>
        <td class="value">{{ $registration->submitted_at?->translatedFormat('l, d F Y H:i') }} WITA</td>
    </tr>
    <tr>
        <td class="label">Cara Cek Status</td>
        <td class="sep">:</td>
        <td class="value">
            Buka {{ route('login') }} lalu masukkan Nomor Pendaftaran dan Kode Akses.
        </td>
    </tr>
</table>

<div class="note">
    <strong>Simpan bukti pendaftaran ini.</strong>
    Kode akses sengaja tidak dicetak di sini: bukti pendaftaran sering difotokopi dan
    dititipkan, sehingga mencantumkannya akan membuka akses ke data pendaftaran Anda.
    Gunakan kode akses yang Anda buat sendiri saat mendaftar. Bila lupa, hubungi panitia
    {{ $settings->admissionName() }} dengan membawa identitas diri untuk penerbitan ulang.
</div>

<div class="footer">
    Dokumen ini dihasilkan otomatis oleh sistem {{ $settings->admissionName() }} {{ $settings->schoolName() }}
    pada {{ now()->translatedFormat('d F Y H:i') }} WITA dan sah tanpa tanda tangan basah.
</div>

</body>
</html>
