@extends('layouts.public')

@section('title', $announcement->title)

@section('hero')
    <x-page-header :title="$announcement->title"
                   :breadcrumb="['Pengumuman' => route('announcements.index'), $announcement->title => null]">
        <p class="mt-3 flex items-center gap-1.5 text-sm text-slate-500">
            <x-icon name="calendar" class="h-4 w-4" />
            {{ $announcement->published_at?->translatedFormat('d F Y, H:i') }}
        </p>
    </x-page-header>
@endsection

@section('content')
    <div class="mx-auto max-w-3xl px-4 sm:px-6 lg:px-8">
        <article class="card p-6 sm:p-8">
            <div class="prose-content space-y-3 text-base">
                @foreach (preg_split('/\r\n|\r|\n/', $announcement->content) as $paragraph)
                    @if (trim($paragraph) !== '')
                        <p>{{ $paragraph }}</p>
                    @endif
                @endforeach
            </div>
        </article>

        <a href="{{ route('announcements.index') }}" class="mt-8 inline-flex items-center gap-1.5 text-sm font-semibold text-brand-600 hover:text-brand-700">
            <x-icon name="arrow-left" class="h-4 w-4" />
            Kembali ke pengumuman
        </a>
    </div>
@endsection
