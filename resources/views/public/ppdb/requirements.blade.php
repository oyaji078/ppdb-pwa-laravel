@extends('layouts.public')

@section('title', 'Persyaratan Berkas')

@section('hero')
    <x-page-header title="Persyaratan Berkas"
                   :subtitle="$academicYear ? 'Berkas yang harus diunggah untuk Tahun Ajaran '.$academicYear->name : null"
                   :breadcrumb="[$settings->admissionName() => route('ppdb.index'), 'Persyaratan' => null]" />
@endsection

@section('content')
    <div class="mx-auto max-w-4xl space-y-8 px-4 sm:px-6 lg:px-8">
        @if ($tracks->isEmpty())
            <div class="card">
                <x-empty-state icon="file-stack" title="Persyaratan belum tersedia"
                               description="Panitia belum menetapkan persyaratan berkas untuk tahun ajaran ini." />
            </div>
        @else
            <x-alert type="info" title="Ketentuan umum berkas">
                <ul class="mt-1 list-inside list-disc space-y-1">
                    <li>Berkas diunggah dalam format dan ukuran yang tertera pada masing-masing jenis berkas.</li>
                    <li>Pastikan hasil pindai atau foto terbaca jelas, tidak buram, dan tidak terpotong.</li>
                    <li>Berkas yang tidak terbaca akan dikembalikan panitia untuk diperbaiki.</li>
                </ul>
            </x-alert>

            @foreach ($tracks as $track)
                <section class="card overflow-hidden">
                    <div class="border-b border-slate-200 bg-slate-50 px-6 py-4">
                        <h2 class="font-bold text-slate-900">Jalur {{ $track->name }}</h2>
                        @if ($track->description)
                            <p class="mt-0.5 text-sm text-slate-600">{{ $track->description }}</p>
                        @endif
                    </div>

                    @if ($track->documentTypes->isEmpty())
                        <x-empty-state icon="file-x" title="Belum ada persyaratan"
                                       description="Panitia belum menetapkan berkas untuk jalur ini." />
                    @else
                        <ul class="divide-y divide-slate-100">
                            @foreach ($track->documentTypes as $type)
                                <li class="flex flex-col gap-2 px-6 py-4 sm:flex-row sm:items-start sm:justify-between">
                                    <div class="flex min-w-0 items-start gap-3">
                                        <span class="mt-0.5 flex h-8 w-8 shrink-0 items-center justify-center rounded-lg bg-brand-50 text-brand-600">
                                            <x-icon name="file-text" class="h-4 w-4" />
                                        </span>
                                        <div class="min-w-0">
                                            <p class="font-medium text-slate-900">{{ $type->name }}</p>
                                            @if ($type->description)
                                                <p class="prose-content mt-0.5">{{ $type->description }}</p>
                                            @endif
                                            <p class="mt-1 text-xs text-slate-500">
                                                Format {{ $type->extensionLabel() }} &middot; maksimal {{ $type->humanMaxSize() }}
                                            </p>
                                        </div>
                                    </div>

                                    @if ($type->pivot->is_required)
                                        <x-badge class="shrink-0 bg-rose-50 text-rose-700 ring-rose-200">Wajib</x-badge>
                                    @else
                                        <x-badge class="shrink-0 bg-slate-100 text-slate-600 ring-slate-200">Opsional</x-badge>
                                    @endif
                                </li>
                            @endforeach
                        </ul>
                    @endif
                </section>
            @endforeach
        @endif
    </div>
@endsection
