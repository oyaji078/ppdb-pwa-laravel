@extends('layouts.public')

@section('title', 'Galeri')

@section('hero')
    <x-page-header title="Galeri"
                   subtitle="Dokumentasi kegiatan dan suasana sekolah."
                   :breadcrumb="['Galeri' => null]" />
@endsection

@section('content')
    <div class="mx-auto max-w-7xl px-4 sm:px-6 lg:px-8">
        @if ($galleries->isEmpty())
            <div class="card">
                <x-empty-state icon="images" title="Belum ada album"
                               description="Dokumentasi kegiatan akan ditampilkan di sini." />
            </div>
        @else
            <div class="grid gap-5 sm:grid-cols-2 lg:grid-cols-3">
                @foreach ($galleries as $gallery)
                    @php $cover = $gallery->images->first(); @endphp

                    <a href="{{ route('gallery.show', $gallery) }}" class="card group overflow-hidden">
                        @if ($cover)
                            <img src="{{ Storage::disk('public')->url($cover->image_path) }}" alt=""
                                 class="h-48 w-full object-cover transition-transform group-hover:scale-105" loading="lazy">
                        @else
                            <div class="flex h-48 items-center justify-center bg-slate-100 text-slate-300">
                                <x-icon name="images" class="h-12 w-12" />
                            </div>
                        @endif

                        <div class="p-5">
                            <h2 class="font-semibold text-slate-900 group-hover:text-brand-700">{{ $gallery->title }}</h2>
                            <p class="mt-1 text-xs text-slate-500">{{ $gallery->images_count }} foto</p>
                            @if ($gallery->description)
                                <p class="prose-content mt-2">{{ Str::limit($gallery->description, 90) }}</p>
                            @endif
                        </div>
                    </a>
                @endforeach
            </div>

            <div class="mt-8">{{ $galleries->links() }}</div>
        @endif
    </div>
@endsection
