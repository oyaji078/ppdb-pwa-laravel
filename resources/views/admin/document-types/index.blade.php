@extends('layouts.admin')

@section('title', 'Persyaratan Berkas')
@section('subheading', 'Jenis berkas yang dapat diminta dari pendaftar. Penugasan ke jalur diatur pada menu Jalur.')

@section('actions')
    <a href="{{ route('admin.document-types.create') }}" class="btn-primary btn-sm">
        <x-icon name="plus" class="h-4 w-4" />
        Tambah Jenis Berkas
    </a>
@endsection

@section('content')
    <div class="space-y-5">
        <x-admin.year-filter :action="route('admin.document-types.index')" :academic-years="$academicYears" :selected="$selectedYearId" />

        <div class="card overflow-hidden">
            @if ($documentTypes->isEmpty())
                <x-empty-state icon="file-stack" title="Belum ada jenis berkas"
                               description="Tambahkan jenis berkas seperti Kartu Keluarga, Akta Kelahiran, atau Rapor.">
                    <a href="{{ route('admin.document-types.create') }}" class="btn-primary btn-sm">
                        <x-icon name="plus" class="h-4 w-4" />
                        Tambah Jenis Berkas
                    </a>
                </x-empty-state>
            @else
                <div class="table-wrapper">
                    <table class="data-table">
                        <thead>
                            <tr>
                                <th scope="col">Urutan</th>
                                <th scope="col">Nama Berkas</th>
                                <th scope="col">Kode</th>
                                <th scope="col">Format</th>
                                <th scope="col">Maks Ukuran</th>
                                <th scope="col">Dipakai Jalur</th>
                                <th scope="col">Terunggah</th>
                                <th scope="col">Status</th>
                                <th scope="col"><span class="sr-only">Aksi</span></th>
                            </tr>
                        </thead>
                        <tbody>
                            @foreach ($documentTypes as $type)
                                <tr>
                                    <td class="text-slate-500">{{ $type->sort_order }}</td>
                                    <td>
                                        <span class="font-medium text-slate-900">{{ $type->name }}</span>
                                        @if ($type->description)
                                            <span class="block max-w-64 truncate text-xs text-slate-500">{{ $type->description }}</span>
                                        @endif
                                    </td>
                                    <td class="font-mono text-xs">{{ $type->code }}</td>
                                    <td class="text-xs">{{ $type->extensionLabel() }}</td>
                                    <td class="whitespace-nowrap">{{ $type->humanMaxSize() }}</td>
                                    <td>
                                        @if ($type->admission_tracks_count === 0)
                                            <x-badge class="bg-amber-50 text-amber-800 ring-amber-200">Belum dipakai</x-badge>
                                        @else
                                            {{ $type->admission_tracks_count }} jalur
                                        @endif
                                    </td>
                                    <td>{{ number_format($type->registration_documents_count, 0, ',', '.') }}</td>
                                    <td>
                                        @if ($type->is_active)
                                            <x-badge class="bg-emerald-50 text-emerald-700 ring-emerald-200">Aktif</x-badge>
                                        @else
                                            <x-badge class="bg-slate-100 text-slate-600 ring-slate-200">Nonaktif</x-badge>
                                        @endif
                                    </td>
                                    <td>
                                        <div class="flex justify-end gap-2">
                                            <a href="{{ route('admin.document-types.edit', $type) }}" class="btn-secondary btn-sm">
                                                <x-icon name="pencil" class="h-3.5 w-3.5" />
                                                Edit
                                            </a>

                                            @if ($type->registration_documents_count === 0)
                                                <form method="POST" action="{{ route('admin.document-types.destroy', $type) }}"
                                                      onsubmit="return confirm('Hapus jenis berkas {{ $type->name }}?')">
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

                <div class="border-t border-slate-100 px-5 py-4">{{ $documentTypes->links() }}</div>
            @endif
        </div>
    </div>
@endsection
