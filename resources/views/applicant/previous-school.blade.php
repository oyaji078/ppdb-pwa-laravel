@extends('layouts.applicant')

@section('title', 'Asal Sekolah')

@section('content')
    @if ($school === null)
        <div class="card">
            <x-empty-state icon="school" title="Data asal sekolah belum tersedia"
                           description="Hubungi panitia bila data ini seharusnya sudah terisi." />
        </div>
    @else
        <x-review-section title="Sekolah Asal">
            <x-review-item label="Nama Sekolah" :value="$school->school_name" wide />
            <x-review-item label="Jenis Sekolah" :value="$school->school_type" />
            <x-review-item label="Status Sekolah" :value="$school->school_status" />
            <x-review-item label="NPSN" :value="$school->npsn" />
            <x-review-item label="NSM" :value="$school->nsm" />
            <x-review-item label="Provinsi" :value="$school->province" />
            <x-review-item label="Kabupaten/Kota" :value="$school->regency" />
            <x-review-item label="Tahun Lulus" :value="$school->graduation_year" />
        </x-review-section>
    @endif
@endsection
