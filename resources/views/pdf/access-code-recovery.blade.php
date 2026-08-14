{{-- One-time recovery sheet handed to an applicant after an admin reset. --}}
<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="utf-8">
    <title>Kode Akses Baru {{ $registration->registration_number }}</title>
    <style>
        @page { margin: 20mm 18mm; }

        body { font-family: DejaVu Sans, sans-serif; font-size: 10.5px; color: #1e293b; line-height: 1.55; }
        table { width: 100%; border-collapse: collapse; }
        td { vertical-align: top; }

        .logo img { width: 56px; height: 56px; }
        .school-name { font-size: 15px; font-weight: bold; color: #1e3a8a; }
        .school-meta { font-size: 9px; color: #475569; }
        .rule { border-bottom: 2px solid #1e3a8a; margin: 6px 0 14px; }

        .doc-title { text-align: center; font-size: 13px; font-weight: bold; text-transform: uppercase; letter-spacing: 0.5px; }

        .credential {
            border: 1.5px dashed #d97706;
            background: #fffbeb;
            padding: 12px 14px;
            margin: 16px 0;
            text-align: center;
        }
        .credential-label { font-size: 8px; text-transform: uppercase; letter-spacing: 0.6px; color: #92400e; font-weight: bold; }
        .credential-value { font-size: 26px; font-weight: bold; letter-spacing: 4px; color: #92400e; }

        table.data td { padding: 3px 0; }
        table.data td.label { width: 34%; color: #475569; }
        table.data td.sep { width: 10px; }
        table.data td.value { font-weight: bold; }

        .note { border: 1px solid #fcd34d; background: #fffbeb; padding: 9px 11px; font-size: 9px; color: #78350f; margin-top: 14px; }
        .note strong { display: block; margin-bottom: 3px; }

        .sign { margin-top: 26px; font-size: 9.5px; }
        .sign-line { margin-top: 46px; border-top: 1px solid #94a3b8; width: 190px; }

        .footer { margin-top: 20px; border-top: 1px solid #cbd5e1; padding-top: 6px; font-size: 7.5px; color: #64748b; text-align: center; }
    </style>
</head>
<body>

<table>
    <tr>
        @if ($logoData)
            <td class="logo" style="width: 62px;"><img src="{{ $logoData }}" alt=""></td>
        @endif
        <td>
            <div class="school-name">{{ $settings->schoolName() }}</div>
            <div class="school-meta">{{ $settings->get('school_address') }}</div>
        </td>
    </tr>
</table>

<div class="rule"></div>

<div class="doc-title">Penerbitan Ulang Kode Akses</div>

<table class="data" style="margin-top: 14px;">
    <tr><td class="label">Nomor Pendaftaran</td><td class="sep">:</td><td class="value">{{ $registration->registration_number }}</td></tr>
    <tr><td class="label">Nama Pendaftar</td><td class="sep">:</td><td class="value">{{ $applicant->full_name }}</td></tr>
    <tr><td class="label">NISN</td><td class="sep">:</td><td class="value">{{ $applicant->nisn ?: '-' }}</td></tr>
    <tr><td class="label">Tahun Ajaran</td><td class="sep">:</td><td class="value">{{ $registration->academicYear->name }}</td></tr>
    <tr><td class="label">Gelombang</td><td class="sep">:</td><td class="value">{{ $registration->wave->name }}</td></tr>
    <tr><td class="label">Diterbitkan</td><td class="sep">:</td><td class="value">{{ now()->translatedFormat('l, d F Y H:i') }} WITA</td></tr>
</table>

<div class="credential">
    <div class="credential-label">Kode Akses Baru</div>
    <div class="credential-value">{{ $accessCode }}</div>
</div>

<div class="note">
    <strong>Penting</strong>
    Kode akses lama sudah tidak berlaku dan seluruh sesi sebelumnya telah dihentikan.
    Gunakan kode di atas bersama nomor pendaftaran untuk masuk ke portal pendaftar melalui
    {{ route('status.form') }}. Dokumen ini hanya dapat diunduh satu kali dan tidak disimpan oleh sistem.
    Simpan baik-baik serta jangan membagikannya kepada orang lain.
</div>

<table class="sign">
    <tr>
        <td style="width: 55%;">
            Diterima oleh pendaftar/orang tua,
            <div class="sign-line"></div>
            Nama &amp; tanda tangan
        </td>
        <td>
            Panitia {{ $settings->admissionName() }},
            <div class="sign-line"></div>
            Nama &amp; tanda tangan
        </td>
    </tr>
</table>

<div class="footer">
    Dokumen ini dihasilkan otomatis oleh sistem {{ $settings->admissionName() }} {{ $settings->schoolName() }}.
</div>

</body>
</html>
