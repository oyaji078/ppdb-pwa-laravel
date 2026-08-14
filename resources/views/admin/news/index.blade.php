@extends('layouts.admin')

@section('title', 'Berita')
@section('subheading', 'Kabar dan kegiatan sekolah yang tampil di website.')

@section('actions')
    <a href="{{ route('admin.news.create') }}" class="btn-primary btn-sm">
        <x-icon name="plus" class="h-4 w-4" />
        Tulis Berita
    </a>
@endsection

@section('content')
    <div class="space-y-5">
        <form method="GET" action="{{ route('admin.news.index') }}" class="card flex flex-wrap items-end gap-3 p-4">
            <div class="min-w-56 flex-1">
                <x-form.input name="q" label="Cari Judul" :value="request('q')" placeholder="Kata kunci judul" />
            </div>
            <button type="submit" class="btn-secondary">
                <x-icon name="search" class="h-4 w-4" />
                Cari
            </button>
        </form>

        <div class="card overflow-hidden">
            @if ($news->isEmpty())
                <x-empty-state icon="newspaper" title="Belum ada berita"
                               description="Tulis berita untuk menampilkan kegiatan sekolah di website.">
                    <a href="{{ route('admin.news.create') }}" class="btn-primary btn-sm">
                        <x-icon name="plus" class="h-4 w-4" />
                        Tulis Berita
                    </a>
                </x-empty-state>
            @else
                <div class="table-wrapper">
                    <table class="data-table">
                        <thead>
                            <tr>
                                <th scope="col">Sampul</th>
                                <th scope="col">Judul</th>
                                <th scope="col">Penulis</th>
                                <th scope="col">Publikasi</th>
                                <th scope="col">Dibaca</th>
                                <th scope="col">Status</th>
                                <th scope="col"><span class="sr-only">Aksi</span></th>
                            </tr>
                        </thead>
                        <tbody>
                            @foreach ($news as $item)
                                <tr>
                                    <td>
                                        @if ($item->cover_path)
                                            <img src="{{ Storage::disk('public')->url($item->cover_path) }}" alt=""
                                                 class="h-11 w-16 rounded object-cover">
                                        @else
                                            <span class="flex h-11 w-16 items-center justify-center rounded bg-slate-100 text-slate-300">
                                                <x-icon name="image" class="h-4 w-4" />
                                            </span>
                                        @endif
                                    </td>
                                    <td>
                                        <span class="font-medium text-slate-900">{{ $item->title }}</span>
                                        <span class="block max-w-64 truncate text-xs text-slate-500">{{ $item->excerpt }}</span>
                                    </td>
                                    <td>{{ $item->author?->name ?? '-' }}</td>
                                    <td class="whitespace-nowrap">{{ $item->published_at?->translatedFormat('d M Y') ?? '-' }}</td>
                                    <td>{{ number_format($item->views, 0, ',', '.') }}</td>
                                    <td>
                                        @if ($item->is_published)
                                            <x-badge class="bg-emerald-50 text-emerald-700 ring-emerald-200">Terbit</x-badge>
                                        @else
                                            <x-badge class="bg-slate-100 text-slate-600 ring-slate-200">Draft</x-badge>
                                        @endif
                                    </td>
                                    <td>
                                        <div class="flex justify-end gap-2">
                                            @if ($item->is_published)
                                                <a href="{{ route('news.show', $item) }}" target="_blank" rel="noopener" class="btn-secondary btn-sm">
                                                    <x-icon name="external-link" class="h-3.5 w-3.5" />
                                                    <span class="sr-only">Lihat</span>
                                                </a>
                                            @endif

                                            <a href="{{ route('admin.news.edit', $item) }}" class="btn-secondary btn-sm">
                                                <x-icon name="pencil" class="h-3.5 w-3.5" />
                                                Edit
                                            </a>

                                            <form method="POST" action="{{ route('admin.news.destroy', $item) }}"
                                                  onsubmit="return confirm('Hapus berita ini?')">
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

                <div class="border-t border-slate-100 px-5 py-4">{{ $news->links() }}</div>
            @endif
        </div>
    </div>
@endsection
