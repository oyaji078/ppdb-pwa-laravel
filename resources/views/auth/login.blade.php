{{--
    The single sign-in for everyone: applicants, verifiers, PPDB admins and
    super admins. The role decides where the login lands, so this page makes no
    distinction between them.
--}}
<!DOCTYPE html>
<html lang="id" class="h-full">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <meta name="csrf-token" content="{{ csrf_token() }}">
    <meta name="robots" content="noindex, nofollow">
    <title>Masuk &mdash; {{ $settings->schoolName() }}</title>
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

            <h1 class="mt-5 text-lg font-bold text-white">Masuk {{ $settings->admissionName() }}</h1>
            <p class="mt-1 text-sm text-slate-400">{{ $settings->schoolName() }}</p>
        </div>

        <form method="POST" action="{{ route('login.store') }}" class="mt-8 rounded-xl bg-white p-6 shadow-xl sm:p-8">
            @csrf

            @if (session('success'))
                <x-alert type="success" class="mb-5">{{ session('success') }}</x-alert>
            @endif

            @if (session('error'))
                <x-alert type="error" class="mb-5">{{ session('error') }}</x-alert>
            @endif

            @if ($errors->any())
                <x-alert type="error" class="mb-5">{{ $errors->first() }}</x-alert>
            @endif

            <div class="space-y-5">
                <x-form.input name="email" type="text" label="Email / Nama Pengguna" required
                              autocomplete="username" autofocus
                              placeholder="nama@email.com atau nama pengguna"
                              hint="Gunakan email atau nama pengguna (untuk panitia)." />

                <x-form.input name="password" type="password" label="Kata Sandi" required
                              autocomplete="current-password" placeholder="••••••••" />

                <x-form.checkbox name="remember" label="Ingat saya di perangkat ini" />
            </div>

            <button type="submit" class="btn-primary mt-6 w-full">
                <x-icon name="log-in" class="h-4 w-4" />
                Masuk
            </button>

            <p class="mt-6 border-t border-slate-100 pt-5 text-center text-sm text-slate-600">
                Calon peserta didik belum punya akun?
                <a href="{{ route('registration.start') }}" class="font-semibold text-brand-600 hover:text-brand-700">
                    Daftar sekarang
                </a>
            </p>
        </form>

        <p class="mt-6 text-center text-xs text-slate-400">
            <a href="{{ route('home') }}" class="hover:text-slate-200">&larr; Kembali ke website</a>
        </p>
    </main>
</body>
</html>
