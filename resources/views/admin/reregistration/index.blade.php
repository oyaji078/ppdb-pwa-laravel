@extends('layouts.admin')

@section('title', 'Daftar Ulang')
@section('subheading', 'Pendaftar yang dinyatakan diterima dan hasilnya sudah dipublikasikan.')

@section('content')
    <div class="space-y-5">
        <section class="grid gap-4 sm:grid-cols-2 lg:grid-cols-4">
            <x-stat-card label="Diterima" :value="number_format($summary['accepted'], 0, ',', '.')"
                         icon="award" tone="brand" />
            <x-stat-card label="Menunggu Daftar Ulang" :value="number_format($summary['pending'], 0, ',', '.')"
                         icon="clock" tone="warning" />
            <x-stat-card label="Selesai" :value="number_format($summary['completed'], 0, ',', '.')"
                         icon="circle-check" tone="success" />
            <x-stat-card label="Mengundurkan Diri / Kedaluwarsa" :value="number_format($summary['withdrawn'], 0, ',', '.')"
                         icon="user-x" tone="danger" />
        </section>

        <x-admin.filter-bar :action="route('admin.reregistration.index')"
                            :academic-years="$academicYears" :waves="$waves" :tracks="$tracks" :programs="$programs"
                            :reregistration-statuses="$reregistrationStatuses" />

        <div class="card overflow-hidden">
            @if ($registrations->isEmpty())
                <x-empty-state icon="clipboard-check" title="Belum ada pendaftar yang diterima"
                               description="Daftar ulang terbuka setelah hasil seleksi dipublikasikan untuk pendaftar yang diterima." />
            @else
                <ul class="divide-y divide-slate-100">
                    @foreach ($registrations as $registration)
                        @php $reregistration = $registration->reregistration; @endphp

                        <li class="p-5" x-data="{ open: false }">
                            <div class="flex flex-wrap items-start justify-between gap-3">
                                <div class="min-w-0">
                                    <div class="flex flex-wrap items-center gap-2">
                                        <a href="{{ route('admin.registrations.show', $registration) }}"
                                           class="font-mono text-sm font-semibold text-brand-700 hover:text-brand-800">
                                            {{ $registration->registration_number }}
                                        </a>
                                        <x-badge :class="$registration->reregistration_status->badge()">
                                            {{ $registration->reregistration_status->label() }}
                                        </x-badge>
                                    </div>

                                    <p class="mt-1 font-medium text-slate-900">{{ $registration->applicant->full_name }}</p>
                                    <p class="text-xs text-slate-500">
                                        {{ $registration->admissionTrack->name }} &middot;
                                        {{ $registration->program?->name ?? 'Tanpa program' }}
                                    </p>

                                    @if ($reregistration?->completed_at)
                                        <p class="mt-1 text-xs text-emerald-700">
                                            Selesai {{ $reregistration->completed_at->translatedFormat('d M Y, H:i') }}
                                            @if ($reregistration->verifier) oleh {{ $reregistration->verifier->name }} @endif
                                        </p>
                                    @endif

                                    @if ($reregistration?->notes)
                                        <p class="mt-1 text-xs text-slate-600">Catatan: {{ $reregistration->notes }}</p>
                                    @endif
                                </div>

                                @can('manageReregistration', $registration)
                                    <button type="button" @click="open = ! open" class="btn-secondary btn-sm shrink-0">
                                        <x-icon name="pencil" class="h-3.5 w-3.5" />
                                        Ubah Status
                                    </button>
                                @endcan
                            </div>

                            @can('manageReregistration', $registration)
                                <form method="POST" action="{{ route('admin.reregistration.update', $registration) }}"
                                      x-show="open" x-cloak x-collapse class="mt-4 rounded-lg bg-slate-50 p-4">
                                    @csrf

                                    <div class="grid gap-4 sm:grid-cols-3">
                                        <x-form.select :name="'status'" :id="'rr-status-'.$registration->id"
                                                       label="Status Daftar Ulang" required :placeholder="null"
                                                       :value="$registration->reregistration_status->value"
                                                       :options="$statusOptions" />

                                        <div class="sm:col-span-2">
                                            <x-form.textarea :name="'notes'" :id="'rr-notes-'.$registration->id"
                                                             label="Catatan" rows="2" :value="$reregistration?->notes"
                                                             placeholder="Contoh: Berkas daftar ulang lengkap dan biaya administrasi telah diselesaikan." />
                                        </div>
                                    </div>

                                    <button type="submit" class="btn-primary btn-sm mt-4">
                                        <x-icon name="save" class="h-3.5 w-3.5" />
                                        Simpan
                                    </button>
                                </form>
                            @endcan
                        </li>
                    @endforeach
                </ul>

                <div class="border-t border-slate-100 px-5 py-4">{{ $registrations->links() }}</div>
            @endif
        </div>
    </div>
@endsection
