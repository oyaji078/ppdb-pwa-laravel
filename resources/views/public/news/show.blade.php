@extends('layouts.public')

@section('title', $article->title)
@section('meta_description', Str::limit(strip_tags($article->excerpt ?: $article->content), 155))

@section('hero')
    <x-page-header :title="$article->title"
                   :breadcrumb="['Berita' => route('news.index'), $article->title => null]">
        <p class="mt-3 flex flex-wrap items-center gap-x-4 gap-y-1 text-sm text-slate-500">
            <span class="flex items-center gap-1.5">
                <x-icon name="calendar" class="h-4 w-4" />
                {{ $article->published_at?->translatedFormat('d F Y') }}
            </span>
            @if ($article->author)
                <span class="flex items-center gap-1.5">
                    <x-icon name="user" class="h-4 w-4" />
                    {{ $article->author->name }}
                </span>
            @endif
            <span class="flex items-center gap-1.5">
                <x-icon name="eye" class="h-4 w-4" />
                {{ number_format($article->views, 0, ',', '.') }}x dibaca
            </span>
        </p>
    </x-page-header>
@endsection

@section('content')
    <div class="mx-auto max-w-3xl px-4 sm:px-6 lg:px-8">
        <article class="card overflow-hidden">
            @if ($article->cover_path)
                <img src="{{ Storage::disk('public')->url($article->cover_path) }}" alt=""
                     class="max-h-96 w-full object-cover">
            @endif

            <div class="p-6 sm:p-8">
                @if ($article->excerpt)
                    <p class="border-l-4 border-brand-200 pl-4 text-base leading-relaxed font-medium text-slate-700">
                        {{ $article->excerpt }}
                    </p>
                @endif

                <div class="prose-content mt-6 space-y-3 text-base">
                    @foreach (preg_split('/\r\n|\r|\n/', $article->content) as $paragraph)
                        @if (trim($paragraph) !== '')
                            <p>{{ $paragraph }}</p>
                        @endif
                    @endforeach
                </div>
            </div>
        </article>

        @if ($related->isNotEmpty())
            <section class="mt-10">
                <h2 class="text-lg font-bold text-slate-900">Berita Lainnya</h2>
                <div class="mt-4 space-y-3">
                    @foreach ($related as $item)
                        <a href="{{ route('news.show', $item) }}" class="card flex items-center gap-4 p-4 transition-colors hover:bg-slate-50">
                            @if ($item->cover_path)
                                <img src="{{ Storage::disk('public')->url($item->cover_path) }}" alt=""
                                     class="h-16 w-20 shrink-0 rounded-lg object-cover" loading="lazy">
                            @endif
                            <div class="min-w-0">
                                <p class="text-xs text-slate-500">{{ $item->published_at?->translatedFormat('d F Y') }}</p>
                                <p class="mt-0.5 truncate text-sm font-semibold text-slate-900">{{ $item->title }}</p>
                            </div>
                        </a>
                    @endforeach
                </div>
            </section>
        @endif

        <a href="{{ route('news.index') }}" class="mt-8 inline-flex items-center gap-1.5 text-sm font-semibold text-brand-600 hover:text-brand-700">
            <x-icon name="arrow-left" class="h-4 w-4" />
            Kembali ke daftar berita
        </a>
    </div>
@endsection
