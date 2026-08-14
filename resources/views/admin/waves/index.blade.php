@extends('layouts.admin')

@section('title', 'Gelombang')
@section('subheading', 'Periode pendaftaran. Kode gelombang menjadi bagian dari nomor pendaftaran.')

@section('actions')
    <a href="{{ route('admin.waves.create') }}" class="btn-primary btn-sm">
        <x-icon name="plus" class="h-4 w-4" />
        Tambah Gelombang
    </a>
@endsection

@section('content')
    <div class="space-y-5">
        <x-admin.year-filter :action="route('admin.waves.index')" :academic-years="$academicYears" :selected="$selectedYearId" />

        <div class="card overflow-hidden">
            @if ($waves->isEmpty())
                <x-empty-state icon="layers" title="Belum ada gelombang"
                               description="Tambahkan minimal satu gelombang agar calon peserta didik dapat mendaftar.">
                    <a href="{{ route('admin.waves.create') }}" class="btn-primary btn-sm">
                        <x-icon name="plus" class="h-4 w-4" />
                        Tambah Gelombang
                    </a>
                </x-empty-state>
            @else
                <div class="table-wrapper">
                    <table class="data-table">
                        <thead>
                            <tr>
                                <th scope="col">Kode</th>
                                <th scope="col">Nama</th>
                                <th scope="col">Tahun Ajaran</th>
                                <th scope="col">Periode</th>
                                <th scope="col">Kuota</th>
                                <th scope="col">Pendaftar</th>
                                <th scope="col">Status</th>
                                <th scope="col"><span class="sr-only">Aksi</span></th>
                            </tr>
                        </thead>
                        <tbody>
                            @foreach ($waves as $wave)
                                <tr>
                                    <td class="font-mono font-semibold text-slate-900">{{ $wave->code }}</td>
                                    <td class="font-medium text-slate-900">{{ $wave->name }}</td>
                                    <td>{{ $wave->academicYear->name }}</td>
                                    <td class="whitespace-nowrap">
                                        {{ $wave->start_at->translatedFormat('d M Y') }}
                                        <span class="block text-xs text-slate-500">s.d. {{ $wave->end_at->translatedFormat('d M Y') }}</span>
                                    </td>
                                    <td>{{ $wave->quota ? number_format($wave->quota, 0, ',', '.') : 'Tanpa batas' }}</td>
                                    <td>{{ number_format($wave->registrations_count, 0, ',', '.') }}</td>
                                    <td>
                                        @if ($wave->isOpen())
                                            <x-badge class="bg-emerald-50 text-emerald-700 ring-emerald-200">Dibuka</x-badge>
                                        @elseif (! $wave->is_active)
                                            <x-badge class="bg-slate-100 text-slate-600 ring-slate-200">Nonaktif</x-badge>
                                        @elseif ($wave->end_at->isPast())
                                            <x-badge class="bg-slate-100 text-slate-500 ring-slate-200">Ditutup</x-badge>
                                        @else
                                            <x-badge class="bg-blue-50 text-blue-700 ring-blue-200">Akan Datang</x-badge>
                                        @endif
                                    </td>
                                    <td>
                                        <div class="flex justify-end gap-2">
                                            <a href="{{ route('admin.waves.edit', $wave) }}" class="btn-secondary btn-sm">
                                                <x-icon name="pencil" class="h-3.5 w-3.5" />
                                                Edit
                                            </a>

                                            @if ($wave->registrations_count === 0)
                                                <form method="POST" action="{{ route('admin.waves.destroy', $wave) }}"
                                                      onsubmit="return confirm('Hapus gelombang {{ $wave->name }}?')">
                                                    @csrf
                                                    @method('DELETE')
                                                    <button type="submit" class="btn-secondary btn-sm text-rose-600">
                                                        <x-icon name="trash-2" class="h-3.5 w-3.5" />
                                                        <span class="sr-only">Hapus</span>
                                                    </button>
                                                </form>
                                            @endif
                                        </div>
                                    </td>
                                </tr>
                            @endforeach
                        </tbody>
                    </table>
                </div>

                <div class="border-t border-slate-100 px-5 py-4">{{ $waves->links() }}</div>
            @endif
        </div>
    </div>
@endsection
