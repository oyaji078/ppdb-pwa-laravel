@extends('layouts.admin')

@section('title', 'Verifikasi Berkas')
@section('subheading', 'Pendaftar yang menunggu pemeriksaan berkas.')

@section('content')
    <div class="space-y-5">
        <x-admin.filter-bar :action="route('admin.verification.index')"
                            :academic-years="$academicYears" :waves="$waves" :tracks="$tracks" :programs="$programs"
                            :registration-statuses="$registrationStatuses" />

        <div class="card overflow-hidden">
            <div class="border-b border-slate-100 px-5 py-3.5">
                <p class="text-sm text-slate-600">
                    <span class="font-semibold text-slate-900">{{ number_format($registrations->total(), 0, ',', '.') }}</span>
                    pendaftar dalam antrean verifikasi
                </p>
            </div>

            @if ($registrations->isEmpty())
                <x-empty-state icon="file-check" title="Tidak ada berkas yang menunggu"
                               description="Semua pendaftar pada filter ini sudah selesai diverifikasi." />
            @else
                <div class="table-wrapper">
                    <table class="data-table">
                        <thead>
                            <tr>
                                <th scope="col">Nomor Pendaftaran</th>
                                <th scope="col">Nama</th>
                                <th scope="col">Jalur</th>
                                <th scope="col">Berkas</th>
                                <th scope="col">Menunggu</th>
                                <th scope="col">Status</th>
                                <th scope="col"><span class="sr-only">Aksi</span></th>
                            </tr>
                        </thead>
                        <tbody>
                            @foreach ($registrations as $registration)
                                <tr>
                                    <td class="font-mono text-sm font-semibold text-slate-900">
                                        {{ $registration->registration_number }}
                                    </td>
                                    <td>
                                        <span class="font-medium text-slate-900">{{ $registration->applicant->full_name }}</span>
                                        <span class="block text-xs text-slate-500">
                                            Dikirim {{ $registration->submitted_at?->translatedFormat('d M Y') }}
                                        </span>
                                    </td>
                                    <td>{{ $registration->admissionTrack->name }}</td>
                                    <td>
                                        <span class="font-medium text-slate-900">{{ $registration->verified_documents_count }}</span>
                                        <span class="text-slate-500">/ {{ $registration->documents_count }} terverifikasi</span>
                                    </td>
                                    <td>
                                        @if ($registration->pending_documents_count > 0)
                                            <x-badge class="bg-blue-50 text-blue-700 ring-blue-200">
                                                {{ $registration->pending_documents_count }} berkas
                                            </x-badge>
                                        @else
                                            <span class="text-xs text-slate-500">-</span>
                                        @endif
                                    </td>
                                    <td>
                                        <x-badge :class="$registration->registration_status->badge()">
                                            {{ $registration->registration_status->label() }}
                                        </x-badge>
                                    </td>
                                    <td class="text-right">
                                        <a href="{{ route('admin.verification.show', $registration) }}" class="btn-primary btn-sm">
                                            <x-icon name="file-check" class="h-3.5 w-3.5" />
                                            Periksa
                                        </a>
                                    </td>
                                </tr>
                            @endforeach
                        </tbody>
                    </table>
                </div>

                <div class="border-t border-slate-100 px-5 py-4">{{ $registrations->links() }}</div>
            @endif
        </div>
    </div>
@endsection
