@extends('layouts.admin')

@section('title', 'Laporan')
@section('subheading', 'Rekap pendaftaran dengan filter dan ekspor.')

@section('content')
    <div class="space-y-5">
        {{-- Preset picker --}}
        <nav class="card p-4" aria-label="Jenis laporan">
            <p class="form-label">Jenis Laporan</p>
            <div class="flex flex-wrap gap-2">
                @foreach ($presets as $key => $label)
                    <a href="{{ route('admin.reports.index', array_merge(request()->except('page'), ['preset' => $key])) }}"
                       @class([
                           'rounded-lg px-3.5 py-2 text-sm font-medium transition-colors',
                           'bg-brand-600 text-white' => $preset === $key,
                           'bg-slate-100 text-slate-700 hover:bg-slate-200' => $preset !== $key,
                       ])>{{ $label }}</a>
                @endforeach
            </div>
        </nav>

        <x-admin.filter-bar :action="route('admin.reports.index')"
                            :academic-years="$academicYears" :waves="$waves" :tracks="$tracks" :programs="$programs"
                            :registration-statuses="$registrationStatuses"
                            :selection-statuses="$selectionStatuses"
                            :reregistration-statuses="$reregistrationStatuses">
            <input type="hidden" name="preset" value="{{ $preset }}">

            <x-form.input name="from" type="date" label="Periode Dari" :value="request('from')" />
            <x-form.input name="to" type="date" label="Periode Sampai" :value="request('to')" />
        </x-admin.filter-bar>

        {{-- Summary --}}
        <section class="grid gap-4 sm:grid-cols-3 lg:grid-cols-6">
            <x-stat-card label="Total" :value="number_format($summary['total'], 0, ',', '.')" icon="users" tone="brand" />
            <x-stat-card label="Terverifikasi" :value="number_format($summary['verified'], 0, ',', '.')" icon="file-check" tone="info" />
            <x-stat-card label="Diterima" :value="number_format($summary['accepted'], 0, ',', '.')" icon="award" tone="success" />
            <x-stat-card label="Cadangan" :value="number_format($summary['reserve'], 0, ',', '.')" icon="list-ordered" tone="warning" />
            <x-stat-card label="Tidak Diterima" :value="number_format($summary['rejected'], 0, ',', '.')" icon="user-x" tone="danger" />
            <x-stat-card label="Daftar Ulang Selesai" :value="number_format($summary['reregistered'], 0, ',', '.')" icon="clipboard-check" tone="success" />
        </section>

        {{-- Export --}}
        <section class="card flex flex-wrap items-center justify-between gap-4 p-5">
            <div>
                <h2 class="font-semibold text-slate-900">Ekspor Laporan {{ $presetLabel }}</h2>
                <p class="prose-content mt-1">
                    Mengekspor seluruh data yang sesuai filter saat ini
                    ({{ number_format($summary['total'], 0, ',', '.') }} baris), bukan hanya halaman ini.
                </p>
            </div>

            <div class="flex flex-wrap gap-2">
                @foreach ([['pdf', 'file-text', 'PDF'], ['csv', 'file-spreadsheet', 'CSV'], ['xlsx', 'sheet', 'XLSX']] as [$format, $icon, $label])
                    <a href="{{ route('admin.reports.export', array_merge(request()->except('page'), ['preset' => $preset, 'format' => $format])) }}"
                       class="btn-secondary btn-sm">
                        <x-icon :name="$icon" class="h-4 w-4" />
                        {{ $label }}
                    </a>
                @endforeach
            </div>
        </section>

        {{-- Table --}}
        <div class="card overflow-hidden">
            @if ($registrations->isEmpty())
                <x-empty-state icon="chart-column" title="Tidak ada data"
                               description="Tidak ada pendaftar yang sesuai dengan filter laporan ini." />
            @else
                <div class="table-wrapper">
                    <table class="data-table">
                        <thead>
                            <tr>
                                <th scope="col">No</th>
                                <th scope="col">Nomor Pendaftaran</th>
                                <th scope="col">Nama</th>
                                <th scope="col">NISN</th>
                                <th scope="col">Asal Sekolah</th>
                                <th scope="col">Jalur</th>
                                <th scope="col">Program</th>
                                <th scope="col">Status</th>
                                <th scope="col">Hasil Seleksi</th>
                                <th scope="col">Daftar Ulang</th>
                            </tr>
                        </thead>
                        <tbody>
                            @foreach ($registrations as $registration)
                                <tr>
                                    <td class="text-slate-500">{{ $registrations->firstItem() + $loop->index }}</td>
                                    <td>
                                        <a href="{{ route('admin.registrations.show', $registration) }}"
                                           class="font-mono text-xs font-semibold text-brand-700 hover:text-brand-800">
                                            {{ $registration->registration_number }}
                                        </a>
                                    </td>
                                    <td class="font-medium text-slate-900">{{ $registration->applicant->full_name }}</td>
                                    <td>{{ $registration->applicant->nisn ?: '-' }}</td>
                                    <td class="max-w-40 truncate">{{ $registration->applicant->previousSchool?->school_name ?? '-' }}</td>
                                    <td>{{ $registration->admissionTrack?->name }}</td>
                                    <td>{{ $registration->program?->name ?? '-' }}</td>
                                    <td>
                                        <x-badge :class="$registration->registration_status->badge()">
                                            {{ $registration->registration_status->label() }}
                                        </x-badge>
                                    </td>
                                    <td>
                                        @if ($registration->hasPublishedResult())
                                            <x-badge :class="$registration->selection_status->badge()">
                                                {{ $registration->selection_status->label() }}
                                            </x-badge>
                                        @else
                                            <span class="text-xs text-slate-500">Belum diumumkan</span>
                                        @endif
                                    </td>
                                    <td>
                                        <x-badge :class="$registration->reregistration_status->badge()">
                                            {{ $registration->reregistration_status->label() }}
                                        </x-badge>
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
