@extends('layouts.public')

@section('title', 'Profil Sekolah')

@section('hero')
    <x-page-header title="Profil Sekolah"
                   :subtitle="'Mengenal lebih dekat '.$settings->schoolName().'.'"
                   :breadcrumb="['Profil' => null]" />
@endsection

@section('content')
    <div class="mx-auto max-w-4xl px-4 sm:px-6 lg:px-8">
        @if ($sections->isEmpty())
            <div class="card">
                <x-empty-state icon="landmark" title="Profil belum tersedia"
                               description="Panitia belum melengkapi informasi profil sekolah." />
            </div>
        @else
            <div class="space-y-6">
                @foreach ($sections as $section)
                    <article class="card overflow-hidden">
                        @if ($section->image_path)
                            <img src="{{ Storage::disk('public')->url($section->image_path) }}" alt=""
                                 class="h-56 w-full object-cover" loading="lazy">
                        @endif

                        <div class="p-6">
                            <h2 class="text-lg font-bold text-slate-900">{{ $section->title }}</h2>

                            <div class="prose-content mt-3 space-y-2">
                                @foreach (preg_split('/\r\n|\r|\n/', (string) $section->content) as $line)
                                    @if (trim($line) !== '')
                                        <p>{{ $line }}</p>
                                    @endif
                                @endforeach
                            </div>
                        </div>
                    </article>
                @endforeach
            </div>
        @endif
    </div>
@endsection
