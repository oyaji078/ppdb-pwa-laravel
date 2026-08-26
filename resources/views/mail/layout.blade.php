{{-- Shared shell for applicant e-mails. Table layout and inline styles: mail
     clients cannot be trusted with flexbox or external stylesheets. --}}
<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>{{ $title ?? $schoolName }}</title>
</head>
<body style="margin:0;padding:0;background-color:#f1f5f9;font-family:Arial,Helvetica,sans-serif;color:#0f172a;">
    <table role="presentation" width="100%" cellpadding="0" cellspacing="0" style="background-color:#f1f5f9;padding:24px 12px;">
        <tr>
            <td align="center">
                <table role="presentation" width="100%" cellpadding="0" cellspacing="0" style="max-width:560px;background-color:#ffffff;border-radius:12px;overflow:hidden;border:1px solid #e2e8f0;">
                    <tr>
                        <td style="background-color:#0f172a;padding:20px 24px;">
                            <div style="color:#ffffff;font-size:16px;font-weight:bold;">{{ $schoolName }}</div>
                            <div style="color:#94a3b8;font-size:13px;">{{ $admissionName }}</div>
                        </td>
                    </tr>
                    <tr>
                        <td style="padding:24px;font-size:14px;line-height:1.6;">
                            @yield('content')
                        </td>
                    </tr>
                    <tr>
                        <td style="background-color:#f8fafc;padding:16px 24px;font-size:12px;color:#64748b;border-top:1px solid #e2e8f0;">
                            Email ini dikirim otomatis oleh sistem {{ $admissionName }} {{ $schoolName }}.
                            Mohon tidak membalas email ini.
                        </td>
                    </tr>
                </table>
            </td>
        </tr>
    </table>
</body>
</html>
