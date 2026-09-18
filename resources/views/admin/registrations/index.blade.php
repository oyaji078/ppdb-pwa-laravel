@extends('layouts.admin')

@section('title', 'Pendaftar')
@section('subheading', 'Seluruh akun pendaftar, termasuk formulir yang masih berupa draft.')

@section('actions')
    <a href="{{ route('admin.reports.index', request()->query()) }}" class="btn-secondary btn-sm">
        <x-icon name="chart-column" class="h-4 w-4" />
        Laporan
    </a>
@endsection

@section('content')
    <div class="space-y-5">
        <x-admin.filter-bar :action="route('admin.registrations.index')"
                            :academic-years="$academicYears" :waves="$waves" :tracks="$tracks" :programs="$programs"
                            :registration-statuses="$registrationStatuses"
                            :selection-statuses="$selectionStatuses"
                            :reregistration-statuses="$reregistrationStatuses" />

        <div class="card overflow-hidden">
            <div class="flex flex-wrap items-center justify-between gap-3 border-b border-slate-100 px-5 py-3.5">
                <p class="text-sm text-slate-600">
                    Menampilkan <span class="font-semibold text-slate-900">{{ number_format($registrations->count(), 0, ',', '.') }}</span>
                    dari <span class="font-semibold text-slate-900">{{ number_format($registrations->total(), 0, ',', '.') }}</span> pendaftar
                </p>
            </div>

            @if ($registrations->isEmpty())
                <x-empty-state icon="users" title="Belum ada pendaftar"
                               description="Pendaftar akan muncul di sini setelah mengirimkan formulir pendaftaran." />
            @else
                <div class="table-wrapper">
                    <table class="data-table">
                        <thead>
                            <tr>
                                <th scope="col">No</th>
                                <th scope="col">Nomor Pendaftaran</th>
                                <th scope="col">Nama</th>
                                <th scope="col">Asal Sekolah</th>
                                <th scope="col">Jalur</th>
                                <th scope="col">Program</th>
                                <th scope="col">Tanggal Daftar</th>
                                <th scope="col">Status</th>
                                <th scope="col"><span class="sr-only">Aksi</span></th>
                            </tr>
                        </thead>
                        <tbody>
                            @foreach ($registrations as $registration)
                                <tr>
                                    <td class="text-slate-500">{{ $registrations->firstItem() + $loop->index }}</td>
                                    <td>
                                        <a href="{{ route('admin.registrations.show', $registration) }}"
                                           class="font-mono text-sm font-semibold text-brand-700 hover:text-brand-800">
                                            {{ $registration->registration_number }}
                                        </a>
                                    </td>
                                    <td>
                                        <span class="font-medium text-slate-900">{{ $registration->applicant->full_name }}</span>
                                        <span class="block text-xs text-slate-500">NISN {{ $registration->applicant->nisn ?: '-' }}</span>
                                    </td>
                                    <td class="max-w-48 truncate">{{ $registration->applicant->previousSchool?->school_name ?? '-' }}</td>
                                    <td>{{ $registration->admissionTrack->name }}</td>
                                    <td>{{ $registration->program?->name ?? '-' }}</td>
                                    <td class="whitespace-nowrap">
                                        @if ($registration->submitted_at)
                                            {{ $registration->submitted_at->translatedFormat('d M Y') }}
                                            <span class="block text-xs text-slate-500">{{ $registration->submitted_at->format('H:i') }}</span>
                                        @else
                                            <span class="text-slate-400">Belum dikirim</span>
                                        @endif
                                    </td>
                                    <td>
                                        <x-badge :class="$registration->registration_status->badge()">
                                            {{ $registration->registration_status->label() }}
                                        </x-badge>

                                        @if ($registration->hasPublishedResult())
                                            <x-badge :class="$registration->selection_status->badge()" class="mt-1">
                                                {{ $registration->selection_status->label() }}
                                            </x-badge>
                                        @endif
                                    </td>
                                    <td class="text-right">
                                        <a href="{{ route('admin.registrations.show', $registration) }}" class="btn-secondary btn-sm">
                                            <x-icon name="eye" class="h-3.5 w-3.5" />
                                            Detail
                                        </a>
                                    </td>
                                </tr>
                            @endforeach
                        </tbody>
                    </table>
                </div>

                <div class="border-t border-slate-100 px-5 py-4">
                    {{ $registrations->links() }}
                </div>
            @endif
        </div>
    </div>
@endsection
