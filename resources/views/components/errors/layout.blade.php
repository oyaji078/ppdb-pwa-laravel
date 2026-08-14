@props(['code', 'title', 'message', 'icon' => 'triangle-alert'])

<!DOCTYPE html>
<html lang="id" class="h-full">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <meta name="robots" content="noindex, nofollow">
    <title>{{ $code }} &mdash; {{ $title }}</title>
    @vite(['resources/css/app.css', 'resources/js/app.js'])
</head>
<body class="flex min-h-full items-center justify-center bg-slate-50 px-4 py-12">
    <main class="w-full max-w-md text-center">
        <span class="mx-auto flex h-20 w-20 items-center justify-center rounded-full bg-brand-50 text-brand-600">
            <x-icon :name="$icon" class="h-10 w-10" />
        </span>

        <p class="mt-6 text-5xl font-bold tracking-tight text-slate-900">{{ $code }}</p>
        <h1 class="mt-2 text-lg font-semibold text-slate-800">{{ $title }}</h1>
        <p class="mt-3 text-sm leading-relaxed text-slate-600">{{ $message }}</p>

        <div class="mt-8 flex flex-wrap justify-center gap-3">
            <a href="{{ url()->previous() !== url()->current() ? url()->previous() : route('home') }}" class="btn-secondary">
                <x-icon name="arrow-left" class="h-4 w-4" />
                Kembali
            </a>
            <a href="{{ route('home') }}" class="btn-primary">
                <x-icon name="house" class="h-4 w-4" />
                Beranda
            </a>
        </div>
    </main>
</body>
</html>
