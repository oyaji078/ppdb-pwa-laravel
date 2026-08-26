@extends('mail.layout', ['title' => 'Uji Coba Pengiriman Email'])

@section('content')
    <p style="margin:0 0 12px;">Konfigurasi email berhasil.</p>
    <p style="margin:0;font-size:13px;color:#475569;">
        Email ini dikirim dari halaman Pengaturan untuk memastikan kredensial SMTP
        {{ $schoolName }} sudah benar. Bila Anda menerimanya, pemberitahuan status
        pendaftaran dan verifikasi email sudah siap digunakan.
    </p>
@endsection
