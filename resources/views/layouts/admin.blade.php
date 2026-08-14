<!DOCTYPE html>
<html lang="id" class="h-full">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1, viewport-fit=cover">
    <meta name="csrf-token" content="{{ csrf_token() }}">
    <meta name="robots" content="noindex, nofollow">
    <meta name="theme-color" content="#0f172a">

    <title>@yield('title', 'Dashboard') &mdash; Panel Admin</title>

    <link rel="icon" href="{{ asset('icons/icon-192.png') }}" type="image/png">

    @vite(['resources/css/app.css', 'resources/js/app.js'])
</head>
<body class="h-full bg-slate-100">
    <a href="#konten" class="sr-only focus:not-sr-only focus:absolute focus:top-2 focus:left-2 focus:z-50 focus:rounded-lg focus:bg-brand-700 focus:px-4 focus:py-2 focus:text-sm focus:font-semibold focus:text-white">
        Lewati ke konten utama
    </a>

    <div x-data="{ sidebar: false }" class="min-h-full">
        <div x-show="sidebar" x-cloak @click="sidebar = false"
             class="fixed inset-0 z-40 bg-slate-900/60 lg:hidden" aria-hidden="true"></div>

        @include('partials.admin-sidebar')

        <div class="lg:pl-64">
            @include('partials.admin-topbar')

            <x-offline-banner />

            <main id="konten" class="px-4 py-6 sm:px-6 lg:px-8">
                <div class="mb-5 flex flex-col gap-3 sm:flex-row sm:items-center sm:justify-between">
                    <div class="min-w-0">
                        <h1 class="truncate text-xl font-bold text-slate-900">@yield('heading', View::getSection('title', 'Dashboard'))</h1>
                        @hasSection('subheading')
                            <p class="mt-0.5 text-sm text-slate-500">@yield('subheading')</p>
                        @endif
                    </div>

                    @hasSection('actions')
                        <div class="flex shrink-0 flex-wrap items-center gap-2">@yield('actions')</div>
                    @endif
                </div>

                <x-flash />

                @yield('content')
            </main>
        </div>
    </div>
</body>
</html>
