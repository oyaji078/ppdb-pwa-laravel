@php $stepKey = 'alamat'; @endphp

@extends('layouts.wizard')

@section('title', 'Pendaftaran - Alamat')

@section('wizard')
    <form method="POST" action="{{ route('registration.address.store') }}" class="card p-6 sm:p-8">
        @csrf

        <h2 class="text-lg font-bold text-slate-900">Alamat Tempat Tinggal</h2>
        <p class="mt-1 text-sm text-slate-600">Isi alamat tempat tinggal calon peserta didik saat ini.</p>

        <div class="mt-6 grid gap-5 sm:grid-cols-2">
            {{-- autocomplete lets the browser fill a saved address in one tap. --}}
            <x-form.input name="province" label="Provinsi" :value="$address?->province" required
                          autocomplete="address-level1" placeholder="Nusa Tenggara Barat" />

            <x-form.input name="regency" label="Kabupaten/Kota" :value="$address?->regency" required
                          autocomplete="address-level2" placeholder="Lombok Timur" />

            <x-form.input name="district" label="Kecamatan" :value="$address?->district" required
                          autocomplete="address-level3" />

            <x-form.input name="village" label="Desa/Kelurahan" :value="$address?->village" required
                          autocomplete="address-level4" />

            {{-- 4..10 digits, matching the digits_between rule on the request. --}}
            <x-form.input name="postal_code" label="Kode Pos" :value="$address?->postal_code"
                          inputmode="numeric" pattern="[0-9]{4,10}" maxlength="10"
                          autocomplete="postal-code" placeholder="83651" />

            <div class="sm:col-span-2">
                <x-form.textarea name="address" label="Alamat Lengkap" :value="$address?->address" required rows="3"
                                 autocomplete="street-address"
                                 placeholder="Nama jalan, dusun, RT/RW, nomor rumah"
                                 hint="Contoh: Jl. Pendidikan No. 12, Dusun Peneda, RT 003 / RW 001" />
            </div>
        </div>

        <div class="mt-8 flex flex-col gap-3 border-t border-slate-100 pt-6 sm:flex-row sm:justify-between">
            <a href="{{ route('registration.biodata') }}" class="btn-secondary">
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
