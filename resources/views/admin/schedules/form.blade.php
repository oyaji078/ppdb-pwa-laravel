@extends('layouts.admin')

@section('title', $schedule->exists ? 'Edit Jadwal' : 'Tambah Jadwal')

@section('actions')
    <a href="{{ route('admin.schedules.index') }}" class="btn-secondary btn-sm">
        <x-icon name="arrow-left" class="h-4 w-4" />
        Kembali
    </a>
@endsection

@section('content')
    <form method="POST" action="{{ $schedule->exists ? route('admin.schedules.update', $schedule) : route('admin.schedules.store') }}"
          class="card max-w-2xl p-6">
        @csrf
        @if ($schedule->exists)
            @method('PUT')
        @endif

        <div class="grid gap-5 sm:grid-cols-2">
            <x-form.select name="academic_year_id" label="Tahun Ajaran" required
                           :value="$schedule->academic_year_id" :options="$academicYears->pluck('name', 'id')->all()" />

            <x-form.select name="registration_wave_id" label="Gelombang" placeholder="Semua gelombang"
                           :value="$schedule->registration_wave_id" :options="$waves->pluck('name', 'id')->all()"
                           hint="Kosongkan bila tahapan berlaku untuk semua gelombang." />

            <div class="sm:col-span-2">
                <x-form.input name="title" label="Nama Tahapan" required :value="$schedule->title"
                              placeholder="Pendaftaran Gelombang 1" />
            </div>

            <div class="sm:col-span-2">
                <x-form.textarea name="description" label="Keterangan" rows="2" :value="$schedule->description" />
            </div>

            <x-form.input name="start_at" type="datetime-local" label="Mulai" required
                          :value="$schedule->start_at?->format('Y-m-d\TH:i')" />

            <x-form.input name="end_at" type="datetime-local" label="Selesai" required
                          :value="$schedule->end_at?->format('Y-m-d\TH:i')" />

            <x-form.input name="sort_order" type="number" label="Urutan Tampil" :value="$schedule->sort_order ?? 0" min="0" />

            <div class="flex items-end pb-1">
                <x-form.checkbox name="is_public" label="Tampilkan di halaman publik"
                                 :checked="$schedule->exists ? $schedule->is_public : true" />
            </div>
        </div>

        <div class="mt-6 flex gap-3 border-t border-slate-100 pt-5">
            <button type="submit" class="btn-primary">
                <x-icon name="save" class="h-4 w-4" />
                Simpan
            </button>
            <a href="{{ route('admin.schedules.index') }}" class="btn-secondary">Batal</a>
        </div>
    </form>
@endsection
