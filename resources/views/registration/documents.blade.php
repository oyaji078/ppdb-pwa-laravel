@php $stepKey = 'berkas'; @endphp

@extends('layouts.wizard')

@section('title', 'Pendaftaran - Berkas')

@section('wizard')
    <div class="card p-6 sm:p-8" data-documents>
        <h2 class="text-lg font-bold text-slate-900">Unggah Berkas</h2>
        <p class="mt-1 text-sm text-slate-600">
            Pilih seluruh berkas wajib untuk jalur <span class="font-semibold">{{ $registration->admissionTrack->name }}</span>,
            lalu tekan tombol di bawah sekali untuk mengunggah semuanya.
            Pastikan hasil pindai atau foto terbaca jelas.
        </p>

        @if ($documentTypes->isEmpty())
            <x-empty-state icon="file-x" title="Belum ada persyaratan berkas"
                           description="Panitia belum menetapkan berkas untuk jalur ini. Anda dapat melanjutkan ke langkah berikutnya." />
        @else
            <ul class="mt-6 space-y-4">
                @foreach ($documentTypes as $type)
                    @php $file = $uploaded[$type->id] ?? null; @endphp

                    <li class="rounded-lg border border-slate-200 p-4 sm:p-5" data-document-row>
                        <div class="flex flex-wrap items-start justify-between gap-3">
                            <div class="min-w-0">
                                <div class="flex flex-wrap items-center gap-2">
                                    <h3 class="font-semibold text-slate-900">{{ $type->name }}</h3>
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
                                <x-badge class="shrink-0 bg-emerald-50 text-emerald-700 ring-emerald-200" icon="circle-check">
                                    Terunggah
                                </x-badge>
                            @endif
                        </div>

                        @if ($file)
                            <div class="mt-4 flex flex-wrap items-center gap-3 rounded-lg bg-slate-50 p-3">
                                <x-icon :name="$file->isPdf() ? 'file-text' : 'image'" class="h-5 w-5 shrink-0 text-slate-400" />
                                <div class="min-w-0 flex-1">
                                    <p class="truncate text-sm font-medium text-slate-800">{{ $file->original_name }}</p>
                                    <p class="text-xs text-slate-500">
                                        {{ $file->humanFileSize() }} &middot; diunggah {{ $file->uploaded_at?->translatedFormat('d M Y, H:i') }}
                                    </p>
                                </div>

                                <div class="flex shrink-0 gap-2">
                                    <a href="{{ route('registration.documents.preview', $file) }}" target="_blank" rel="noopener"
                                       class="btn-secondary btn-sm">
                                        <x-icon name="eye" class="h-3.5 w-3.5" />
                                        Lihat
                                    </a>

                                    <form method="POST" action="{{ route('registration.documents.destroy', $file) }}"
                                          onsubmit="return confirm('Hapus berkas {{ addslashes($type->name) }}?')">
                                        @csrf
                                        @method('DELETE')
                                        <button type="submit" class="btn-secondary btn-sm text-rose-600">
                                            <x-icon name="trash-2" class="h-3.5 w-3.5" />
                                            Hapus
                                        </button>
                                    </form>
                                </div>
                            </div>
                        @endif

                        {{-- One form per document. The page's script posts each of
                             them in turn behind a single button; left alone, they
                             still work as ordinary one-at-a-time uploads. --}}
                        <form method="POST" action="{{ route('registration.documents.store', $type) }}"
                              enctype="multipart/form-data" data-document-form
                              class="mt-4 flex flex-col gap-3 sm:flex-row sm:items-end">
                            @csrf

                            <div class="min-w-0 flex-1">
                                <label for="file-{{ $type->id }}" class="form-label">
                                    {{ $file ? 'Ganti berkas' : 'Pilih berkas' }}
                                </label>
                                <input type="file" name="file" id="file-{{ $type->id }}" required
                                       accept="{{ collect($type->extensions())->map(fn ($e) => '.'.$e)->implode(',') }}"
                                       class="block w-full cursor-pointer rounded-lg text-sm text-slate-600 ring-1 ring-slate-300 ring-inset file:mr-3 file:cursor-pointer file:rounded-l-lg file:border-0 file:bg-slate-50 file:px-4 file:py-2.5 file:text-sm file:font-medium file:text-slate-700 hover:file:bg-slate-100">
                            </div>

                            <button type="submit" class="btn-primary shrink-0" data-document-submit>
                                <x-icon name="upload" class="h-4 w-4" />
                                Unggah
                            </button>
                        </form>

                        <p data-document-status hidden class="mt-2 text-xs text-slate-500" aria-live="polite"></p>
                    </li>
                @endforeach
            </ul>
        @endif

        <p data-documents-summary hidden aria-live="polite"></p>

        <div class="mt-8 flex flex-col gap-3 border-t border-slate-100 pt-6 sm:flex-row sm:justify-between">
            <a href="{{ route('registration.program') }}" class="btn-secondary">
                <x-icon name="arrow-left" class="h-4 w-4" />
                Kembali
            </a>
            <a href="{{ route('registration.review') }}" class="btn-primary" data-documents-continue>
                <span data-documents-label>Lanjut ke Review</span>
                <x-icon name="arrow-right" class="h-4 w-4" />
            </a>
        </div>
    </div>
@endsection
