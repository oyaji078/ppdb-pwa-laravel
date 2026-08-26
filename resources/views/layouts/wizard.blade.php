@extends('layouts.public')

@section('hero')
    <x-page-header :title="'Formulir Pendaftaran '.$settings->admissionName()"
                   subtitle="Isi setiap langkah dengan data yang benar. Isian Anda tersimpan otomatis pada setiap langkah."
                   :breadcrumb="[$settings->admissionName() => route('ppdb.index'), 'Pendaftaran' => null]" />
@endsection

@section('content')
    <div class="mx-auto max-w-4xl space-y-6 px-4 pb-4 sm:px-6 lg:px-8">
        <x-wizard-steps :current="$stepKey" :registration="$registration ?? null" />

        @isset($registration)
            <x-registration-identity-bar :registration="$registration" />
        @endisset

        @yield('wizard')
    </div>
@endsection
