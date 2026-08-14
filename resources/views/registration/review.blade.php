@php $stepKey = 'review'; @endphp

@extends('layouts.wizard')

@section('title', 'Pendaftaran - Review')

@section('wizard')
    @php
        $address = $applicant->address;
        $school = $applicant->previousSchool;
        $uploaded = $registration->documents->keyBy('document_type_id');
    @endphp

    <div class="space-y-5">
        @if ($blockers !== [])
            <x-alert type="warning" title="Pendaftaran belum dapat dikirim">
                <ul class="mt-1 list-inside list-disc space-y-0.5">
                    @foreach ($blockers as $blocker)
                        <li>{{ $blocker }}</li>
                    @endforeach
                </ul>
            </x-alert>
        @else
            <x-alert type="success" title="Data Anda sudah lengkap">
                Periksa kembali seluruh data di bawah ini. Setelah dikirim, data tidak dapat diubah sendiri.
            </x-alert>
        @endif

        {{-- Data pendaftaran --}}
        <x-review-section title="Data Pendaftaran" :edit="route('registration.start')">
            <x-review-item label="Tahun Ajaran" :value="$registration->academicYear->name" />
            <x-review-item label="Gelombang" :value="$registration->wave->name" />
            <x-review-item label="Jalur" :value="$registration->admissionTrack->name" />
        </x-review-section>

        {{-- Biodata --}}
        <x-review-section title="Biodata" :edit="route('registration.biodata')">
            <x-review-item label="NISN" :value="$applicant->nisn" />
            <x-review-item label="NIK" :value="$applicant->nik" />
            <x-review-item label="Nama Lengkap" :value="$applicant->full_name" />
            <x-review-item label="Jenis Kelamin" :value="$applicant->genderLabel()" />
            <x-review-item label="Tempat, Tanggal Lahir" :value="$applicant->birthInfo()" />
            <x-review-item label="Agama" :value="$applicant->religion" />
            <x-review-item label="Anak Ke / Jumlah Saudara"
                           :value="($applicant->child_order ?? '-').' / '.($applicant->siblings_count ?? '-')" />
            <x-review-item label="Nomor HP" :value="$applicant->phone" />
            <x-review-item label="Email" :value="$applicant->email" />
            <x-review-item label="Nomor Kartu Keluarga" :value="$applicant->family_card_number" />
        </x-review-section>

        {{-- Alamat --}}
        <x-review-section title="Alamat" :edit="route('registration.address')">
            <x-review-item label="Provinsi" :value="$address?->province" />
            <x-review-item label="Kabupaten/Kota" :value="$address?->regency" />
            <x-review-item label="Kecamatan" :value="$address?->district" />
            <x-review-item label="Desa/Kelurahan" :value="$address?->village" />
            <x-review-item label="Kode Pos" :value="$address?->postal_code" />
            <x-review-item label="Alamat Lengkap" :value="$address?->address" wide />
        </x-review-section>

        {{-- Orang tua --}}
        <x-review-section title="Orang Tua / Wali" :edit="route('registration.parents')">
            @forelse ($applicant->parentGuardians as $parent)
                <x-review-item :label="$parent->relationshipLabel()"
                               :value="$parent->name.($parent->occupation ? ' — '.$parent->occupation : '').($parent->phone ? ' ('.$parent->phone.')' : '')"
                               wide />
            @empty
                <x-review-item label="Data" value="Belum diisi" wide />
            @endforelse
        </x-review-section>

        {{-- Asal sekolah --}}
        <x-review-section title="Asal Sekolah" :edit="route('registration.previous-school')">
            <x-review-item label="Nama Sekolah" :value="$school?->school_name" wide />
            <x-review-item label="Jenis / Status"
                           :value="$school ? trim($school->school_type.' / '.$school->school_status, ' /') : null" />
            <x-review-item label="NPSN" :value="$school?->npsn" />
            <x-review-item label="Tahun Lulus" :value="$school?->graduation_year" />
            <x-review-item label="Kabupaten/Kota" :value="$school?->regency" />
        </x-review-section>

        {{-- Program --}}
        <x-review-section title="Program Pilihan" :edit="route('registration.program')">
            <x-review-item label="Program" :value="$registration->program?->name" wide />
        </x-review-section>

        {{-- Berkas --}}
        <x-review-section title="Berkas" :edit="route('registration.documents')">
            <div class="sm:col-span-2">
                <ul class="divide-y divide-slate-100">
                    @foreach ($documentTypes as $type)
                        @php $file = $uploaded[$type->id] ?? null; @endphp

                        <li class="flex items-center justify-between gap-3 py-2.5">
                            <span class="flex min-w-0 items-center gap-2">
                                <x-icon :name="$file ? 'circle-check' : 'circle-dashed'"
                                        class="h-4 w-4 shrink-0 {{ $file ? 'text-emerald-600' : 'text-slate-300' }}" />
                                <span class="min-w-0">
                                    <span class="block truncate text-sm text-slate-800">{{ $type->name }}</span>
                                    @if ($file)
                                        <span class="block truncate text-xs text-slate-500">{{ $file->original_name }}</span>
                                    @endif
                                </span>
                            </span>

                            @if ($type->pivot->is_required && ! $file)
                                <x-badge class="shrink-0 bg-rose-50 text-rose-700 ring-rose-200">Belum ada</x-badge>
                            @elseif (! $file)
                                <x-badge class="shrink-0 bg-slate-100 text-slate-500 ring-slate-200">Opsional</x-badge>
                            @endif
                        </li>
                    @endforeach
                </ul>
            </div>
        </x-review-section>

        {{-- Pernyataan & submit --}}
        <form method="POST" action="{{ route('registration.submit') }}" class="card p-6 sm:p-8"
              x-data="{ agreed: false }"
              onsubmit="return confirm('Kirim pendaftaran sekarang? Data tidak dapat diubah setelah dikirim.')">
            @csrf

            <h2 class="text-lg font-bold text-slate-900">Pernyataan Kebenaran Data</h2>

            <div class="mt-4 rounded-lg bg-slate-50 p-4">
                <x-form.checkbox name="statement_agreed" x-model="agreed"
                                 label="Saya menyatakan seluruh data yang saya isi benar dan dapat dipertanggungjawabkan."
                                 hint="Data yang terbukti tidak benar dapat menggugurkan pendaftaran." />
            </div>

            <x-alert type="warning" class="mt-5" title="Perhatian">
                Setelah dikirim, Anda akan menerima <strong>nomor pendaftaran</strong> dan <strong>kode akses</strong>.
                Simpan keduanya baik-baik &mdash; kode akses hanya ditampilkan satu kali.
            </x-alert>

            <div class="mt-6 flex flex-col gap-3 border-t border-slate-100 pt-6 sm:flex-row sm:justify-between">
                <a href="{{ route('registration.documents') }}" class="btn-secondary">
                    <x-icon name="arrow-left" class="h-4 w-4" />
                    Kembali
                </a>

                <button type="submit" class="btn-primary" x-bind:disabled="! agreed" @disabled($blockers !== [])>
                    <x-icon name="send" class="h-4 w-4" />
                    Kirim Pendaftaran
                </button>
            </div>
        </form>
    </div>
@endsection
