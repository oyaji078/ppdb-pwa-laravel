@extends('layouts.applicant')

@section('title', 'Program Pilihan')

@section('content')
    <div class="space-y-5">
        <x-review-section title="Pilihan Pendaftaran">
            <x-review-item label="Tahun Ajaran" :value="$registration->academicYear->name" />
            <x-review-item label="Gelombang" :value="$registration->wave->name" />
            <x-review-item label="Jalur Pendaftaran" :value="$registration->admissionTrack->name" />
            <x-review-item label="Program Pilihan" :value="$registration->program?->name" />
        </x-review-section>

        @if ($registration->program?->description)
            <section class="card p-6">
                <h2 class="font-semibold text-slate-900">Tentang {{ $registration->program->name }}</h2>
                <p class="prose-content mt-2">{{ $registration->program->description }}</p>
            </section>
        @endif

        @if ($registration->admissionTrack->description)
            <section class="card p-6">
                <h2 class="font-semibold text-slate-900">Jalur {{ $registration->admissionTrack->name }}</h2>
                <p class="prose-content mt-2">{{ $registration->admissionTrack->description }}</p>
            </section>
        @endif
    </div>
@endsection
