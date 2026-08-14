@extends('layouts.admin')

@section('title', 'Tahun Ajaran')
@section('subheading', 'Periode penerimaan. Hanya satu tahun ajaran yang aktif pada satu waktu.')

@section('actions')
    <a href="{{ route('admin.academic-years.create') }}" class="btn-primary btn-sm">
        <x-icon name="plus" class="h-4 w-4" />
        Tambah Tahun Ajaran
    </a>
@endsection

@section('content')
    <div class="card overflow-hidden">
        @if ($academicYears->isEmpty())
            <x-empty-state icon="calendar-days" title="Belum ada tahun ajaran"
                           description="Tambahkan tahun ajaran terlebih dahulu sebelum membuat gelombang, jalur, dan program.">
                <a href="{{ route('admin.academic-years.create') }}" class="btn-primary btn-sm">
                    <x-icon name="plus" class="h-4 w-4" />
                    Tambah Tahun Ajaran
                </a>
            </x-empty-state>
        @else
            <div class="table-wrapper">
                <table class="data-table">
                    <thead>
                        <tr>
                            <th scope="col">Tahun Ajaran</th>
                            <th scope="col">Periode</th>
                            <th scope="col">Gelombang</th>
                            <th scope="col">Jalur</th>
                            <th scope="col">Program</th>
                            <th scope="col">Pendaftar</th>
                            <th scope="col">Status</th>
                            <th scope="col"><span class="sr-only">Aksi</span></th>
                        </tr>
                    </thead>
                    <tbody>
                        @foreach ($academicYears as $year)
                            <tr>
                                <td class="font-semibold text-slate-900">{{ $year->name }}</td>
                                <td>{{ $year->start_year }} &ndash; {{ $year->end_year }}</td>
                                <td>{{ $year->waves_count }}</td>
                                <td>{{ $year->admission_tracks_count }}</td>
                                <td>{{ $year->programs_count }}</td>
                                <td>{{ number_format($year->registrations_count, 0, ',', '.') }}</td>
                                <td>
                                    <div class="flex flex-wrap gap-1">
                                        @if ($year->is_active)
                                            <x-badge class="bg-emerald-50 text-emerald-700 ring-emerald-200" icon="circle-check">Aktif</x-badge>
                                        @else
                                            <x-badge class="bg-slate-100 text-slate-600 ring-slate-200">Nonaktif</x-badge>
                                        @endif

                                        @if ($year->registration_open)
                                            <x-badge class="bg-blue-50 text-blue-700 ring-blue-200">Pendaftaran Dibuka</x-badge>
                                        @else
                                            <x-badge class="bg-slate-100 text-slate-500 ring-slate-200">Pendaftaran Ditutup</x-badge>
                                        @endif
                                    </div>
                                </td>
                                <td>
                                    <div class="flex justify-end gap-2">
                                        @unless ($year->is_active)
                                            <form method="POST" action="{{ route('admin.academic-years.activate', $year) }}"
                                                  onsubmit="return confirm('Jadikan {{ $year->name }} sebagai tahun ajaran aktif?')">
                                                @csrf
                                                <button type="submit" class="btn-secondary btn-sm">
                                                    <x-icon name="check" class="h-3.5 w-3.5" />
                                                    Aktifkan
                                                </button>
                                            </form>
                                        @endunless

                                        <a href="{{ route('admin.academic-years.edit', $year) }}" class="btn-secondary btn-sm">
                                            <x-icon name="pencil" class="h-3.5 w-3.5" />
                                            Edit
                                        </a>

                                        @if ($year->registrations_count === 0)
                                            <form method="POST" action="{{ route('admin.academic-years.destroy', $year) }}"
                                                  onsubmit="return confirm('Hapus tahun ajaran {{ $year->name }}?')">
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

            <div class="border-t border-slate-100 px-5 py-4">{{ $academicYears->links() }}</div>
        @endif
    </div>
@endsection
