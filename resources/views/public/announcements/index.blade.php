@extends('layouts.public')

@section('title', 'Pengumuman')

@section('hero')
    <x-page-header title="Pengumuman"
                   :subtitle="'Pengumuman resmi panitia '.$settings->admissionName().'.'"
                   :breadcrumb="['Pengumuman' => null]" />
@endsection

@section('content')
    <div class="mx-auto max-w-4xl px-4 sm:px-6 lg:px-8">
        @if ($announcements->isEmpty())
            <div class="card">
                <x-empty-state icon="megaphone" title="Belum ada pengumuman"
                               description="Pengumuman resmi akan ditampilkan di halaman ini." />
            </div>
        @else
            <ul class="space-y-4">
                @foreach ($announcements as $announcement)
                    <li class="card p-6">
                        <p class="text-xs text-slate-500">{{ $announcement->published_at?->translatedFormat('d F Y, H:i') }}</p>
                        <h2 class="mt-1.5 text-lg font-bold text-slate-900">
                            <a href="{{ route('announcements.show', $announcement) }}" class="hover:text-brand-700">
                                {{ $announcement->title }}
                            </a>
                        </h2>
                        <p class="prose-content mt-2">{{ Str::limit(strip_tags($announcement->content), 180) }}</p>
                        <a href="{{ route('announcements.show', $announcement) }}"
                           class="mt-4 inline-flex items-center gap-1 text-sm font-semibold text-brand-600 hover:text-brand-700">
                            Baca selengkapnya
                            <x-icon name="arrow-right" class="h-4 w-4" />
                        </a>
                    </li>
                @endforeach
            </ul>

            <div class="mt-8">{{ $announcements->links() }}</div>
        @endif
    </div>
@endsection
