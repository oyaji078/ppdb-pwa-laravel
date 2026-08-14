@extends('layouts.applicant')

@section('title', $announcement->title)

@section('content')
    <article class="card p-6 sm:p-8">
        <div class="flex flex-wrap items-center gap-2">
            <x-badge :class="$announcement->audience->badge()">{{ $announcement->audience->label() }}</x-badge>
            <span class="text-xs text-slate-500">{{ $announcement->published_at?->translatedFormat('d F Y, H:i') }}</span>
        </div>

        <h1 class="mt-3 text-xl font-bold text-slate-900">{{ $announcement->title }}</h1>

        <div class="prose-content mt-5 space-y-3 text-base">
            @foreach (preg_split('/\r\n|\r|\n/', $announcement->content) as $paragraph)
                @if (trim($paragraph) !== '')
                    <p>{{ $paragraph }}</p>
                @endif
            @endforeach
        </div>
    </article>

    <a href="{{ route('applicant.announcements.index') }}"
       class="mt-6 inline-flex items-center gap-1.5 text-sm font-semibold text-brand-600 hover:text-brand-700">
        <x-icon name="arrow-left" class="h-4 w-4" />
        Kembali ke pengumuman
    </a>
@endsection
