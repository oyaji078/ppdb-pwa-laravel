@extends('layouts.admin')

@section('title', 'Jadwal PPDB')
@section('subheading', 'Tahapan penerimaan yang ditampilkan pada halaman publik.')

@section('actions')
    <a href="{{ route('admin.schedules.create') }}" class="btn-primary btn-sm">
        <x-icon name="plus" class="h-4 w-4" />
        Tambah Jadwal
    </a>
@endsection

@section('content')
    <div class="space-y-5">
        <x-admin.year-filter :action="route('admin.schedules.index')" :academic-years="$academicYears" :selected="$selectedYearId" />

        <div class="card overflow-hidden">
            @if ($schedules->isEmpty())
                <x-empty-state icon="calendar-clock" title="Belum ada jadwal"
                               description="Tambahkan tahapan seperti Pendaftaran, Verifikasi, Seleksi, Pengumuman, dan Daftar Ulang.">
                    <a href="{{ route('admin.schedules.create') }}" class="btn-primary btn-sm">
                        <x-icon name="plus" class="h-4 w-4" />
                        Tambah Jadwal
                    </a>
                </x-empty-state>
            @else
                <div class="table-wrapper">
                    <table class="data-table">
                        <thead>
                            <tr>
                                <th scope="col">Urutan</th>
                                <th scope="col">Tahapan</th>
                                <th scope="col">Gelombang</th>
                                <th scope="col">Periode</th>
                                <th scope="col">Fase</th>
                                <th scope="col">Publik</th>
                                <th scope="col"><span class="sr-only">Aksi</span></th>
                            </tr>
                        </thead>
                        <tbody>
                            @foreach ($schedules as $schedule)
                                <tr>
                                    <td class="text-slate-500">{{ $schedule->sort_order }}</td>
                                    <td>
                                        <span class="font-medium text-slate-900">{{ $schedule->title }}</span>
                                        @if ($schedule->description)
                                            <span class="block max-w-72 truncate text-xs text-slate-500">{{ $schedule->description }}</span>
                                        @endif
                                    </td>
                                    <td>{{ $schedule->wave?->name ?? 'Semua' }}</td>
                                    <td class="whitespace-nowrap">{{ $schedule->dateRange() }}</td>
                                    <td>
                                        <x-badge :class="$schedule->phaseBadge()">{{ $schedule->phaseLabel() }}</x-badge>
                                    </td>
                                    <td>
                                        @if ($schedule->is_public)
                                            <x-badge class="bg-emerald-50 text-emerald-700 ring-emerald-200">Tampil</x-badge>
                                        @else
                                            <x-badge class="bg-slate-100 text-slate-600 ring-slate-200">Disembunyikan</x-badge>
                                        @endif
                                    </td>
                                    <td>
                                        <div class="flex justify-end gap-2">
                                            <a href="{{ route('admin.schedules.edit', $schedule) }}" class="btn-secondary btn-sm">
                                                <x-icon name="pencil" class="h-3.5 w-3.5" />
                                                Edit
                                            </a>

                                            <form method="POST" action="{{ route('admin.schedules.destroy', $schedule) }}"
                                                  onsubmit="return confirm('Hapus jadwal {{ $schedule->title }}?')">
                                                @csrf
                                                @method('DELETE')
                                                <button type="submit" class="btn-secondary btn-sm text-rose-600">
                                                    <x-icon name="trash-2" class="h-3.5 w-3.5" />
                                                    <span class="sr-only">Hapus</span>
                                                </button>
                                            </form>
                                        </div>
                                    </td>
                                </tr>
                            @endforeach
                        </tbody>
                    </table>
                </div>

                <div class="border-t border-slate-100 px-5 py-4">{{ $schedules->links() }}</div>
            @endif
        </div>
    </div>
@endsection
