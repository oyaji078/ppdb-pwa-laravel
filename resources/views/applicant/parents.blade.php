@extends('layouts.applicant')

@section('title', 'Orang Tua/Wali')

@section('content')
    <div class="space-y-5">
        @if ($parents->isEmpty())
            <div class="card">
                <x-empty-state icon="users-round" title="Data orang tua belum tersedia"
                               description="Hubungi panitia bila data ini seharusnya sudah terisi." />
            </div>
        @else
            @foreach ($parents as $parent)
                <x-review-section :title="'Data '.$parent->relationshipLabel()">
                    <x-review-item label="Nama Lengkap" :value="$parent->name" wide />
                    <x-review-item label="NIK" :value="mask_identity_number($parent->nik)" />
                    <x-review-item label="Tanggal Lahir"
                               :value="$parent->birth_date?->translatedFormat('d F Y')" />
                    <x-review-item label="Pendidikan Terakhir" :value="$parent->education" />
                    <x-review-item label="Pekerjaan" :value="$parent->occupation" />
                    <x-review-item label="Penghasilan per Bulan" :value="$parent->monthly_income" />
                    <x-review-item label="Nomor HP" :value="$parent->phone" />
                    @if ($parent->relationship !== 'guardian')
                        <x-review-item label="Status" :value="$parent->is_alive ? 'Masih hidup' : 'Sudah meninggal'" />
                    @else
                        <x-review-item label="Alamat" :value="$parent->address" wide />
                    @endif
                </x-review-section>
            @endforeach
        @endif
    </div>
@endsection
