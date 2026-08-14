@extends('layouts.public')

@section('title', 'Tidak Ada Koneksi')

@section('content')
    <div class="mx-auto flex max-w-lg flex-col items-center px-4 py-16 text-center sm:px-6">
        <span class="flex h-20 w-20 items-center justify-center rounded-full bg-amber-50 text-amber-500">
            <x-icon name="wifi-off" class="h-10 w-10" />
        </span>

        <h1 class="mt-6 text-2xl font-bold text-slate-900">Anda sedang offline</h1>
        <p class="mt-3 text-sm text-slate-600">
            Halaman yang Anda tuju belum tersimpan di perangkat dan membutuhkan koneksi internet.
            Periksa koneksi Anda, lalu muat ulang halaman ini.
        </p>

        <div class="mt-8 flex flex-wrap justify-center gap-3">
            <button type="button" onclick="window.location.reload()" class="btn-primary">
                <x-icon name="refresh-cw" class="h-4 w-4" />
                Muat Ulang
            </button>
            <a href="{{ route('home') }}" class="btn-secondary">
                <x-icon name="house" class="h-4 w-4" />
                Ke Beranda
            </a>
        </div>

        <p class="mt-8 text-xs text-slate-500">
            Halaman publik yang pernah Anda buka tetap dapat diakses tanpa koneksi.
        </p>
    </div>
@endsection
