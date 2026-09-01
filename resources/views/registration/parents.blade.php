@php $stepKey = 'orang-tua'; @endphp

@extends('layouts.wizard')

@section('title', 'Pendaftaran - Orang Tua/Wali')

@section('wizard')
    <form method="POST" action="{{ route('registration.parents.store') }}" class="card p-6 sm:p-8" data-draft="orang-tua">
        @csrf

        <x-draft-note />

        <h2 class="text-lg font-bold text-slate-900">Data Orang Tua / Wali</h2>

        {{-- Spelled out because the rules here are conditional: what is required
             depends on the "masih hidup" boxes, and only one phone number is
             needed across all three blocks. --}}
        <div class="mt-2 rounded-lg bg-slate-50 p-4 text-sm text-slate-700 ring-1 ring-slate-200">
            <p class="font-medium text-slate-900">Yang wajib diisi hanya ini:</p>
            <ul class="mt-2 list-disc space-y-1 pl-5">
                <li>Nama ayah dan nama ibu, selama keduanya masih hidup.</li>
                <li>Satu nomor HP saja, boleh milik ayah, ibu, atau wali.</li>
            </ul>
            <p class="mt-2">
                Isian bertanda <span class="font-semibold text-rose-600">*</span> adalah yang wajib.
                Selain itu boleh dikosongkan, termasuk seluruh bagian Data Wali bila tidak ada wali.
            </p>
        </div>

        <div class="mt-6 space-y-6">
            @include('partials.parent-fieldset', ['prefix' => 'father', 'legend' => 'Data Ayah', 'model' => $father])
            @include('partials.parent-fieldset', ['prefix' => 'mother', 'legend' => 'Data Ibu', 'model' => $mother])
            @include('partials.parent-fieldset', ['prefix' => 'guardian', 'legend' => 'Data Wali (opsional)', 'model' => $guardian])
        </div>

        <div class="mt-8 flex flex-col gap-3 border-t border-slate-100 pt-6 sm:flex-row sm:justify-between">
            <a href="{{ route('registration.address') }}" class="btn-secondary">
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
