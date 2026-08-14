@extends('layouts.admin')

@section('title', $wave->exists ? 'Edit Gelombang' : 'Tambah Gelombang')

@section('actions')
    <a href="{{ route('admin.waves.index') }}" class="btn-secondary btn-sm">
        <x-icon name="arrow-left" class="h-4 w-4" />
        Kembali
    </a>
@endsection

@section('content')
    <form method="POST" action="{{ $wave->exists ? route('admin.waves.update', $wave) : route('admin.waves.store') }}"
          class="card max-w-2xl p-6">
        @csrf
        @if ($wave->exists)
            @method('PUT')
        @endif

        <div class="grid gap-5 sm:grid-cols-2">
            <x-form.select name="academic_year_id" label="Tahun Ajaran" required
                           :value="$wave->academic_year_id" :options="$academicYears->pluck('name', 'id')->all()" />

            <x-form.input name="code" label="Kode Gelombang" required :value="$wave->code"
                          inputmode="numeric" maxlength="2" placeholder="01"
                          hint="2 angka. Dipakai sebagai digit ke-3 dan ke-4 nomor pendaftaran." />

            <div class="sm:col-span-2">
                <x-form.input name="name" label="Nama Gelombang" required :value="$wave->name" placeholder="Gelombang 1" />
            </div>

            <x-form.input name="start_at" type="datetime-local" label="Mulai" required
                          :value="$wave->start_at?->format('Y-m-d\TH:i')" />

            <x-form.input name="end_at" type="datetime-local" label="Selesai" required
                          :value="$wave->end_at?->format('Y-m-d\TH:i')" />

            <x-form.input name="quota" type="number" label="Kuota" :value="$wave->quota" min="1"
                          hint="Kosongkan bila tanpa batas kuota." />

            <div class="flex items-end pb-1">
                <x-form.checkbox name="is_active" label="Gelombang aktif" :checked="$wave->exists ? $wave->is_active : true" />
            </div>
        </div>

        <div class="mt-6 flex gap-3 border-t border-slate-100 pt-5">
            <button type="submit" class="btn-primary">
                <x-icon name="save" class="h-4 w-4" />
                Simpan
            </button>
            <a href="{{ route('admin.waves.index') }}" class="btn-secondary">Batal</a>
        </div>
    </form>
@endsection
