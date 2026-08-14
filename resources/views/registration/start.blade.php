@php $stepKey = 'pendaftaran'; @endphp

@extends('layouts.wizard')

@section('title', 'Pendaftaran - Data Pendaftaran')

@section('wizard')
    <form method="POST" action="{{ route('registration.start.store') }}" class="card p-6 sm:p-8">
        @csrf

        <h2 class="text-lg font-bold text-slate-900">Data Pendaftaran</h2>
        <p class="mt-1 text-sm text-slate-600">
            Pilih gelombang dan jalur pendaftaran yang sesuai. Pilihan ini menentukan berkas yang harus Anda unggah.
        </p>

        <dl class="mt-6 grid gap-3 rounded-lg bg-slate-50 p-4 sm:grid-cols-2">
            <div>
                <dt class="text-xs font-medium text-slate-500">Tahun Ajaran</dt>
                <dd class="mt-0.5 text-sm font-semibold text-slate-900">{{ $academicYear->name }}</dd>
            </div>
            <div>
                <dt class="text-xs font-medium text-slate-500">Sekolah</dt>
                <dd class="mt-0.5 text-sm font-semibold text-slate-900">{{ $settings->schoolName() }}</dd>
            </div>
        </dl>

        @if ($waves->isEmpty())
            <x-alert type="warning" class="mt-6" title="Belum ada gelombang yang dibuka">
                Saat ini tidak ada gelombang pendaftaran yang sedang berlangsung.
                Silakan periksa <a href="{{ route('ppdb.schedule') }}" class="font-semibold underline">jadwal penerimaan</a>.
            </x-alert>
        @else
            <div class="mt-6 space-y-6">
                {{-- Gelombang --}}
                <fieldset>
                    <legend class="form-label">
                        Gelombang Pendaftaran
                        <span class="text-rose-600" aria-hidden="true">*</span>
                    </legend>

                    <div class="mt-2 grid gap-3 sm:grid-cols-2">
                        @foreach ($waves as $wave)
                            <label class="relative flex cursor-pointer items-start gap-3 rounded-lg border border-slate-200 p-4 transition-colors has-checked:border-brand-500 has-checked:bg-brand-50/60 hover:bg-slate-50">
                                <input type="radio" name="registration_wave_id" value="{{ $wave->id }}"
                                       @checked((int) old('registration_wave_id', $draft?->registration_wave_id) === $wave->id)
                                       required
                                       class="mt-0.5 h-4 w-4 border-slate-300 text-brand-600 focus:ring-brand-600">
                                <span class="min-w-0">
                                    <span class="block text-sm font-semibold text-slate-900">{{ $wave->name }}</span>
                                    <span class="mt-0.5 block text-xs text-slate-500">
                                        {{ $wave->start_at->translatedFormat('d M Y') }} &ndash; {{ $wave->end_at->translatedFormat('d M Y') }}
                                    </span>
                                    @if ($wave->quota)
                                        <span class="mt-0.5 block text-xs text-slate-500">Kuota {{ number_format($wave->quota, 0, ',', '.') }} peserta</span>
                                    @endif
                                </span>
                            </label>
                        @endforeach
                    </div>

                    @error('registration_wave_id')
                        <p class="form-error">
                            <x-icon name="circle-alert" class="mt-px h-3.5 w-3.5 shrink-0" />
                            <span>{{ $message }}</span>
                        </p>
                    @enderror
                </fieldset>

                {{-- Jalur --}}
                <fieldset>
                    <legend class="form-label">
                        Jalur Pendaftaran
                        <span class="text-rose-600" aria-hidden="true">*</span>
                    </legend>

                    <div class="mt-2 grid gap-3 sm:grid-cols-2 lg:grid-cols-3">
                        @foreach ($tracks as $track)
                            <label class="relative flex cursor-pointer items-start gap-3 rounded-lg border border-slate-200 p-4 transition-colors has-checked:border-brand-500 has-checked:bg-brand-50/60 hover:bg-slate-50">
                                <input type="radio" name="admission_track_id" value="{{ $track->id }}"
                                       @checked((int) old('admission_track_id', $draft?->admission_track_id) === $track->id)
                                       required
                                       class="mt-0.5 h-4 w-4 border-slate-300 text-brand-600 focus:ring-brand-600">
                                <span class="min-w-0">
                                    <span class="block text-sm font-semibold text-slate-900">{{ $track->name }}</span>
                                    <span class="mt-0.5 block text-xs leading-relaxed text-slate-500">{{ Str::limit($track->description, 90) }}</span>
                                </span>
                            </label>
                        @endforeach
                    </div>

                    @error('admission_track_id')
                        <p class="form-error">
                            <x-icon name="circle-alert" class="mt-px h-3.5 w-3.5 shrink-0" />
                            <span>{{ $message }}</span>
                        </p>
                    @enderror
                </fieldset>
            </div>

            <div class="mt-8 flex flex-col gap-3 border-t border-slate-100 pt-6 sm:flex-row sm:justify-between">
                <a href="{{ route('ppdb.index') }}" class="btn-secondary">
                    <x-icon name="arrow-left" class="h-4 w-4" />
                    Kembali
                </a>
                <button type="submit" class="btn-primary">
                    Lanjut ke Biodata
                    <x-icon name="arrow-right" class="h-4 w-4" />
                </button>
            </div>
        @endif
    </form>

    @if ($draft)
        <x-alert type="info" title="Anda memiliki pendaftaran yang belum selesai">
            Melanjutkan formulir akan menggunakan data yang sudah Anda isi sebelumnya.
            <a href="{{ route('registration.biodata') }}" class="font-semibold underline">Lanjutkan pengisian</a>.
        </x-alert>
    @endif
@endsection
