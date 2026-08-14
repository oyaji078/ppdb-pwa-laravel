@extends('layouts.public')

@section('title', 'Fasilitas')

@section('hero')
    <x-page-header title="Fasilitas Sekolah"
                   subtitle="Sarana dan prasarana yang menunjang kegiatan belajar peserta didik."
                   :breadcrumb="['Fasilitas' => null]" />
@endsection

@section('content')
    <div class="mx-auto max-w-7xl px-4 sm:px-6 lg:px-8">
        @if ($facilities->isEmpty())
            <div class="card">
                <x-empty-state icon="building-2" title="Belum ada fasilitas"
                               description="Daftar fasilitas akan ditampilkan setelah ditambahkan panitia." />
            </div>
        @else
            <div class="grid gap-5 sm:grid-cols-2 lg:grid-cols-3">
                @foreach ($facilities as $facility)
                    <article class="card overflow-hidden">
                        @if ($facility->image_path)
                            <img src="{{ Storage::disk('public')->url($facility->image_path) }}" alt="{{ $facility->name }}"
                                 class="h-44 w-full object-cover" loading="lazy">
                        @else
                            <div class="flex h-44 items-center justify-center bg-brand-50 text-brand-300">
                                <x-icon :name="$facility->icon ?: 'building-2'" class="h-12 w-12" />
                            </div>
                        @endif

                        <div class="p-5">
                            <h2 class="font-semibold text-slate-900">{{ $facility->name }}</h2>
                            <p class="prose-content mt-2">{{ $facility->description }}</p>
                        </div>
                    </article>
                @endforeach
            </div>
        @endif
    </div>
@endsection
