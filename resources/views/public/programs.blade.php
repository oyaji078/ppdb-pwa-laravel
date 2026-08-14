@extends('layouts.public')

@section('title', 'Program Sekolah')

@section('hero')
    <x-page-header title="Program Sekolah"
                   subtitle="Program unggulan yang dapat diikuti peserta didik selama menempuh pendidikan."
                   :breadcrumb="['Program' => null]" />
@endsection

@section('content')
    <div class="mx-auto max-w-7xl px-4 sm:px-6 lg:px-8">
        @if ($programs->isEmpty())
            <div class="card">
                <x-empty-state icon="book-open" title="Belum ada program"
                               description="Program sekolah akan ditampilkan di sini setelah ditambahkan panitia." />
            </div>
        @else
            <div class="grid gap-5 md:grid-cols-2">
                @foreach ($programs as $program)
                    <article class="card overflow-hidden">
                        @if ($program->image_path)
                            <img src="{{ Storage::disk('public')->url($program->image_path) }}" alt=""
                                 class="h-44 w-full object-cover" loading="lazy">
                        @endif

                        <div class="p-6">
                            <div class="flex items-center gap-3">
                                <span class="flex h-11 w-11 shrink-0 items-center justify-center rounded-lg bg-brand-50 text-brand-600">
                                    <x-icon :name="$program->icon ?: 'book-open'" class="h-5 w-5" />
                                </span>
                                <h2 class="text-lg font-bold text-slate-900">{{ $program->name }}</h2>
                            </div>

                            <p class="prose-content mt-4">{{ $program->description ?: $program->excerpt }}</p>
                        </div>
                    </article>
                @endforeach
            </div>
        @endif
    </div>
@endsection
