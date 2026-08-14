@php $stepKey = 'orang-tua'; @endphp

@extends('layouts.wizard')

@section('title', 'Pendaftaran - Orang Tua/Wali')

@section('wizard')
    <form method="POST" action="{{ route('registration.parents.store') }}" class="card p-6 sm:p-8">
        @csrf

        <h2 class="text-lg font-bold text-slate-900">Data Orang Tua / Wali</h2>
        <p class="mt-1 text-sm text-slate-600">
            Pastikan minimal satu nomor HP orang tua dapat dihubungi panitia.
        </p>

        <div class="mt-6 space-y-6">
            @include('partials.parent-fieldset', ['prefix' => 'father', 'legend' => 'Data Ayah', 'model' => $father, 'required' => true])
            @include('partials.parent-fieldset', ['prefix' => 'mother', 'legend' => 'Data Ibu', 'model' => $mother, 'required' => true])
            @include('partials.parent-fieldset', ['prefix' => 'guardian', 'legend' => 'Data Wali (opsional)', 'model' => $guardian, 'required' => false])
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
