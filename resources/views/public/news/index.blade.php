@extends('layouts.public')

@section('title', 'Berita')

@section('hero')
    <x-page-header title="Berita Sekolah"
                   subtitle="Kabar terbaru seputar kegiatan dan prestasi sekolah."
                   :breadcrumb="['Berita' => null]">
        <form method="GET" action="{{ route('news.index') }}" class="mt-6 flex max-w-md gap-2">
            <label for="q" class="sr-only">Cari berita</label>
            <input type="search" name="q" id="q" value="{{ $search }}" placeholder="Cari berita..." class="form-input">
            <button type="submit" class="btn-primary shrink-0">
                <x-icon name="search" class="h-4 w-4" />
                <span class="hidden sm:inline">Cari</span>
            </button>
        </form>
    </x-page-header>
@endsection

@section('content')
    <div class="mx-auto max-w-7xl px-4 sm:px-6 lg:px-8">
        @if ($news->isEmpty())
            <div class="card">
                <x-empty-state icon="newspaper"
                               :title="$search !== '' ? 'Berita tidak ditemukan' : 'Belum ada berita'"
                               :description="$search !== '' ? 'Coba gunakan kata kunci lain.' : 'Berita sekolah akan tampil di sini.'">
                    @if ($search !== '')
                        <a href="{{ route('news.index') }}" class="btn-secondary btn-sm">Tampilkan semua berita</a>
                    @endif
                </x-empty-state>
            </div>
        @else
            <div class="grid gap-5 sm:grid-cols-2 lg:grid-cols-3">
                @foreach ($news as $item)
                    <article class="card flex flex-col overflow-hidden">
                        @if ($item->cover_path)
                            <img src="{{ Storage::disk('public')->url($item->cover_path) }}" alt=""
                                 class="h-44 w-full object-cover" loading="lazy">
                        @else
                            <div class="flex h-44 items-center justify-center bg-slate-100 text-slate-300">
                                <x-icon name="newspaper" class="h-12 w-12" />
                            </div>
                        @endif

                        <div class="flex flex-1 flex-col p-5">
                            <p class="text-xs text-slate-500">{{ $item->published_at?->translatedFormat('d F Y') }}</p>
                            <h2 class="mt-1.5 font-semibold text-slate-900">
                                <a href="{{ route('news.show', $item) }}" class="hover:text-brand-700">{{ $item->title }}</a>
                            </h2>
                            <p class="prose-content mt-2 flex-1">{{ Str::limit($item->excerpt, 120) }}</p>
                            <a href="{{ route('news.show', $item) }}" class="mt-4 inline-flex items-center gap-1 text-sm font-semibold text-brand-600 hover:text-brand-700">
                                Baca selengkapnya
                                <x-icon name="arrow-right" class="h-4 w-4" />
                            </a>
                        </div>
                    </article>
                @endforeach
            </div>

            <div class="mt-8">{{ $news->links() }}</div>
        @endif
    </div>
@endsection
