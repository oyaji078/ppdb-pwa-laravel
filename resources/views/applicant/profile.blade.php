@extends('layouts.applicant')

@section('title', 'Profil')

@section('content')
    <div class="space-y-5">
        <x-review-section title="Akun Pendaftar">
            <x-review-item label="Nomor Pendaftaran" :value="$registration->registration_number" />
            <x-review-item label="Nama Pendaftar" :value="$applicant->full_name" />
            <x-review-item label="Tahun Ajaran" :value="$registration->academicYear->name" />
            <x-review-item label="Gelombang" :value="$registration->wave->name" />
            <x-review-item label="Status Pendaftaran" :value="$registration->registration_status->label()" />
            <x-review-item label="Terdaftar Sejak" :value="$registration->submitted_at?->translatedFormat('d F Y, H:i').' WITA'" />
        </x-review-section>

        <section class="card p-6">
            <h2 class="font-semibold text-slate-900">Kode Akses</h2>
            <p class="prose-content mt-2">
                Kode akses Anda disimpan dalam bentuk terenkripsi dan tidak dapat ditampilkan ulang,
                termasuk oleh panitia. Bila Anda kehilangan kode akses, hubungi panitia
                {{ $settings->admissionName() }} dengan membawa identitas diri untuk penerbitan kode akses baru.
            </p>

            @if ($settings->whatsappUrl())
                <a href="{{ $settings->whatsappUrl() }}" target="_blank" rel="noopener" class="btn-secondary btn-sm mt-4">
                    <x-icon name="message-circle" class="h-3.5 w-3.5" />
                    Hubungi Panitia
                </a>
            @endif
        </section>

        <section class="card p-6">
            <h2 class="font-semibold text-slate-900">Bukti Pendaftaran</h2>
            <p class="prose-content mt-2">Unduh ulang bukti pendaftaran Anda kapan saja.</p>

            <a href="{{ route('applicant.receipt') }}" class="btn-primary mt-4">
                <x-icon name="download" class="h-4 w-4" />
                Unduh Bukti Pendaftaran
            </a>
        </section>

        <section class="card p-6">
            <h2 class="font-semibold text-slate-900">Keluar dari Portal</h2>
            <p class="prose-content mt-2">
                Pastikan Anda keluar setelah selesai, terutama bila menggunakan perangkat bersama.
            </p>

            <form method="POST" action="{{ route('logout') }}" class="mt-4">
                @csrf
                <button type="submit" class="btn-danger">
                    <x-icon name="log-out" class="h-4 w-4" />
                    Keluar
                </button>
            </form>
        </section>
    </div>
@endsection
