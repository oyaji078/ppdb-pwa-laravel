@extends('layouts.admin')

@section('title', 'Detail Pendaftar')
@section('heading', $applicant->full_name)
@section('subheading', 'Nomor pendaftaran '.$registration->registration_number)

@section('actions')
    <a href="{{ route('admin.registrations.index') }}" class="btn-secondary btn-sm">
        <x-icon name="arrow-left" class="h-4 w-4" />
        Kembali
    </a>
    <a href="{{ route('admin.registrations.receipt', $registration) }}" class="btn-secondary btn-sm">
        <x-icon name="download" class="h-4 w-4" />
        Bukti Pendaftaran
    </a>
    @can('verify', $registration)
        <a href="{{ route('admin.verification.show', $registration) }}" class="btn-primary btn-sm">
            <x-icon name="file-check" class="h-4 w-4" />
            Verifikasi Berkas
        </a>
    @endcan
@endsection

@section('content')
    <div class="space-y-5">
        {{-- Status strip --}}
        <section class="card grid gap-4 p-5 sm:grid-cols-3">
            <div>
                <p class="text-xs font-medium text-slate-500">Status Pendaftaran</p>
                <x-badge :class="$registration->registration_status->badge()" class="mt-1.5">
                    {{ $registration->registration_status->label() }}
                </x-badge>
            </div>
            <div>
                <p class="text-xs font-medium text-slate-500">Status Seleksi</p>
                @if ($registration->selectionResult)
                    <x-badge :class="$registration->selectionResult->status->badge()" class="mt-1.5">
                        {{ $registration->selectionResult->status->label() }}
                    </x-badge>
                    <span class="mt-1 block text-xs text-slate-500">
                        {{ $registration->selectionResult->isPublished() ? 'Sudah dipublikasikan' : 'Draft, belum dipublikasikan' }}
                    </span>
                @else
                    <x-badge class="mt-1.5 bg-slate-100 text-slate-600 ring-slate-200">Belum diseleksi</x-badge>
                @endif
            </div>
            <div>
                <p class="text-xs font-medium text-slate-500">Daftar Ulang</p>
                <x-badge :class="$registration->reregistration_status->badge()" class="mt-1.5">
                    {{ $registration->reregistration_status->label() }}
                </x-badge>
            </div>
        </section>

        <div class="grid gap-5 lg:grid-cols-3">
            <div class="space-y-5 lg:col-span-2">
                <x-review-section title="Data Pendaftaran">
                    <x-review-item label="Nomor Pendaftaran" :value="$registration->registration_number" />
                    <x-review-item label="Tahun Ajaran" :value="$registration->academicYear->name" />
                    <x-review-item label="Gelombang" :value="$registration->wave->name" />
                    <x-review-item label="Jalur" :value="$registration->admissionTrack->name" />
                    <x-review-item label="Program" :value="$registration->program?->name" />
                    <x-review-item label="Tanggal Kirim"
                                   :value="$registration->submitted_at?->translatedFormat('d F Y, H:i').' WITA'" />
                    @if ($registration->verified_at)
                        <x-review-item label="Diverifikasi"
                                       :value="$registration->verified_at->translatedFormat('d F Y, H:i').' oleh '.($registration->verifier?->name ?? '-')" wide />
                    @endif
                </x-review-section>

                <x-review-section title="Biodata">
                    <x-review-item label="Nama Lengkap" :value="$applicant->full_name" wide />
                    <x-review-item label="NISN" :value="$applicant->nisn" />
                    <x-review-item label="NIK"
                                   :value="$canSeeSensitive ? $applicant->nik : mask_identity_number($applicant->nik)" />
                    <x-review-item label="Nomor Kartu Keluarga"
                                   :value="$canSeeSensitive ? $applicant->family_card_number : mask_identity_number($applicant->family_card_number)" />
                    <x-review-item label="Jenis Kelamin" :value="$applicant->genderLabel()" />
                    <x-review-item label="Tempat, Tanggal Lahir" :value="$applicant->birthInfo()" />
                    <x-review-item label="Agama" :value="$applicant->religion" />
                    <x-review-item label="Anak Ke / Jumlah Saudara"
                                   :value="($applicant->child_order ?? '-').' / '.($applicant->siblings_count ?? '-')" />
                    <x-review-item label="Nomor HP" :value="$applicant->phone" />
                    <x-review-item label="Email" :value="$applicant->email" />
                    <x-review-item label="Alamat" :value="$applicant->address?->fullAddress()" wide />
                </x-review-section>

                <x-review-section title="Orang Tua / Wali">
                    @forelse ($applicant->parentGuardians as $parent)
                        <x-review-item :label="$parent->relationshipLabel()"
                                       :value="$parent->name.($parent->occupation ? ' — '.$parent->occupation : '').($parent->phone ? ' ('.$parent->phone.')' : '')"
                                       wide />
                    @empty
                        <x-review-item label="Data" value="Belum diisi" wide />
                    @endforelse
                </x-review-section>

                <x-review-section title="Asal Sekolah">
                    <x-review-item label="Nama Sekolah" :value="$applicant->previousSchool?->school_name" wide />
                    <x-review-item label="Jenis / Status"
                                   :value="$applicant->previousSchool ? trim($applicant->previousSchool->school_type.' / '.$applicant->previousSchool->school_status, ' /') : null" />
                    <x-review-item label="NPSN" :value="$applicant->previousSchool?->npsn" />
                    <x-review-item label="Tahun Lulus" :value="$applicant->previousSchool?->graduation_year" />
                    <x-review-item label="Kabupaten/Kota" :value="$applicant->previousSchool?->regency" />
                </x-review-section>
            </div>

            <div class="space-y-5">
                {{-- Berkas --}}
                <section class="card overflow-hidden">
                    <h2 class="border-b border-slate-100 px-5 py-4 font-semibold text-slate-900">Berkas</h2>

                    @php $uploaded = $registration->documents->keyBy('document_type_id'); @endphp

                    @if ($documentTypes->isEmpty())
                        <x-empty-state icon="file-x" title="Tidak ada persyaratan berkas" class="py-8" />
                    @else
                        <ul class="divide-y divide-slate-100">
                            @foreach ($documentTypes as $type)
                                @php $file = $uploaded[$type->id] ?? null; @endphp

                                <li class="px-5 py-3.5">
                                    <div class="flex items-start justify-between gap-3">
                                        <div class="min-w-0">
                                            <p class="truncate text-sm font-medium text-slate-900">{{ $type->name }}</p>
                                            <p class="truncate text-xs text-slate-500">
                                                {{ $file ? $file->original_name : 'Belum diunggah' }}
                                            </p>
                                        </div>

                                        @if ($file)
                                            <x-badge :class="$file->verification_status->badge()">
                                                {{ $file->verification_status->label() }}
                                            </x-badge>
                                        @elseif ($type->pivot->is_required)
                                            <x-badge class="bg-rose-50 text-rose-700 ring-rose-200">Wajib</x-badge>
                                        @endif
                                    </div>

                                    @if ($file)
                                        <div class="mt-2 flex gap-2">
                                            <a href="{{ route('admin.documents.preview', $file) }}" target="_blank" rel="noopener"
                                               class="btn-secondary btn-sm">
                                                <x-icon name="eye" class="h-3.5 w-3.5" />
                                                Lihat
                                            </a>
                                            <a href="{{ route('admin.documents.download', $file) }}" class="btn-secondary btn-sm">
                                                <x-icon name="download" class="h-3.5 w-3.5" />
                                                Unduh
                                            </a>
                                        </div>
                                    @endif
                                </li>
                            @endforeach
                        </ul>
                    @endif
                </section>

                {{-- Reset kode akses --}}
                @can('resetAccessCode', $registration)
                    <section class="card p-5">
                        <h2 class="font-semibold text-slate-900">Reset Kode Akses</h2>
                        <p class="prose-content mt-1.5">
                            Kode akses lama tidak dapat dilihat siapa pun. Reset akan menerbitkan kode baru,
                            menghentikan seluruh sesi pendaftar, dan mencatat aksi ini pada activity log.
                        </p>

                        @if (session()->has('ppdb_access_code_recovery'))
                            <a href="{{ route('admin.registrations.recovery', $registration) }}" class="btn-primary btn-sm mt-3 w-full">
                                <x-icon name="file-down" class="h-3.5 w-3.5" />
                                Unduh Dokumen Pemulihan
                            </a>
                        @endif

                        <form method="POST" action="{{ route('admin.registrations.reset-code', $registration) }}"
                              class="mt-4 space-y-3"
                              onsubmit="return confirm('Reset kode akses pendaftar ini? Kode lama akan langsung tidak berlaku.')">
                            @csrf
                            <x-form.textarea name="reason" label="Alasan Reset" required rows="2"
                                             placeholder="Contoh: Pendaftar kehilangan kode akses dan telah memverifikasi identitas." />
                            <button type="submit" class="btn-danger btn-sm w-full">
                                <x-icon name="key-round" class="h-3.5 w-3.5" />
                                Reset Kode Akses
                            </button>
                        </form>
                    </section>
                @endcan

                {{-- Riwayat notifikasi --}}
                <section class="card overflow-hidden">
                    <h2 class="border-b border-slate-100 px-5 py-4 font-semibold text-slate-900">Riwayat Notifikasi</h2>

                    @if ($registration->notifications->isEmpty())
                        <x-empty-state icon="bell-off" title="Belum ada notifikasi" class="py-8" />
                    @else
                        <ul class="divide-y divide-slate-100">
                            @foreach ($registration->notifications->sortByDesc('created_at')->take(8) as $notification)
                                <li class="px-5 py-3">
                                    <p class="text-sm font-medium text-slate-900">{{ $notification->title }}</p>
                                    <p class="mt-0.5 text-xs text-slate-500">{{ $notification->created_at?->translatedFormat('d M Y, H:i') }}</p>
                                </li>
                            @endforeach
                        </ul>
                    @endif
                </section>

                @can('delete', $registration)
                    <section class="card border-rose-200 p-5 ring-rose-200">
                        <h2 class="font-semibold text-rose-800">Arsipkan Pendaftar</h2>
                        <p class="prose-content mt-1.5">
                            Pendaftar dipindahkan ke arsip (soft delete) dan tidak muncul pada daftar maupun laporan.
                            Data tetap tersimpan dan dapat dipulihkan.
                        </p>

                        <form method="POST" action="{{ route('admin.registrations.destroy', $registration) }}" class="mt-3"
                              onsubmit="return confirm('Arsipkan pendaftaran {{ $registration->registration_number }}?')">
                            @csrf
                            @method('DELETE')
                            <button type="submit" class="btn-danger btn-sm w-full">
                                <x-icon name="archive" class="h-3.5 w-3.5" />
                                Arsipkan
                            </button>
                        </form>
                    </section>
                @endcan
            </div>
        </div>
    </div>
@endsection
