@extends('layouts.applicant')

@section('title', 'Pengumuman')

@section('content')
    @if ($announcements->isEmpty())
        <div class="card">
            <x-empty-state icon="megaphone" title="Belum ada pengumuman"
                           description="Pengumuman dari panitia akan tampil di halaman ini." />
        </div>
    @else
        <ul class="space-y-4">
            @foreach ($announcements as $announcement)
                <li class="card p-5 sm:p-6">
                    <div class="flex flex-wrap items-center gap-2">
                        <x-badge :class="$announcement->audience->badge()">{{ $announcement->audience->label() }}</x-badge>
                        <span class="text-xs text-slate-500">{{ $announcement->published_at?->translatedFormat('d F Y, H:i') }}</span>
                    </div>

                    <h2 class="mt-2 font-semibold text-slate-900">
                        <a href="{{ route('applicant.announcements.show', $announcement) }}" class="hover:text-brand-700">
                            {{ $announcement->title }}
                        </a>
                    </h2>

                    <p class="prose-content mt-1.5">{{ Str::limit(strip_tags($announcement->content), 160) }}</p>

                    <a href="{{ route('applicant.announcements.show', $announcement) }}"
                       class="mt-3 inline-flex items-center gap-1 text-sm font-semibold text-brand-600 hover:text-brand-700">
                        Baca selengkapnya
                        <x-icon name="arrow-right" class="h-4 w-4" />
                    </a>
                </li>
            @endforeach
        </ul>

        <div class="mt-6">{{ $announcements->links() }}</div>
    @endif
@endsection
