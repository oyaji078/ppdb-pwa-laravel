@extends('mail.layout', ['title' => $title])

@section('content')
    <p style="margin:0 0 12px;">Halo <strong>{{ $applicant->full_name }}</strong>,</p>

    <p style="margin:0 0 8px;font-size:16px;font-weight:bold;">{{ $title }}</p>
    <p style="margin:0 0 16px;">{{ $body }}</p>

    <table role="presentation" cellpadding="0" cellspacing="0" style="margin:0 0 20px;background-color:#f8fafc;border:1px solid #e2e8f0;border-radius:8px;">
        <tr>
            <td style="padding:12px 16px;font-size:13px;">
                <span style="color:#64748b;">Nomor Pendaftaran</span><br>
                <strong style="letter-spacing:1px;font-size:15px;">{{ $registration->registration_number }}</strong>
            </td>
        </tr>
    </table>

    <p style="margin:0 0 20px;text-align:center;">
        <a href="{{ $portalUrl }}"
           style="display:inline-block;background-color:#0f172a;color:#ffffff;text-decoration:none;padding:12px 24px;border-radius:8px;font-weight:bold;">
            Buka Portal Pendaftar
        </a>
    </p>

    <p style="margin:0;font-size:13px;color:#475569;">
        Masuk memakai nomor pendaftaran dan kode akses Anda untuk melihat detailnya.
    </p>
@endsection
