@extends('layouts.admin')

@section('title', 'Galeri')
@section('subheading', 'Album dokumentasi kegiatan sekolah.')

@section('actions')
    <a href="{{ route('admin.galleries.create') }}" class="btn-primary btn-sm">
        <x-icon name="plus" class="h-4 w-4" />
        Tambah Album
    </a>
@endsection

@section('content')
    <div class="card overflow-hidden">
        @if ($galleries->isEmpty())
            <x-empty-state icon="images" title="Belum ada album"
                           description="Buat album lalu unggah foto kegiatan sekolah.">
                <a href="{{ route('admin.galleries.create') }}" class="btn-primary btn-sm">
                    <x-icon name="plus" class="h-4 w-4" />
                    Tambah Album
                </a>
            </x-empty-state>
        @else
            <div class="table-wrapper">
                <table class="data-table">
                    <thead>
                        <tr>
                            <th scope="col">Urutan</th>
                            <th scope="col">Album</th>
                            <th scope="col">Jumlah Foto</th>
                            <th scope="col">Status</th>
                            <th scope="col"><span class="sr-only">Aksi</span></th>
                        </tr>
                    </thead>
                    <tbody>
                        @foreach ($galleries as $gallery)
                            <tr>
                                <td class="text-slate-500">{{ $gallery->sort_order }}</td>
                                <td>
                                    <span class="font-medium text-slate-900">{{ $gallery->title }}</span>
                                    @if ($gallery->description)
                                        <span class="block max-w-72 truncate text-xs text-slate-500">{{ $gallery->description }}</span>
                                    @endif
                                </td>
                                <td>{{ $gallery->images_count }} foto</td>
                                <td>
                                    @if ($gallery->is_published)
                                        <x-badge class="bg-emerald-50 text-emerald-700 ring-emerald-200">Tampil</x-badge>
                                    @else
                                        <x-badge class="bg-slate-100 text-slate-600 ring-slate-200">Disembunyikan</x-badge>
                                    @endif
                                </td>
                                <td>
                                    <div class="flex justify-end gap-2">
                                        <a href="{{ route('admin.galleries.edit', $gallery) }}" class="btn-secondary btn-sm">
                                            <x-icon name="pencil" class="h-3.5 w-3.5" />
                                            Kelola
                                        </a>

                                        <form method="POST" action="{{ route('admin.galleries.destroy', $gallery) }}"
                                              onsubmit="return confirm('Hapus album beserta seluruh fotonya?')">
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

            <div class="border-t border-slate-100 px-5 py-4">{{ $galleries->links() }}</div>
        @endif
    </div>
@endsection
