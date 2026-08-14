@extends('layouts.admin')

@section('title', 'Pengumuman')
@section('subheading', 'Pengumuman untuk publik maupun pendaftar.')

@section('actions')
    <a href="{{ route('admin.announcements.create') }}" class="btn-primary btn-sm">
        <x-icon name="plus" class="h-4 w-4" />
        Tambah Pengumuman
    </a>
@endsection

@section('content')
    <div class="space-y-5">
        <form method="GET" action="{{ route('admin.announcements.index') }}" class="card flex flex-wrap items-end gap-3 p-4">
            <div class="min-w-56 flex-1">
                <x-form.input name="q" label="Cari Judul" :value="request('q')" placeholder="Kata kunci judul" />
            </div>
            <div class="min-w-48">
                <x-form.select name="audience" label="Target Pembaca" placeholder="Semua target"
                               :value="request('audience')" :options="$audiences" />
            </div>
            <button type="submit" class="btn-secondary">
                <x-icon name="filter" class="h-4 w-4" />
                Terapkan
            </button>
        </form>

        <div class="card overflow-hidden">
            @if ($announcements->isEmpty())
                <x-empty-state icon="megaphone" title="Belum ada pengumuman"
                               description="Buat pengumuman untuk menyampaikan informasi kepada publik atau pendaftar.">
                    <a href="{{ route('admin.announcements.create') }}" class="btn-primary btn-sm">
                        <x-icon name="plus" class="h-4 w-4" />
                        Tambah Pengumuman
                    </a>
                </x-empty-state>
            @else
                <div class="table-wrapper">
                    <table class="data-table">
                        <thead>
                            <tr>
                                <th scope="col">Judul</th>
                                <th scope="col">Target</th>
                                <th scope="col">Tahun Ajaran</th>
                                <th scope="col">Publikasi</th>
                                <th scope="col">Status</th>
                                <th scope="col"><span class="sr-only">Aksi</span></th>
                            </tr>
                        </thead>
                        <tbody>
                            @foreach ($announcements as $announcement)
                                <tr>
                                    <td>
                                        <span class="font-medium text-slate-900">{{ $announcement->title }}</span>
                                        <span class="block max-w-72 truncate text-xs text-slate-500">
                                            {{ Str::limit(strip_tags($announcement->content), 80) }}
                                        </span>
                                    </td>
                                    <td>
                                        <x-badge :class="$announcement->audience->badge()">{{ $announcement->audience->label() }}</x-badge>
                                    </td>
                                    <td>{{ $announcement->academicYear?->name ?? 'Semua' }}</td>
                                    <td class="whitespace-nowrap">
                                        {{ $announcement->published_at?->translatedFormat('d M Y') ?? '-' }}
                                        @if ($announcement->expires_at)
                                            <span class="block text-xs text-slate-500">
                                                s.d. {{ $announcement->expires_at->translatedFormat('d M Y') }}
                                            </span>
                                        @endif
                                    </td>
                                    <td>
                                        @if ($announcement->is_published)
                                            <x-badge class="bg-emerald-50 text-emerald-700 ring-emerald-200">Terbit</x-badge>
                                        @else
                                            <x-badge class="bg-slate-100 text-slate-600 ring-slate-200">Draft</x-badge>
                                        @endif
                                    </td>
                                    <td>
                                        <div class="flex justify-end gap-2">
                                            <form method="POST" action="{{ route('admin.announcements.publish', $announcement) }}">
                                                @csrf
                                                <button type="submit" class="btn-secondary btn-sm">
                                                    <x-icon :name="$announcement->is_published ? 'eye-off' : 'megaphone'" class="h-3.5 w-3.5" />
                                                    {{ $announcement->is_published ? 'Sembunyikan' : 'Terbitkan' }}
                                                </button>
                                            </form>

                                            <a href="{{ route('admin.announcements.edit', $announcement) }}" class="btn-secondary btn-sm">
                                                <x-icon name="pencil" class="h-3.5 w-3.5" />
                                                Edit
                                            </a>

                                            <form method="POST" action="{{ route('admin.announcements.destroy', $announcement) }}"
                                                  onsubmit="return confirm('Hapus pengumuman ini?')">
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

                <div class="border-t border-slate-100 px-5 py-4">{{ $announcements->links() }}</div>
            @endif
        </div>
    </div>
@endsection
