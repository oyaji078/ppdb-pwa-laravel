@php $stepKey = 'asal-sekolah'; @endphp

@extends('layouts.wizard')

@section('title', 'Pendaftaran - Asal Sekolah')

@section('wizard')
    <form method="POST" action="{{ route('registration.previous-school.store') }}" class="card p-6 sm:p-8">
        @csrf

        <h2 class="text-lg font-bold text-slate-900">Asal Sekolah</h2>
        <p class="mt-1 text-sm text-slate-600">Data sekolah tempat calon peserta didik menyelesaikan jenjang sebelumnya.</p>

        <div class="mt-6 grid gap-5 sm:grid-cols-2">
            <div class="sm:col-span-2">
                <x-form.input name="school_name" label="Nama Sekolah Asal" :value="$school?->school_name" required
                              placeholder="Contoh: SMP Negeri 1 Peneda" />
            </div>

            <x-form.select name="school_type" label="Jenis Sekolah" :value="$school?->school_type" required
                           :options="config('ppdb.reference.school_types')" />

            <x-form.select name="school_status" label="Status Sekolah" :value="$school?->school_status" required
                           :options="config('ppdb.reference.school_statuses')" />

            <x-form.input name="npsn" label="NPSN" :value="$school?->npsn"
                          inputmode="numeric" maxlength="8" hint="8 angka. Kosongkan bila tidak diketahui." />

            <x-form.input name="nsm" label="NSM" :value="$school?->nsm" maxlength="20"
                          hint="Diisi bila sekolah asal berupa madrasah." />

            <x-form.input name="province" label="Provinsi Sekolah" :value="$school?->province" />

            <x-form.input name="regency" label="Kabupaten/Kota Sekolah" :value="$school?->regency" />

            <x-form.input name="graduation_year" type="number" label="Tahun Lulus" required
                          :value="$school?->graduation_year ?? now()->year"
                          min="{{ now()->year - 15 }}" max="{{ now()->year + 1 }}" />
        </div>

        <div class="mt-8 flex flex-col gap-3 border-t border-slate-100 pt-6 sm:flex-row sm:justify-between">
            <a href="{{ route('registration.parents') }}" class="btn-secondary">
                <x-icon name="arrow-left" class="h-4 w-4" />
                Kembali
            </a>
            <button type="submit" class="btn-primary">
                Simpan &amp; Lanjut
                <x-icon name="arrow-right" class="h-4 w-4" />
            </button>
        </div>
    </form>
@endsection
