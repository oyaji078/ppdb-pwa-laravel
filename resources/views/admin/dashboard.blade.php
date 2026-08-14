@extends('layouts.admin')

@section('title', 'Dashboard')
@section('subheading', 'Ringkasan penerimaan peserta didik baru.')

@section('content')
    <div class="space-y-6">
        {{-- Filter --}}
        <form method="GET" action="{{ route('admin.dashboard') }}" class="card flex flex-wrap items-end gap-3 p-4">
            <div class="min-w-48 flex-1">
                <x-form.select name="academic_year_id" label="Tahun Ajaran" placeholder="Semua tahun"
                               :value="$selectedYearId" :options="$academicYears->pluck('name', 'id')->all()" />
            </div>
            <div class="min-w-48 flex-1">
                <x-form.select name="registration_wave_id" label="Gelombang" placeholder="Semua gelombang"
                               :value="$selectedWaveId" :options="$waves->pluck('name', 'id')->all()" />
            </div>
            <button type="submit" class="btn-secondary">
                <x-icon name="filter" class="h-4 w-4" />
                Terapkan
            </button>
        </form>

        {{-- Statistic cards --}}
        <section class="grid gap-4 sm:grid-cols-2 lg:grid-cols-5">
            <x-stat-card label="Total Pendaftar" :value="number_format($statistics['total'], 0, ',', '.')"
                         icon="users" tone="brand" :href="route('admin.registrations.index', request()->query())" />

            <x-stat-card label="Sudah Diverifikasi" :value="number_format($statistics['verified'], 0, ',', '.')"
                         icon="file-check" tone="success"
                         :href="route('admin.registrations.index', array_merge(request()->query(), ['registration_status' => 'verified']))" />

            <x-stat-card label="Belum Diverifikasi" :value="number_format($statistics['unverified'], 0, ',', '.')"
                         icon="file-clock" tone="warning"
                         :href="route('admin.verification.index', request()->query())" />

            <x-stat-card label="Diterima" :value="number_format($statistics['accepted'], 0, ',', '.')"
                         icon="award" tone="info"
                         :href="route('admin.registrations.index', array_merge(request()->query(), ['selection_status' => 'accepted']))" />

            <x-stat-card label="Ditolak" :value="number_format($statistics['rejected'], 0, ',', '.')"
                         icon="user-x" tone="danger"
                         :href="route('admin.registrations.index', array_merge(request()->query(), ['selection_status' => 'rejected']))" />
        </section>

        {{-- Charts --}}
        <section class="grid gap-5 lg:grid-cols-3">
            <div class="card p-5 lg:col-span-2" data-chart-holder>
                <h2 class="font-semibold text-slate-900">Grafik Pendaftaran</h2>
                <p class="text-xs text-slate-500">Jumlah pendaftar terkirim dalam 14 hari terakhir.</p>

                <div class="mt-4 h-64">
                    <canvas data-chart="line" data-source="chart-trend" aria-label="Grafik pendaftaran harian" role="img"></canvas>
                    <div data-chart-empty class="hidden h-full">
                        <x-empty-state icon="chart-line" title="Belum ada data pendaftaran"
                                       description="Grafik akan muncul setelah ada pendaftar yang mengirim formulir." />
                    </div>
                </div>

                <script type="application/json" id="chart-trend">@json($trend)</script>
            </div>

            <div class="card p-5" data-chart-holder>
                <h2 class="font-semibold text-slate-900">Status Verifikasi</h2>
                <p class="text-xs text-slate-500">Sebaran status pendaftaran.</p>

                <div class="mt-4 h-64">
                    <canvas data-chart="doughnut" data-source="chart-verification" aria-label="Sebaran status verifikasi" role="img"></canvas>
                    <div data-chart-empty class="hidden h-full">
                        <x-empty-state icon="chart-pie" title="Belum ada data" />
                    </div>
                </div>

                <script type="application/json" id="chart-verification">@json($verificationChart)</script>
            </div>
        </section>

        <section class="grid gap-5 lg:grid-cols-3">
            <div class="card p-5 lg:col-span-2" data-chart-holder>
                <h2 class="font-semibold text-slate-900">Sebaran Program</h2>
                <p class="text-xs text-slate-500">Jumlah pendaftar per program pilihan.</p>

                <div class="mt-4 h-56">
                    <canvas data-chart="bar" data-source="chart-program" aria-label="Sebaran pendaftar per program" role="img"></canvas>
                    <div data-chart-empty class="hidden h-full">
                        <x-empty-state icon="chart-column" title="Belum ada data program" />
                    </div>
                </div>

                <script type="application/json" id="chart-program">@json($programChart)</script>
            </div>

            {{-- Aksi cepat --}}
            <div class="card p-5">
                <h2 class="font-semibold text-slate-900">Aksi Cepat</h2>

                <div class="mt-4 space-y-2.5">
                    <a href="{{ route('admin.verification.index') }}"
                       class="flex items-center justify-between gap-3 rounded-lg p-3 ring-1 ring-slate-200 transition-colors hover:bg-slate-50">
                        <span class="flex items-center gap-2.5">
                            <span class="flex h-8 w-8 items-center justify-center rounded-lg bg-amber-50 text-amber-600">
                                <x-icon name="file-check" class="h-4 w-4" />
                            </span>
                            <span class="text-sm font-medium text-slate-800">Verifikasi Berkas</span>
                        </span>
                        @if ($pendingWork['documents_pending'] > 0)
                            <x-badge class="bg-amber-50 text-amber-800 ring-amber-200">{{ $pendingWork['documents_pending'] }}</x-badge>
                        @endif
                    </a>

                    <a href="{{ route('admin.selection.index') }}"
                       class="flex items-center justify-between gap-3 rounded-lg p-3 ring-1 ring-slate-200 transition-colors hover:bg-slate-50">
                        <span class="flex items-center gap-2.5">
                            <span class="flex h-8 w-8 items-center justify-center rounded-lg bg-indigo-50 text-indigo-600">
                                <x-icon name="clipboard-list" class="h-4 w-4" />
                            </span>
                            <span class="text-sm font-medium text-slate-800">Seleksi</span>
                        </span>
                        @if ($pendingWork['awaiting_selection'] > 0)
                            <x-badge class="bg-indigo-50 text-indigo-700 ring-indigo-200">{{ $pendingWork['awaiting_selection'] }}</x-badge>
                        @endif
                    </a>

                    <a href="{{ route('admin.selection.index', ['decision' => 'unpublished']) }}"
                       class="flex items-center justify-between gap-3 rounded-lg p-3 ring-1 ring-slate-200 transition-colors hover:bg-slate-50">
                        <span class="flex items-center gap-2.5">
                            <span class="flex h-8 w-8 items-center justify-center rounded-lg bg-brand-50 text-brand-600">
                                <x-icon name="megaphone" class="h-4 w-4" />
                            </span>
                            <span class="text-sm font-medium text-slate-800">Publikasi Hasil</span>
                        </span>
                        @if ($pendingWork['unpublished_results'] > 0)
                            <x-badge class="bg-brand-50 text-brand-700 ring-brand-200">{{ $pendingWork['unpublished_results'] }}</x-badge>
                        @endif
                    </a>

                    <a href="{{ route('admin.reregistration.index') }}"
                       class="flex items-center justify-between gap-3 rounded-lg p-3 ring-1 ring-slate-200 transition-colors hover:bg-slate-50">
                        <span class="flex items-center gap-2.5">
                            <span class="flex h-8 w-8 items-center justify-center rounded-lg bg-emerald-50 text-emerald-600">
                                <x-icon name="clipboard-check" class="h-4 w-4" />
                            </span>
                            <span class="text-sm font-medium text-slate-800">Daftar Ulang</span>
                        </span>
                        @if ($pendingWork['reregistration_pending'] > 0)
                            <x-badge class="bg-emerald-50 text-emerald-700 ring-emerald-200">{{ $pendingWork['reregistration_pending'] }}</x-badge>
                        @endif
                    </a>

                    <a href="{{ route('admin.reports.index') }}"
                       class="flex items-center gap-2.5 rounded-lg p-3 ring-1 ring-slate-200 transition-colors hover:bg-slate-50">
                        <span class="flex h-8 w-8 items-center justify-center rounded-lg bg-slate-100 text-slate-600">
                            <x-icon name="chart-column" class="h-4 w-4" />
                        </span>
                        <span class="text-sm font-medium text-slate-800">Buat Laporan</span>
                    </a>
                </div>
            </div>
        </section>

        {{-- Pendaftar terbaru + pengumuman --}}
        <section class="grid gap-5 lg:grid-cols-3">
            <div class="card overflow-hidden lg:col-span-2">
                <div class="flex items-center justify-between gap-3 border-b border-slate-100 px-5 py-4">
                    <h2 class="font-semibold text-slate-900">Pendaftar Terbaru</h2>
                    <a href="{{ route('admin.registrations.index') }}" class="shrink-0 text-xs font-semibold text-brand-600 hover:text-brand-700">
                        Lihat semua
                    </a>
                </div>

                @if ($latestApplicants->isEmpty())
                    <x-empty-state icon="users" title="Belum ada pendaftar"
                                   description="Data akan muncul setelah calon peserta didik mengirim formulir." />
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
                                    <th scope="col">Tanggal</th>
                                    <th scope="col">Status</th>
                                </tr>
                            </thead>
                            <tbody>
                                @foreach ($latestApplicants as $registration)
                                    <tr>
                                        <td class="text-slate-500">{{ $loop->iteration }}</td>
                                        <td>
                                            <a href="{{ route('admin.registrations.show', $registration) }}"
                                               class="font-mono text-xs font-semibold text-brand-700 hover:text-brand-800">
                                                {{ $registration->registration_number }}
                                            </a>
                                        </td>
                                        <td class="font-medium text-slate-900">{{ $registration->applicant->full_name }}</td>
                                        <td class="max-w-40 truncate">{{ $registration->applicant->previousSchool?->school_name ?? '-' }}</td>
                                        <td>{{ $registration->admissionTrack?->name }}</td>
                                        <td class="whitespace-nowrap">{{ $registration->submitted_at?->translatedFormat('d M Y') }}</td>
                                        <td>
                                            <x-badge :class="$registration->registration_status->badge()">
                                                {{ $registration->registration_status->label() }}
                                            </x-badge>
                                        </td>
                                    </tr>
                                @endforeach
                            </tbody>
                        </table>
                    </div>
                @endif
            </div>

            <div class="card overflow-hidden">
                <div class="flex items-center justify-between gap-3 border-b border-slate-100 px-5 py-4">
                    <h2 class="font-semibold text-slate-900">Pengumuman Terbaru</h2>
                    <a href="{{ route('admin.announcements.index') }}" class="shrink-0 text-xs font-semibold text-brand-600 hover:text-brand-700">
                        Kelola
                    </a>
                </div>

                @if ($announcements->isEmpty())
                    <x-empty-state icon="megaphone" title="Belum ada pengumuman" class="py-10">
                        <a href="{{ route('admin.announcements.create') }}" class="btn-secondary btn-sm">
                            <x-icon name="plus" class="h-3.5 w-3.5" />
                            Buat Pengumuman
                        </a>
                    </x-empty-state>
                @else
                    <ul class="divide-y divide-slate-100">
                        @foreach ($announcements as $announcement)
                            <li class="px-5 py-3.5">
                                <div class="flex items-center gap-2">
                                    <x-badge :class="$announcement->audience->badge()">{{ $announcement->audience->label() }}</x-badge>
                                </div>
                                <p class="mt-1.5 text-sm font-medium text-slate-900">{{ $announcement->title }}</p>
                                <p class="mt-0.5 text-xs text-slate-500">{{ $announcement->published_at?->translatedFormat('d M Y, H:i') }}</p>
                            </li>
                        @endforeach
                    </ul>
                @endif
            </div>
        </section>
    </div>
@endsection
