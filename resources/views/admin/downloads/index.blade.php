@extends('layouts.admin')

@section('title', 'Unduhan')
@section('subheading', 'Berkas, formulir, dan panduan yang dapat diunduh publik.')

@section('actions')
    <a href="{{ route('admin.downloads.create') }}" class="btn-primary btn-sm">
        <x-icon name="plus" class="h-4 w-4" />
        Tambah Berkas
    </a>
@endsection

@section('content')
    <div class="card overflow-hidden">
        @if ($downloads->isEmpty())
            <x-empty-state icon="download" title="Belum ada berkas unduhan"
                           description="Unggah formulir atau panduan yang dapat diunduh calon peserta didik.">
                <a href="{{ route('admin.downloads.create') }}" class="btn-primary btn-sm">
                    <x-icon name="plus" class="h-4 w-4" />
                    Tambah Berkas
                </a>
            </x-empty-state>
        @else
            <div class="table-wrapper">
                <table class="data-table">
                    <thead>
                        <tr>
                            <th scope="col">Urutan</th>
                            <th scope="col">Judul</th>
                            <th scope="col">Format</th>
                            <th scope="col">Ukuran</th>
                            <th scope="col">Diunduh</th>
                            <th scope="col">Status</th>
                            <th scope="col"><span class="sr-only">Aksi</span></th>
                        </tr>
                    </thead>
                    <tbody>
                        @foreach ($downloads as $download)
                            <tr>
                                <td class="text-slate-500">{{ $download->sort_order }}</td>
                                <td>
                                    <span class="font-medium text-slate-900">{{ $download->title }}</span>
                                    <span class="block max-w-72 truncate text-xs text-slate-500">{{ $download->original_name }}</span>
                                </td>
                                <td class="uppercase">{{ $download->extension }}</td>
                                <td class="whitespace-nowrap">{{ $download->humanFileSize() }}</td>
                                <td>{{ number_format($download->download_count, 0, ',', '.') }}x</td>
                                <td>
                                    @if ($download->is_published)
                                        <x-badge class="bg-emerald-50 text-emerald-700 ring-emerald-200">Tampil</x-badge>
                                    @else
                                        <x-badge class="bg-slate-100 text-slate-600 ring-slate-200">Disembunyikan</x-badge>
                                    @endif
                                </td>
                                <td>
                                    <div class="flex justify-end gap-2">
                                        <a href="{{ route('downloads.download', $download) }}" class="btn-secondary btn-sm">
                                            <x-icon name="download" class="h-3.5 w-3.5" />
                                            <span class="sr-only">Unduh</span>
                                        </a>

                                        <a href="{{ route('admin.downloads.edit', $download) }}" class="btn-secondary btn-sm">
                                            <x-icon name="pencil" class="h-3.5 w-3.5" />
                                            Edit
                                        </a>

                                        <form method="POST" action="{{ route('admin.downloads.destroy', $download) }}"
                                              onsubmit="return confirm('Hapus berkas ini?')">
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

            <div class="border-t border-slate-100 px-5 py-4">{{ $downloads->links() }}</div>
        @endif
    </div>
@endsection
