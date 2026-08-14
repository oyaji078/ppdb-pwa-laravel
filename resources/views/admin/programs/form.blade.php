@extends('layouts.admin')

@section('title', $program->exists ? 'Edit Program' : 'Tambah Program')

@section('actions')
    <a href="{{ route('admin.programs.index') }}" class="btn-secondary btn-sm">
        <x-icon name="arrow-left" class="h-4 w-4" />
        Kembali
    </a>
@endsection

@section('content')
    <form method="POST" action="{{ $program->exists ? route('admin.programs.update', $program) : route('admin.programs.store') }}"
          class="card max-w-2xl p-6">
        @csrf
        @if ($program->exists)
            @method('PUT')
        @endif

        <div class="grid gap-5 sm:grid-cols-2">
            <x-form.select name="academic_year_id" label="Tahun Ajaran" required
                           :value="$program->academic_year_id" :options="$academicYears->pluck('name', 'id')->all()" />

            <x-form.input name="code" label="Kode Program" required :value="$program->code"
                          placeholder="mipa" hint="Huruf kecil, angka, dan tanda hubung." />

            <div class="sm:col-span-2">
                <x-form.input name="name" label="Nama Program" required :value="$program->name"
                              placeholder="Matematika dan Ilmu Pengetahuan Alam" />
            </div>

            <div class="sm:col-span-2">
                <x-form.textarea name="description" label="Deskripsi" rows="3" :value="$program->description" />
            </div>

            <x-form.input name="quota" type="number" label="Daya Tampung" :value="$program->quota" min="1"
                          hint="Kosongkan bila tanpa batas." />

            <x-form.input name="sort_order" type="number" label="Urutan Tampil" :value="$program->sort_order ?? 0" min="0" />

            <div class="sm:col-span-2">
                <x-form.checkbox name="is_active" label="Program aktif" :checked="$program->exists ? $program->is_active : true" />
            </div>
        </div>

        <div class="mt-6 flex gap-3 border-t border-slate-100 pt-5">
            <button type="submit" class="btn-primary">
                <x-icon name="save" class="h-4 w-4" />
                Simpan
            </button>
            <a href="{{ route('admin.programs.index') }}" class="btn-secondary">Batal</a>
        </div>
    </form>
@endsection
