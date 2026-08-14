@props(['title', 'subtitle' => null, 'breadcrumb' => []])

<section class="border-b border-slate-200 bg-white">
    <div class="mx-auto max-w-7xl px-4 py-10 sm:px-6 lg:px-8">
        @if ($breadcrumb !== [])
            <nav aria-label="Remah roti" class="mb-3">
                <ol class="flex flex-wrap items-center gap-1.5 text-xs text-slate-500">
                    <li><a href="{{ route('home') }}" class="hover:text-brand-700">Beranda</a></li>
                    @foreach ($breadcrumb as $label => $url)
                        <li aria-hidden="true">/</li>
                        <li>
                            @if (is_string($url) && $url !== '')
                                <a href="{{ $url }}" class="hover:text-brand-700">{{ $label }}</a>
                            @else
                                <span class="font-medium text-slate-700">{{ is_int($label) ? $url : $label }}</span>
                            @endif
                        </li>
                    @endforeach
                </ol>
            </nav>
        @endif

        <h1 class="text-2xl font-bold text-slate-900 sm:text-3xl">{{ $title }}</h1>

        @if ($subtitle)
            <p class="mt-2 max-w-3xl text-sm text-slate-600 sm:text-base">{{ $subtitle }}</p>
        @endif

        {{ $slot }}
    </div>
</section>
