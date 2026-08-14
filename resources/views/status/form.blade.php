@extends('layouts.public')

@section('title', 'Cek Status Pendaftaran')

@section('hero')
    <x-page-header title="Cek Status Pendaftaran"
                   subtitle="Masuk menggunakan nomor pendaftaran dan kode akses yang Anda terima saat mendaftar."
                   :breadcrumb="['Cek Status' => null]" />
@endsection

@section('content')
    <div class="mx-auto max-w-md px-4 pb-8 sm:px-6">
        <form method="POST" action="{{ route('status.authenticate') }}" class="card p-6 sm:p-8">
            @csrf

            <div class="space-y-5">
                <x-form.input name="registration_number" label="Nomor Pendaftaran" required
                              :value="$registrationNumber" inputmode="numeric" maxlength="10"
                              autocomplete="off" autofocus
                              placeholder="Contoh: 2601000128"
                              class="form-input font-mono tracking-wider" />

                <x-form.input name="access_code" label="Kode Akses" required
                              maxlength="{{ config('ppdb.access_code.length') }}" autocomplete="off"
                              placeholder="Contoh: K7M9Q2XA"
                              hint="Huruf besar, {{ config('ppdb.access_code.length') }} karakter, tanpa spasi."
                              class="form-input font-mono tracking-widest uppercase" />
            </div>

            <button type="submit" class="btn-primary mt-6 w-full">
                <x-icon name="log-in" class="h-4 w-4" />
                Masuk ke Portal Pendaftar
            </button>

            <div class="mt-6 border-t border-slate-100 pt-5">
                <h2 class="text-sm font-semibold text-slate-900">Lupa kode akses?</h2>
                <p class="prose-content mt-1.5">
                    Kode akses disimpan dalam bentuk terenkripsi sehingga panitia pun tidak dapat membacanya.
                    Hubungi panitia {{ $settings->admissionName() }} dengan membawa identitas diri untuk penerbitan kode akses baru.
                </p>

                @if ($settings->whatsappUrl())
                    <a href="{{ $settings->whatsappUrl() }}" target="_blank" rel="noopener" class="btn-secondary btn-sm mt-3">
                        <x-icon name="message-circle" class="h-3.5 w-3.5" />
                        Hubungi Panitia
                    </a>
                @endif
            </div>
        </form>

        <p class="mt-5 text-center text-sm text-slate-500">
            Belum mendaftar?
            <a href="{{ route('registration.start') }}" class="font-semibold text-brand-600 hover:text-brand-700">Daftar sekarang</a>
        </p>
    </div>
@endsection
