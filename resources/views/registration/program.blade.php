@php $stepKey = 'program'; @endphp

@extends('layouts.wizard')

@section('title', 'Pendaftaran - Program')

@section('wizard')
    <form method="POST" action="{{ route('registration.program.store') }}" class="card p-6 sm:p-8">
        @csrf

        <h2 class="text-lg font-bold text-slate-900">Program / Peminatan</h2>
        <p class="mt-1 text-sm text-slate-600">Pilih satu program yang ingin Anda ikuti.</p>

        @if ($programs->isEmpty())
            <x-empty-state icon="book-open" title="Belum ada program tersedia"
                           description="Panitia belum menetapkan program untuk tahun ajaran ini. Silakan hubungi panitia." />
        @else
            <fieldset class="mt-6">
                <legend class="sr-only">Pilih program</legend>

                <div class="space-y-3">
                    @foreach ($programs as $program)
                        @php $remaining = $remainingQuota[$program->id] ?? null; @endphp

                        <label @class([
                            'relative flex cursor-pointer items-start gap-3 rounded-lg border border-slate-200 p-4 transition-colors hover:bg-slate-50 has-checked:border-brand-500 has-checked:bg-brand-50/60',
                            'cursor-not-allowed opacity-60 hover:bg-transparent' => $remaining === 0,
                        ])>
                            <input type="radio" name="program_id" value="{{ $program->id }}"
                                   @checked((int) old('program_id', $registration->program_id) === $program->id)
                                   @disabled($remaining === 0)
                                   required
                                   class="mt-0.5 h-4 w-4 border-slate-300 text-brand-600 focus:ring-brand-600">

                            <span class="min-w-0 flex-1">
                                <span class="flex flex-wrap items-center gap-2">
                                    <span class="text-sm font-semibold text-slate-900">{{ $program->name }}</span>
                                    <span class="badge bg-brand-50 text-brand-700 ring-brand-200">{{ Str::upper($program->code) }}</span>
                                </span>

                                @if ($program->description)
                                    <span class="mt-1 block text-xs leading-relaxed text-slate-600">{{ $program->description }}</span>
                                @endif

                                @if ($program->quota !== null)
                                    <span @class([
                                        'mt-1.5 block text-xs font-medium',
                                        'text-rose-600' => $remaining === 0,
                                        'text-amber-600' => $remaining > 0 && $remaining <= 5,
                                        'text-slate-500' => $remaining > 5,
                                    ])>
                                        @if ($remaining === 0)
                                            Kuota penuh
                                        @else
                                            Sisa kuota {{ number_format($remaining, 0, ',', '.') }} dari {{ number_format($program->quota, 0, ',', '.') }}
                                        @endif
                                    </span>
                                @endif
                            </span>
                        </label>
                    @endforeach
                </div>

                @error('program_id')
                    <p class="form-error">
                        <x-icon name="circle-alert" class="mt-px h-3.5 w-3.5 shrink-0" />
                        <span>{{ $message }}</span>
                    </p>
                @enderror
            </fieldset>
        @endif

        <div class="mt-8 flex flex-col gap-3 border-t border-slate-100 pt-6 sm:flex-row sm:justify-between">
            <a href="{{ route('registration.previous-school') }}" class="btn-secondary">
                <x-icon name="arrow-left" class="h-4 w-4" />
                Kembali
            </a>
            <button type="submit" class="btn-primary" @disabled($programs->isEmpty())>
                Simpan &amp; Lanjut
                <x-icon name="arrow-right" class="h-4 w-4" />
            </button>
        </div>
    </form>
@endsection
