@extends('layouts.admin')

@section('title', 'Program / Peminatan')
@section('subheading', 'Pilihan program yang dapat dipilih calon peserta didik.')

@section('actions')
    <a href="{{ route('admin.programs.create') }}" class="btn-primary btn-sm">
        <x-icon name="plus" class="h-4 w-4" />
        Tambah Program
    </a>
@endsection

@section('content')
    <div class="space-y-5">
        <x-admin.year-filter :action="route('admin.programs.index')" :academic-years="$academicYears" :selected="$selectedYearId" />

        <div class="card overflow-hidden">
            @if ($programs->isEmpty())
                <x-empty-state icon="book-open" title="Belum ada program"
                               description="Tambahkan program atau peminatan sesuai kurikulum sekolah.">
                    <a href="{{ route('admin.programs.create') }}" class="btn-primary btn-sm">
                        <x-icon name="plus" class="h-4 w-4" />
                        Tambah Program
                    </a>
                </x-empty-state>
            @else
                <div class="table-wrapper">
                    <table class="data-table">
                        <thead>
                            <tr>
                                <th scope="col">Urutan</th>
                                <th scope="col">Nama Program</th>
                                <th scope="col">Kode</th>
                                <th scope="col">Tahun Ajaran</th>
                                <th scope="col">Kuota</th>
                                <th scope="col">Peminat</th>
                                <th scope="col">Status</th>
                                <th scope="col"><span class="sr-only">Aksi</span></th>
                            </tr>
                        </thead>
                        <tbody>
                            @foreach ($programs as $program)
                                <tr>
                                    <td class="text-slate-500">{{ $program->sort_order }}</td>
                                    <td>
                                        <span class="font-medium text-slate-900">{{ $program->name }}</span>
                                        @if ($program->description)
                                            <span class="block max-w-64 truncate text-xs text-slate-500">{{ $program->description }}</span>
                                        @endif
                                    </td>
                                    <td class="font-mono text-xs uppercase">{{ $program->code }}</td>
                                    <td>{{ $program->academicYear->name }}</td>
                                    <td>{{ $program->quota ? number_format($program->quota, 0, ',', '.') : 'Tanpa batas' }}</td>
                                    <td>
                                        {{ number_format($program->registrations_count, 0, ',', '.') }}
                                        @if ($program->quota && $program->registrations_count >= $program->quota)
                                            <x-badge class="ml-1 bg-rose-50 text-rose-700 ring-rose-200">Penuh</x-badge>
                                        @endif
                                    </td>
                                    <td>
                                        @if ($program->is_active)
                                            <x-badge class="bg-emerald-50 text-emerald-700 ring-emerald-200">Aktif</x-badge>
                                        @else
                                            <x-badge class="bg-slate-100 text-slate-600 ring-slate-200">Nonaktif</x-badge>
                                        @endif
                                    </td>
                                    <td>
                                        <div class="flex justify-end gap-2">
                                            <a href="{{ route('admin.programs.edit', $program) }}" class="btn-secondary btn-sm">
                                                <x-icon name="pencil" class="h-3.5 w-3.5" />
                                                Edit
                                            </a>

                                            @if ($program->registrations_count === 0)
                                                <form method="POST" action="{{ route('admin.programs.destroy', $program) }}"
                                                      onsubmit="return confirm('Hapus program {{ $program->name }}?')">
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

                <div class="border-t border-slate-100 px-5 py-4">{{ $programs->links() }}</div>
            @endif
        </div>
    </div>
@endsection
