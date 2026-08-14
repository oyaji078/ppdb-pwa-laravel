@extends('layouts.admin')

@section('title', 'Fasilitas')
@section('subheading', 'Sarana dan prasarana yang ditampilkan di website.')

@section('actions')
    <a href="{{ route('admin.facilities.create') }}" class="btn-primary btn-sm">
        <x-icon name="plus" class="h-4 w-4" />
        Tambah Fasilitas
    </a>
@endsection

@section('content')
    <div class="card overflow-hidden">
        @if ($facilities->isEmpty())
            <x-empty-state icon="building-2" title="Belum ada fasilitas"
                           description="Tambahkan fasilitas sekolah untuk ditampilkan pada halaman publik.">
                <a href="{{ route('admin.facilities.create') }}" class="btn-primary btn-sm">
                    <x-icon name="plus" class="h-4 w-4" />
                    Tambah Fasilitas
                </a>
            </x-empty-state>
        @else
            <div class="table-wrapper">
                <table class="data-table">
                    <thead>
                        <tr>
                            <th scope="col">Urutan</th>
                            <th scope="col">Gambar</th>
                            <th scope="col">Nama</th>
                            <th scope="col">Status</th>
                            <th scope="col"><span class="sr-only">Aksi</span></th>
                        </tr>
                    </thead>
                    <tbody>
                        @foreach ($facilities as $facility)
                            <tr>
                                <td class="text-slate-500">{{ $facility->sort_order }}</td>
                                <td>
                                    @if ($facility->image_path)
                                        <img src="{{ Storage::disk('public')->url($facility->image_path) }}" alt=""
                                             class="h-11 w-16 rounded object-cover">
                                    @else
                                        <span class="flex h-11 w-16 items-center justify-center rounded bg-brand-50 text-brand-300">
                                            <x-icon :name="$facility->icon ?: 'building-2'" class="h-4 w-4" />
                                        </span>
                                    @endif
                                </td>
                                <td>
                                    <span class="font-medium text-slate-900">{{ $facility->name }}</span>
                                    @if ($facility->description)
                                        <span class="block max-w-72 truncate text-xs text-slate-500">{{ $facility->description }}</span>
                                    @endif
                                </td>
                                <td>
                                    @if ($facility->is_active)
                                        <x-badge class="bg-emerald-50 text-emerald-700 ring-emerald-200">Tampil</x-badge>
                                    @else
                                        <x-badge class="bg-slate-100 text-slate-600 ring-slate-200">Disembunyikan</x-badge>
                                    @endif
                                </td>
                                <td>
                                    <div class="flex justify-end gap-2">
                                        <a href="{{ route('admin.facilities.edit', $facility) }}" class="btn-secondary btn-sm">
                                            <x-icon name="pencil" class="h-3.5 w-3.5" />
                                            Edit
                                        </a>

                                        <form method="POST" action="{{ route('admin.facilities.destroy', $facility) }}"
                                              onsubmit="return confirm('Hapus fasilitas ini?')">
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

            <div class="border-t border-slate-100 px-5 py-4">{{ $facilities->links() }}</div>
        @endif
    </div>
@endsection
