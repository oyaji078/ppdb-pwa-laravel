@php $stepKey = 'biodata'; @endphp

@extends('layouts.wizard')

@section('title', 'Pendaftaran - Biodata')

@section('wizard')
    <form method="POST" action="{{ route('registration.biodata.store') }}" class="card p-6 sm:p-8">
        @csrf

        <h2 class="text-lg font-bold text-slate-900">Biodata Calon Peserta Didik</h2>
        <p class="mt-1 text-sm text-slate-600">Isi sesuai dokumen resmi (Kartu Keluarga dan Akta Kelahiran).</p>

        <div class="mt-6 grid gap-5 sm:grid-cols-2">
            <x-form.input name="nisn" label="NISN" :value="$applicant->nisn" required
                          inputmode="numeric" maxlength="10" hint="10 angka, tertera pada rapor atau surat keterangan sekolah asal." />

            <x-form.input name="nik" label="NIK" :value="$applicant->nik" required
                          inputmode="numeric" maxlength="16" hint="16 angka sesuai Kartu Keluarga." />

            <x-form.input name="full_name" label="Nama Lengkap" :value="$applicant->full_name" required
                          class="form-input sm:col-span-2" placeholder="Tulis sesuai akta kelahiran" />

            <x-form.input name="family_card_number" label="Nomor Kartu Keluarga" :value="$applicant->family_card_number"
                          inputmode="numeric" maxlength="16" />

            <x-form.select name="gender" label="Jenis Kelamin" :value="$applicant->gender" required
                           :options="config('ppdb.reference.genders')" />

            <x-form.input name="birth_place" label="Tempat Lahir" :value="$applicant->birth_place" required />

            <x-form.input name="birth_date" type="date" label="Tanggal Lahir" required
                          :value="$applicant->birth_date?->toDateString()" max="{{ now()->toDateString() }}" />

            <x-form.select name="religion" label="Agama" :value="$applicant->religion" required
                           :options="collect(config('ppdb.reference.religions'))->mapWithKeys(fn ($r) => [$r => $r])->all()" />

            <x-form.input name="child_order" type="number" label="Anak Ke-" :value="$applicant->child_order" min="1" max="20" />

            <x-form.input name="siblings_count" type="number" label="Jumlah Saudara Kandung" :value="$applicant->siblings_count" min="0" max="20" />

            <x-form.input name="phone" label="Nomor HP Aktif" :value="$applicant->phone" required
                          inputmode="tel" placeholder="08xxxxxxxxxx" hint="Digunakan panitia untuk menghubungi Anda." />

            <x-form.input name="email" type="email" label="Email" :value="$applicant->email"
                          placeholder="nama@email.com" hint="Opsional." />
        </div>

        <div class="mt-8 flex flex-col gap-3 border-t border-slate-100 pt-6 sm:flex-row sm:justify-between">
            <a href="{{ route('registration.start') }}" class="btn-secondary">
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
