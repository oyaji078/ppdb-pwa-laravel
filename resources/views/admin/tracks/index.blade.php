@extends('layouts.admin')

@section('title', 'Jalur Pendaftaran')
@section('subheading', 'Jalur menentukan berkas apa saja yang harus diunggah pendaftar.')

@section('actions')
    <a href="{{ route('admin.tracks.create') }}" class="btn-primary btn-sm">
        <x-icon name="plus" class="h-4 w-4" />
        Tambah Jalur
    </a>
@endsection

@section('content')
    <div class="space-y-5">
        <x-admin.year-filter :action="route('admin.tracks.index')" :academic-years="$academicYears" :selected="$selectedYearId" />

        <div class="card overflow-hidden">
            @if ($tracks->isEmpty())
                <x-empty-state icon="route" title="Belum ada jalur pendaftaran"
                               description="Tambahkan jalur seperti Reguler, Prestasi, atau Afirmasi sesuai kebijakan sekolah.">
                    <a href="{{ route('admin.tracks.create') }}" class="btn-primary btn-sm">
                        <x-icon name="plus" class="h-4 w-4" />
                        Tambah Jalur
                    </a>
                </x-empty-state>
            @else
                <div class="table-wrapper">
                    <table class="data-table">
                        <thead>
                            <tr>
                                <th scope="col">Urutan</th>
                                <th scope="col">Nama Jalur</th>
                                <th scope="col">Kode</th>
                                <th scope="col">Tahun Ajaran</th>
                                <th scope="col">Kuota</th>
                                <th scope="col">Persyaratan</th>
                                <th scope="col">Pendaftar</th>
                                <th scope="col">Status</th>
                                <th scope="col"><span class="sr-only">Aksi</span></th>
                            </tr>
                        </thead>
                        <tbody>
                            @foreach ($tracks as $track)
                                <tr>
                                    <td class="text-slate-500">{{ $track->sort_order }}</td>
                                    <td>
                                        <span class="font-medium text-slate-900">{{ $track->name }}</span>
                                        @if ($track->description)
                                            <span class="block max-w-64 truncate text-xs text-slate-500">{{ $track->description }}</span>
                                        @endif
                                    </td>
                                    <td class="font-mono text-xs">{{ $track->code }}</td>
                                    <td>{{ $track->academicYear->name }}</td>
                                    <td>{{ $track->quota ? number_format($track->quota, 0, ',', '.') : 'Tanpa batas' }}</td>
                                    <td>
                                        @if ($track->document_requirements_count === 0)
                                            <x-badge class="bg-amber-50 text-amber-800 ring-amber-200">Belum diatur</x-badge>
                                        @else
                                            {{ $track->document_requirements_count }} berkas
                                        @endif
                                    </td>
                                    <td>{{ number_format($track->registrations_count, 0, ',', '.') }}</td>
                                    <td>
                                        @if ($track->is_active)
                                            <x-badge class="bg-emerald-50 text-emerald-700 ring-emerald-200">Aktif</x-badge>
                                        @else
                                            <x-badge class="bg-slate-100 text-slate-600 ring-slate-200">Nonaktif</x-badge>
                                        @endif
                                    </td>
                                    <td>
                                        <div class="flex justify-end gap-2">
                                            <a href="{{ route('admin.tracks.edit', $track) }}" class="btn-secondary btn-sm">
                                                <x-icon name="pencil" class="h-3.5 w-3.5" />
                                                Edit
                                            </a>

                                            @if ($track->registrations_count === 0)
                                                <form method="POST" action="{{ route('admin.tracks.destroy', $track) }}"
                                                      onsubmit="return confirm('Hapus jalur {{ $track->name }}?')">
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

                <div class="border-t border-slate-100 px-5 py-4">{{ $tracks->links() }}</div>
            @endif
        </div>
    </div>
@endsection
