<!DOCTYPE html>
<html lang="id" class="h-full">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <meta name="csrf-token" content="{{ csrf_token() }}">
    <meta name="robots" content="noindex, nofollow">
    <title>Login Panitia &mdash; {{ $settings->schoolName() }}</title>
    <link rel="icon" href="{{ asset('icons/icon-192.png') }}" type="image/png">
    @vite(['resources/css/app.css', 'resources/js/app.js'])
</head>
<body class="flex min-h-full items-center justify-center bg-slate-900 px-4 py-12">
    <main class="w-full max-w-sm">
        <div class="text-center">
            @if ($settings->logoUrl())
                <img src="{{ $settings->logoUrl() }}" alt="" class="mx-auto h-16 w-16 rounded-xl bg-white object-contain p-1.5">
            @else
                <span class="mx-auto flex h-16 w-16 items-center justify-center rounded-xl bg-brand-600 text-white">
                    <x-icon name="graduation-cap" class="h-9 w-9" />
                </span>
            @endif

            <h1 class="mt-5 text-lg font-bold text-white">Panel Panitia {{ $settings->admissionName() }}</h1>
            <p class="mt-1 text-sm text-slate-400">{{ $settings->schoolName() }}</p>
        </div>

        <form method="POST" action="{{ route('admin.login.store') }}" class="mt-8 rounded-xl bg-white p-6 shadow-xl sm:p-8">
            @csrf

            @if (session('success'))
                <x-alert type="success" class="mb-5">{{ session('success') }}</x-alert>
            @endif

            @if ($errors->any())
                <x-alert type="error" class="mb-5">{{ $errors->first() }}</x-alert>
            @endif

            <div class="space-y-5">
                <x-form.input name="username" label="Nama Pengguna atau Email" required
                              autocomplete="username" autofocus placeholder="superadmin" />

                <x-form.input name="password" type="password" label="Kata Sandi" required
                              autocomplete="current-password" placeholder="••••••••" />

                <x-form.checkbox name="remember" label="Ingat saya di perangkat ini" />
            </div>

            <button type="submit" class="btn-primary mt-6 w-full">
                <x-icon name="log-in" class="h-4 w-4" />
                Masuk
            </button>
        </form>

        <p class="mt-6 text-center text-xs text-slate-400">
            <a href="{{ route('home') }}" class="hover:text-slate-200">&larr; Kembali ke website</a>
        </p>
    </main>
</body>
</html>
