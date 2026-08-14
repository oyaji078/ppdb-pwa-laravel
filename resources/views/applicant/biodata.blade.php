@extends('layouts.applicant')

@section('title', 'Biodata')

@section('content')
    <div class="space-y-5">
        <x-alert type="info">
            Data ini dikirim saat pendaftaran dan tidak dapat diubah sendiri.
            Bila terdapat kekeliruan, hubungi panitia {{ $settings->admissionName() }}.
        </x-alert>

        <x-review-section title="Data Pribadi">
            <x-review-item label="Nama Lengkap" :value="$applicant->full_name" wide />
            <x-review-item label="NISN" :value="$applicant->nisn" />
            <x-review-item label="NIK" :value="mask_identity_number($applicant->nik)" />
            <x-review-item label="Nomor Kartu Keluarga" :value="mask_identity_number($applicant->family_card_number)" />
            <x-review-item label="Jenis Kelamin" :value="$applicant->genderLabel()" />
            <x-review-item label="Tempat, Tanggal Lahir" :value="$applicant->birthInfo()" />
            <x-review-item label="Agama" :value="$applicant->religion" />
            <x-review-item label="Anak Ke" :value="$applicant->child_order" />
            <x-review-item label="Jumlah Saudara" :value="$applicant->siblings_count" />
            <x-review-item label="Nomor HP" :value="$applicant->phone" />
            <x-review-item label="Email" :value="$applicant->email" />
        </x-review-section>

        <x-review-section title="Alamat">
            <x-review-item label="Provinsi" :value="$address?->province" />
            <x-review-item label="Kabupaten/Kota" :value="$address?->regency" />
            <x-review-item label="Kecamatan" :value="$address?->district" />
            <x-review-item label="Desa/Kelurahan" :value="$address?->village" />
            <x-review-item label="Kode Pos" :value="$address?->postal_code" />
            <x-review-item label="Alamat Lengkap" :value="$address?->address" wide />
        </x-review-section>
    </div>
@endsection
