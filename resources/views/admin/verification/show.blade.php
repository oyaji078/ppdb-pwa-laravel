@extends('layouts.admin')

@section('title', 'Verifikasi Berkas')
@section('heading', $applicant->full_name)
@section('subheading', $registration->registration_number.' · '.$registration->admissionTrack->name)

@section('actions')
    <a href="{{ route('admin.verification.index') }}" class="btn-secondary btn-sm">
        <x-icon name="arrow-left" class="h-4 w-4" />
        Kembali
    </a>
    <a href="{{ route('admin.registrations.show', $registration) }}" class="btn-secondary btn-sm">
        <x-icon name="user-round" class="h-4 w-4" />
        Detail Pendaftar
    </a>
@endsection

@section('content')
    @php $uploaded = $registration->documents->keyBy('document_type_id'); @endphp

    <div class="grid gap-5 lg:grid-cols-3">
        <div class="space-y-5 lg:col-span-2">
            @if ($documentTypes->isEmpty())
                <div class="card">
                    <x-empty-state icon="file-x" title="Tidak ada persyaratan berkas"
                                   description="Jalur ini belum memiliki persyaratan berkas." />
                </div>
            @endif

            @foreach ($documentTypes as $type)
                @php $file = $uploaded[$type->id] ?? null; @endphp

                <section class="card overflow-hidden">
                    <div class="flex flex-wrap items-start justify-between gap-3 border-b border-slate-100 px-5 py-4">
                        <div class="min-w-0">
                            <div class="flex flex-wrap items-center gap-2">
                                <h2 class="font-semibold text-slate-900">{{ $type->name }}</h2>
                                @if ($type->pivot->is_required)
                                    <x-badge class="bg-rose-50 text-rose-700 ring-rose-200">Wajib</x-badge>
                                @else
                                    <x-badge class="bg-slate-100 text-slate-600 ring-slate-200">Opsional</x-badge>
                                @endif
                            </div>
                            @if ($file)
                                <p class="mt-1 truncate text-xs text-slate-500">
                                    {{ $file->original_name }} &middot; {{ $file->humanFileSize() }}
                                    &middot; diunggah {{ $file->uploaded_at?->translatedFormat('d M Y, H:i') }}
                                </p>
                            @endif
                        </div>

                        @if ($file)
                            <x-badge :class="$file->verification_status->badge()">
                                {{ $file->verification_status->label() }}
                            </x-badge>
                        @endif
                    </div>

                    @if ($file === null)
                        <x-empty-state icon="file-plus" title="Berkas belum diunggah"
                                       description="Pendaftar belum mengunggah berkas ini." class="py-10" />
                    @else
                        {{-- Preview --}}
                        <div class="border-b border-slate-100 bg-slate-50 p-4">
                            @if ($file->isImage())
                                <img src="{{ route('admin.documents.preview', $file) }}" alt="Pratinjau {{ $type->name }}"
                                     class="mx-auto max-h-96 rounded-lg bg-white object-contain shadow-sm">
                            @else
                                <div class="flex flex-col items-center gap-3 py-8">
                                    <x-icon name="file-text" class="h-12 w-12 text-slate-300" />
                                    <p class="text-sm text-slate-600">Pratinjau PDF terbuka pada tab baru.</p>
                                </div>
                            @endif

                            <div class="mt-3 flex justify-center gap-2">
                                <a href="{{ route('admin.documents.preview', $file) }}" target="_blank" rel="noopener"
                                   class="btn-secondary btn-sm">
                                    <x-icon name="external-link" class="h-3.5 w-3.5" />
                                    Buka Berkas
                                </a>
                                <a href="{{ route('admin.documents.download', $file) }}" class="btn-secondary btn-sm">
                                    <x-icon name="download" class="h-3.5 w-3.5" />
                                    Unduh
                                </a>
                            </div>
                        </div>

                        {{-- Decision form --}}
                        <form method="POST" action="{{ route('admin.verification.decide', $file) }}"
                              class="space-y-4 p-5" x-data="{ status: '{{ old('verification_status', $file->verification_status->value) }}' }">
                            @csrf

                            <fieldset>
                                <legend class="form-label">Keputusan Verifikasi</legend>

                                <div class="mt-2 grid gap-2 sm:grid-cols-3">
                                    @foreach ($statusOptions as $value => $label)
                                        <label class="flex cursor-pointer items-center gap-2.5 rounded-lg border border-slate-200 p-3 transition-colors has-checked:border-brand-500 has-checked:bg-brand-50/60 hover:bg-slate-50">
                                            <input type="radio" name="verification_status" value="{{ $value }}"
                                                   x-model="status" required
                                                   class="h-4 w-4 border-slate-300 text-brand-600 focus:ring-brand-600">
                                            <span class="text-sm font-medium text-slate-800">{{ $label }}</span>
                                        </label>
                                    @endforeach
                                </div>
                            </fieldset>

                            <div x-show="status === 'revision_required' || status === 'rejected'" x-cloak x-collapse>
                                <x-form.textarea :name="'verification_note'" :id="'note-'.$file->id"
                                                 label="Catatan untuk Pendaftar" rows="3"
                                                 :value="$file->verification_note"
                                                 placeholder="Contoh: File ijazah terlalu buram. Silakan unggah ulang dengan hasil pindai yang jelas."
                                                 hint="Catatan wajib diisi bila meminta perbaikan atau menolak berkas. Pendaftar akan melihat catatan ini." />
                            </div>

                            <button type="submit" class="btn-primary">
                                <x-icon name="check" class="h-4 w-4" />
                                Simpan Keputusan
                            </button>
                        </form>

                        {{-- Audit trail --}}
                        @if ($file->verificationLogs->isNotEmpty())
                            <details class="border-t border-slate-100 px-5 py-3">
                                <summary class="cursor-pointer text-xs font-semibold text-slate-600">
                                    Riwayat verifikasi ({{ $file->verificationLogs->count() }})
                                </summary>
                                <ul class="mt-3 space-y-2">
                                    @foreach ($file->verificationLogs->sortByDesc('created_at') as $log)
                                        <li class="text-xs text-slate-600">
                                            <span class="font-medium text-slate-800">{{ $log->admin?->name ?? 'Sistem' }}</span>
                                            mengubah status
                                            @if ($log->old_status)
                                                dari <span class="font-medium">{{ $log->old_status->label() }}</span>
                                            @endif
                                            menjadi <span class="font-medium">{{ $log->new_status->label() }}</span>
                                            &middot; {{ $log->created_at?->translatedFormat('d M Y, H:i') }}
                                            @if ($log->note)
                                                <span class="mt-0.5 block text-slate-500">"{{ $log->note }}"</span>
                                            @endif
                                        </li>
                                    @endforeach
                                </ul>
                            </details>
                        @endif
                    @endif
                </section>
            @endforeach
        </div>

        {{-- Sidebar --}}
        <div class="space-y-5">
            <section class="card p-5">
                <h2 class="font-semibold text-slate-900">Status Pendaftaran</h2>
                <x-badge :class="$registration->registration_status->badge()" class="mt-2">
                    {{ $registration->registration_status->label() }}
                </x-badge>

                @if ($blockers === [])
                    <x-alert type="success" class="mt-4">
                        Semua syarat verifikasi terpenuhi. Pendaftaran siap dinyatakan terverifikasi.
                    </x-alert>

                    @can('completeVerification', $registration)
                        @unless ($registration->isVerified())
                            <form method="POST" action="{{ route('admin.verification.complete', $registration) }}" class="mt-4"
                                  onsubmit="return confirm('Nyatakan pendaftaran ini terverifikasi?')">
                                @csrf
                                <button type="submit" class="btn-primary w-full">
                                    <x-icon name="circle-check" class="h-4 w-4" />
                                    Selesaikan Verifikasi
                                </button>
                            </form>
                        @endunless
                    @endcan
                @else
                    <x-alert type="warning" class="mt-4" title="Belum dapat diselesaikan">
                        <ul class="mt-1 list-inside list-disc space-y-0.5">
                            @foreach ($blockers as $blocker)
                                <li>{{ $blocker }}</li>
                            @endforeach
                        </ul>
                    </x-alert>
                @endif

                @if ($registration->isVerified())
                    <div class="mt-4 rounded-lg bg-emerald-50 p-3 text-sm text-emerald-800">
                        Terverifikasi {{ $registration->verified_at?->translatedFormat('d M Y, H:i') }}
                        @if ($registration->verifier)
                            oleh {{ $registration->verifier->name }}
                        @endif
                    </div>

                    @can('reopenVerification', $registration)
                        <form method="POST" action="{{ route('admin.verification.reopen', $registration) }}" class="mt-4 space-y-3"
                              onsubmit="return confirm('Buka kembali verifikasi pendaftaran ini?')">
                            @csrf
                            <x-form.textarea name="reason" label="Alasan Membuka Kembali" required rows="2" />
                            <button type="submit" class="btn-secondary btn-sm w-full">
                                <x-icon name="rotate-ccw" class="h-3.5 w-3.5" />
                                Buka Kembali Verifikasi
                            </button>
                        </form>
                    @endcan
                @endif
            </section>

            <x-review-section title="Ringkasan Pendaftar">
                <x-review-item label="NISN" :value="$applicant->nisn" />
                <x-review-item label="Jenis Kelamin" :value="$applicant->genderLabel()" />
                <x-review-item label="Tempat, Tanggal Lahir" :value="$applicant->birthInfo()" wide />
                <x-review-item label="Asal Sekolah" :value="$applicant->previousSchool?->school_name" wide />
                <x-review-item label="Program" :value="$registration->program?->name" wide />
                <x-review-item label="Nomor HP" :value="$applicant->phone" />
                <x-review-item label="Gelombang" :value="$registration->wave->name" />
            </x-review-section>
        </div>
    </div>
@endsection
