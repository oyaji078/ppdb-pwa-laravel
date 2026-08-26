@extends('mail.layout', ['title' => 'Verifikasi Email Pendaftaran'])

@section('content')
    <p style="margin:0 0 12px;">Halo <strong>{{ $applicant->full_name }}</strong>,</p>

    <p style="margin:0 0 12px;">
        Akun pendaftaran Anda sudah dibuat dengan nomor pendaftaran
        <strong style="letter-spacing:1px;">{{ $registration->registration_number }}</strong>.
        Silakan konfirmasi alamat email ini agar Anda dapat menerima pemberitahuan
        status pendaftaran.
    </p>

    <p style="margin:0 0 20px;text-align:center;">
        <a href="{{ $verificationUrl }}"
           style="display:inline-block;background-color:#0f172a;color:#ffffff;text-decoration:none;padding:12px 24px;border-radius:8px;font-weight:bold;">
            Verifikasi Email
        </a>
    </p>

    <p style="margin:0 0 12px;font-size:13px;color:#475569;">
        Bila tombol di atas tidak berfungsi, salin dan tempel tautan berikut ke browser Anda:
    </p>
    <p style="margin:0 0 16px;font-size:12px;word-break:break-all;color:#2563eb;">{{ $verificationUrl }}</p>

    <p style="margin:0;font-size:13px;color:#475569;">
        Tautan ini berlaku 7 hari. Bila Anda tidak merasa mendaftar, abaikan email ini.
    </p>
@endsection
