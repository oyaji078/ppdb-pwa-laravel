@extends('layouts.public')

@section('title', 'Unduhan')

@section('hero')
    <x-page-header title="Unduhan"
                   subtitle="Berkas, formulir, dan panduan yang dapat diunduh calon peserta didik."
                   :breadcrumb="['Unduhan' => null]" />
@endsection

@section('content')
    <div class="mx-auto max-w-4xl px-4 sm:px-6 lg:px-8">
        @if ($downloads->isEmpty())
            <div class="card">
                <x-empty-state icon="download" title="Belum ada berkas"
                               description="Berkas unduhan akan tersedia di sini setelah diunggah panitia." />
            </div>
        @else
            <ul class="space-y-3">
                @foreach ($downloads as $download)
                    <li class="card flex flex-col gap-4 p-5 sm:flex-row sm:items-center sm:justify-between">
                        <div class="flex min-w-0 items-start gap-3">
                            <span class="flex h-10 w-10 shrink-0 items-center justify-center rounded-lg bg-brand-50 text-brand-600">
                                <x-icon name="file-text" class="h-5 w-5" />
                            </span>
                            <div class="min-w-0">
                                <h2 class="font-semibold text-slate-900">{{ $download->title }}</h2>
                                @if ($download->description)
                                    <p class="prose-content mt-1">{{ $download->description }}</p>
                                @endif
                                <p class="mt-1.5 text-xs text-slate-500">
                                    {{ Str::upper($download->extension) }} &middot; {{ $download->humanFileSize() }}
                                    &middot; {{ number_format($download->download_count, 0, ',', '.') }}x diunduh
                                </p>
                            </div>
                        </div>

                        <a href="{{ route('downloads.download', $download) }}" class="btn-secondary btn-sm shrink-0">
                            <x-icon name="download" class="h-4 w-4" />
                            Unduh
                        </a>
                    </li>
                @endforeach
            </ul>
        @endif
    </div>
@endsection
