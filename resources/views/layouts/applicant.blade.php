<!DOCTYPE html>
<html lang="id" class="h-full">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1, viewport-fit=cover">
    <meta name="csrf-token" content="{{ csrf_token() }}">
    <meta name="robots" content="noindex, nofollow">
    <meta name="theme-color" content="#1d4ed8">

    <title>@yield('title', 'Dashboard') &mdash; Portal Pendaftar</title>

    <link rel="icon" href="{{ asset('icons/icon-192.png') }}" type="image/png">

    @vite(['resources/css/app.css', 'resources/js/app.js'])
</head>
<body class="h-full bg-slate-50">
    <a href="#konten" class="sr-only focus:not-sr-only focus:absolute focus:top-2 focus:left-2 focus:z-50 focus:rounded-lg focus:bg-brand-700 focus:px-4 focus:py-2 focus:text-sm focus:font-semibold focus:text-white">
        Lewati ke konten utama
    </a>

    <div x-data="{ sidebar: false }" class="min-h-full">
        {{-- Mobile drawer backdrop --}}
        <div x-show="sidebar" x-cloak @click="sidebar = false"
             class="fixed inset-0 z-40 bg-slate-900/50 lg:hidden" aria-hidden="true"></div>

        @include('partials.applicant-sidebar')

        <div class="lg:pl-64">
            @include('partials.applicant-topbar')

            <x-offline-banner />

            <main id="konten" class="px-4 py-6 sm:px-6 lg:px-8">
                <x-flash />
                @yield('content')
            </main>
        </div>
    </div>
</body>
</html>
