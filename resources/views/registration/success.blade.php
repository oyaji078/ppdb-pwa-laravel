@extends('layouts.public')

@section('title', 'Pendaftaran Berhasil')

@section('content')
    <div class="mx-auto max-w-2xl px-4 pb-8 sm:px-6 lg:px-8">
        <div class="card overflow-hidden">
            <div class="bg-emerald-50 px-6 py-8 text-center sm:px-8">
                <span class="mx-auto flex h-16 w-16 items-center justify-center rounded-full bg-emerald-100 text-emerald-600">
                    <x-icon name="circle-check" class="h-9 w-9" />
                </span>
                <h1 class="mt-4 text-xl font-bold text-emerald-900 sm:text-2xl">Pendaftaran Berhasil</h1>
                <p class="mt-1.5 text-sm text-emerald-800">
                    Pendaftaran atas nama <strong>{{ $registration->applicant->full_name }}</strong> telah kami terima.
                </p>
            </div>

            <div class="space-y-5 p-6 sm:p-8">
                <div class="rounded-lg border-2 border-dashed border-brand-200 bg-brand-50/50 p-5 text-center">
                    <p class="text-xs font-semibold tracking-wide text-brand-700 uppercase">Nomor Pendaftaran</p>
                    <p class="mt-1.5 font-mono text-3xl font-bold tracking-wider text-brand-900 sm:text-4xl">
                        {{ $registration->registration_number }}
                    </p>
                </div>

                <div class="flex items-center justify-between gap-3 rounded-lg bg-slate-50 p-4">
                    <span class="text-sm text-slate-600">Status Pendaftaran</span>
                    <x-badge :class="$registration->registration_status->badge()">
                        {{ Str::upper($registration->registration_status->label()) }}
                    </x-badge>
                </div>

                <x-alert type="info" title="Cara masuk kembali">
                    Gunakan <strong>nomor pendaftaran</strong> di atas dan <strong>kode akses</strong> yang
                    Anda buat sendiri saat mendaftar. Demi keamanan, kode akses tidak dicetak pada bukti
                    pendaftaran. Bila lupa, hubungi panitia untuk penerbitan ulang.
                </x-alert>

                <div class="grid gap-3 sm:grid-cols-2">
                    <a href="{{ route('registration.success.receipt') }}" class="btn-primary">
                        <x-icon name="download" class="h-4 w-4" />
                        Unduh Bukti Pendaftaran
                    </a>
                    <a href="{{ route('status.form', ['registration' => $registration->registration_number]) }}" class="btn-secondary">
                        <x-icon name="search" class="h-4 w-4" />
                        Cek Status Pendaftaran
                    </a>
                </div>

                <div class="border-t border-slate-100 pt-5">
                    <h2 class="text-sm font-semibold text-slate-900">Langkah selanjutnya</h2>
                    <ol class="mt-3 space-y-2.5 text-sm text-slate-600">
                        <li class="flex gap-2.5">
                            <span class="flex h-5 w-5 shrink-0 items-center justify-center rounded-full bg-brand-100 text-[11px] font-bold text-brand-700">1</span>
                            Panitia memverifikasi berkas yang Anda unggah.
                        </li>
                        <li class="flex gap-2.5">
                            <span class="flex h-5 w-5 shrink-0 items-center justify-center rounded-full bg-brand-100 text-[11px] font-bold text-brand-700">2</span>
                            Bila ada berkas yang perlu diperbaiki, Anda akan melihat catatan panitia pada portal pendaftar.
                        </li>
                        <li class="flex gap-2.5">
                            <span class="flex h-5 w-5 shrink-0 items-center justify-center rounded-full bg-brand-100 text-[11px] font-bold text-brand-700">3</span>
                            Setelah terverifikasi, pendaftaran Anda masuk tahap seleksi.
                        </li>
                        <li class="flex gap-2.5">
                            <span class="flex h-5 w-5 shrink-0 items-center justify-center rounded-full bg-brand-100 text-[11px] font-bold text-brand-700">4</span>
                            Hasil seleksi diumumkan sesuai jadwal dan dapat dilihat pada portal pendaftar.
                        </li>
                    </ol>
                </div>

                <p class="text-center text-xs text-slate-500">
                    Cek status kapan saja melalui <span class="font-medium text-slate-700">{{ $statusUrl }}</span>
                </p>
            </div>
        </div>
    </div>
@endsection
