<!DOCTYPE html>
<html lang="id" class="h-full">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1, viewport-fit=cover">
    <meta name="csrf-token" content="{{ csrf_token() }}">
    <meta name="description" content="@yield('meta_description', $settings->admissionName().' '.$settings->schoolName().' - pendaftaran online, jadwal, persyaratan, dan pengumuman.')">
    <meta name="theme-color" content="#1d4ed8">

    <title>@yield('title', 'Beranda') &mdash; {{ $settings->schoolName() }}</title>

    <link rel="manifest" href="{{ route('pwa.manifest') }}">
    <link rel="icon" href="{{ asset('icons/icon-192.png') }}" type="image/png">
    <link rel="apple-touch-icon" href="{{ asset('icons/icon-192.png') }}">

    @vite(['resources/css/app.css', 'resources/js/app.js'])
</head>
<body class="flex min-h-full flex-col">
    <a href="#konten" class="sr-only focus:not-sr-only focus:absolute focus:top-2 focus:left-2 focus:z-50 focus:rounded-lg focus:bg-brand-700 focus:px-4 focus:py-2 focus:text-sm focus:font-semibold focus:text-white">
        Lewati ke konten utama
    </a>

    <x-offline-banner />

    @include('partials.public-navbar')

    <main id="konten" class="flex-1">
        @hasSection('hero')
            @yield('hero')
        @endif

        @if (! View::hasSection('bare'))
            <div class="mx-auto w-full max-w-7xl px-4 py-8 sm:px-6 lg:px-8">
                <x-flash />
            </div>
        @endif

        @yield('content')
    </main>

    @include('partials.public-footer')
</body>
</html>
