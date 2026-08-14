@extends('layouts.public')

@section('title', $gallery->title)

@section('hero')
    <x-page-header :title="$gallery->title" :subtitle="$gallery->description"
                   :breadcrumb="['Galeri' => route('gallery.index'), $gallery->title => null]" />
@endsection

@section('content')
    <div x-data="{ active: null }" class="mx-auto max-w-7xl px-4 sm:px-6 lg:px-8">
        @if ($gallery->images->isEmpty())
            <div class="card">
                <x-empty-state icon="image-off" title="Album masih kosong"
                               description="Belum ada foto pada album ini." />
            </div>
        @else
            <div class="grid grid-cols-2 gap-3 sm:grid-cols-3 lg:grid-cols-4">
                @foreach ($gallery->images as $image)
                    <button type="button"
                            @click="active = '{{ Storage::disk('public')->url($image->image_path) }}'"
                            class="group relative overflow-hidden rounded-xl bg-slate-100 ring-1 ring-slate-200">
                        <img src="{{ Storage::disk('public')->url($image->image_path) }}"
                             alt="{{ $image->caption ?: 'Foto '.$gallery->title }}"
                             class="h-40 w-full object-cover transition-transform group-hover:scale-105 sm:h-48" loading="lazy">
                        @if ($image->caption)
                            <span class="absolute inset-x-0 bottom-0 bg-gradient-to-t from-slate-900/80 to-transparent p-2 text-left text-xs text-white">
                                {{ $image->caption }}
                            </span>
                        @endif
                    </button>
                @endforeach
            </div>

            {{-- Lightbox --}}
            <div x-show="active" x-cloak @click="active = null" @keydown.escape.window="active = null"
                 class="fixed inset-0 z-50 flex items-center justify-center bg-slate-900/90 p-4"
                 role="dialog" aria-modal="true" aria-label="Pratinjau foto">
                <button type="button" @click="active = null" class="absolute top-4 right-4 rounded-lg bg-white/10 p-2 text-white hover:bg-white/20">
                    <span class="sr-only">Tutup pratinjau</span>
                    <x-icon name="x" class="h-6 w-6" />
                </button>
                <img :src="active" alt="" class="max-h-[85vh] max-w-full rounded-lg object-contain">
            </div>
        @endif

        <a href="{{ route('gallery.index') }}" class="mt-8 inline-flex items-center gap-1.5 text-sm font-semibold text-brand-600 hover:text-brand-700">
            <x-icon name="arrow-left" class="h-4 w-4" />
            Kembali ke galeri
        </a>
    </div>
@endsection
