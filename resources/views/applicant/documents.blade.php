@extends('layouts.applicant')

@section('title', 'Berkas')

@section('content')
    <div class="space-y-5">
        <x-alert type="info" title="Ketentuan berkas">
            Berkas yang sudah diverifikasi panitia tidak dapat diganti. Berkas yang diminta perbaikan
            dapat Anda unggah ulang langsung dari halaman ini.
        </x-alert>

        @if ($documentTypes->isEmpty())
            <div class="card">
                <x-empty-state icon="file-x" title="Belum ada persyaratan berkas"
                               description="Panitia belum menetapkan berkas untuk jalur pendaftaran Anda." />
            </div>
        @else
            <ul class="space-y-4">
                @foreach ($documentTypes as $type)
                    @php
                        $file = $uploaded[$type->id] ?? null;
                        $canUpload = $file === null || $file->verification_status->isReplaceable();
                    @endphp

                    <li class="card p-5 sm:p-6">
                        <div class="flex flex-wrap items-start justify-between gap-3">
                            <div class="min-w-0">
                                <div class="flex flex-wrap items-center gap-2">
                                    <h2 class="font-semibold text-slate-900">{{ $type->name }}</h2>
                                    @if ($type->pivot->is_required)
                                        <x-badge class="bg-rose-50 text-rose-700 ring-rose-200">Wajib</x-badge>
                                    @else
                                        <x-badge class="bg-slate-100 text-slate-600 ring-slate-200">Opsional</x-badge>
                                    @endif
                                </div>

                                @if ($type->description)
                                    <p class="prose-content mt-1">{{ $type->description }}</p>
                                @endif

                                <p class="mt-1 text-xs text-slate-500">
                                    Format {{ $type->extensionLabel() }} &middot; maksimal {{ $type->humanMaxSize() }}
                                </p>
                            </div>

                            @if ($file)
                                <x-badge :class="$file->verification_status->badge()">
                                    {{ Str::upper($file->verification_status->label()) }}
                                </x-badge>
                            @endif
                        </div>

                        @if ($file?->verification_note)
                            <x-alert :type="$file->verification_status->value === 'rejected' ? 'error' : 'warning'" class="mt-4"
                                     title="Catatan panitia">
                                {{ $file->verification_note }}
                            </x-alert>
                        @endif

                        @if ($file)
                            <div class="mt-4 flex flex-wrap items-center gap-3 rounded-lg bg-slate-50 p-3">
                                <x-icon :name="$file->isPdf() ? 'file-text' : 'image'" class="h-5 w-5 shrink-0 text-slate-400" />

                                <div class="min-w-0 flex-1">
                                    <p class="truncate text-sm font-medium text-slate-800">{{ $file->original_name }}</p>
                                    <p class="text-xs text-slate-500">
                                        {{ $file->humanFileSize() }} &middot; diunggah {{ $file->uploaded_at?->translatedFormat('d M Y, H:i') }}
                                        @if ($file->verified_at)
                                            &middot; diverifikasi {{ $file->verified_at->translatedFormat('d M Y') }}
                                        @endif
                                    </p>
                                </div>

                                <div class="flex shrink-0 gap-2">
                                    <a href="{{ route('applicant.documents.preview', $file) }}" target="_blank" rel="noopener" class="btn-secondary btn-sm">
                                        <x-icon name="eye" class="h-3.5 w-3.5" />
                                        Lihat
                                    </a>
                                    <a href="{{ route('applicant.documents.download', $file) }}" class="btn-secondary btn-sm">
                                        <x-icon name="download" class="h-3.5 w-3.5" />
                                        Unduh
                                    </a>
                                </div>
                            </div>
                        @endif

                        @if ($canUpload)
                            <form method="POST" action="{{ route('applicant.documents.store', $type) }}"
                                  enctype="multipart/form-data" class="mt-4 flex flex-col gap-3 sm:flex-row sm:items-end">
                                @csrf

                                <div class="min-w-0 flex-1">
                                    <label for="file-{{ $type->id }}" class="form-label">
                                        {{ $file ? 'Unggah ulang berkas' : 'Pilih berkas' }}
                                    </label>
                                    <input type="file" name="file" id="file-{{ $type->id }}" required
                                           accept="{{ collect($type->extensions())->map(fn ($e) => '.'.$e)->implode(',') }}"
                                           class="block w-full cursor-pointer rounded-lg text-sm text-slate-600 ring-1 ring-slate-300 ring-inset file:mr-3 file:cursor-pointer file:rounded-l-lg file:border-0 file:bg-slate-50 file:px-4 file:py-2.5 file:text-sm file:font-medium file:text-slate-700 hover:file:bg-slate-100">
                                </div>

                                <button type="submit" class="btn-primary shrink-0">
                                    <x-icon name="upload" class="h-4 w-4" />
                                    {{ $file ? 'Unggah Ulang' : 'Unggah' }}
                                </button>
                            </form>
                        @else
                            <p class="mt-4 flex items-center gap-2 rounded-lg bg-emerald-50 p-3 text-sm text-emerald-800">
                                <x-icon name="lock" class="h-4 w-4 shrink-0" />
                                Berkas ini sudah diverifikasi dan terkunci. Hubungi panitia bila perlu perubahan.
                            </p>
                        @endif
                    </li>
                @endforeach
            </ul>
        @endif
    </div>
@endsection
