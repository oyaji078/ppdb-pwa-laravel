@extends('layouts.admin')

@section('title', $academicYear->exists ? 'Edit Tahun Ajaran' : 'Tambah Tahun Ajaran')

@section('actions')
    <a href="{{ route('admin.academic-years.index') }}" class="btn-secondary btn-sm">
        <x-icon name="arrow-left" class="h-4 w-4" />
        Kembali
    </a>
@endsection

@section('content')
    <form method="POST"
          action="{{ $academicYear->exists ? route('admin.academic-years.update', $academicYear) : route('admin.academic-years.store') }}"
          class="card max-w-2xl p-6">
        @csrf
        @if ($academicYear->exists)
            @method('PUT')
        @endif

        <div class="grid gap-5 sm:grid-cols-2">
            <div class="sm:col-span-2">
                <x-form.input name="name" label="Nama Tahun Ajaran" required :value="$academicYear->name"
                              placeholder="2026/2027" hint="Format yang lazim: 2026/2027." />
            </div>

            <x-form.input name="start_year" type="number" label="Tahun Mulai" required
                          :value="$academicYear->start_year" min="2000" max="2100"
                          hint="Dua digit terakhir dipakai sebagai awalan nomor pendaftaran." />

            <x-form.input name="end_year" type="number" label="Tahun Selesai" required
                          :value="$academicYear->end_year" min="2000" max="2100" />

            <div class="space-y-4 sm:col-span-2">
                <x-form.checkbox name="is_active" label="Jadikan tahun ajaran aktif"
                                 :checked="$academicYear->is_active"
                                 hint="Hanya satu tahun ajaran yang dapat aktif. Mengaktifkan ini akan menonaktifkan yang lain." />

                <x-form.checkbox name="registration_open" label="Buka pendaftaran"
                                 :checked="$academicYear->registration_open"
                                 hint="Bila dimatikan, calon peserta didik tidak dapat mengisi formulir pendaftaran." />
            </div>
        </div>

        <div class="mt-6 flex gap-3 border-t border-slate-100 pt-5">
            <button type="submit" class="btn-primary">
                <x-icon name="save" class="h-4 w-4" />
                Simpan
            </button>
            <a href="{{ route('admin.academic-years.index') }}" class="btn-secondary">Batal</a>
        </div>
    </form>
@endsection
