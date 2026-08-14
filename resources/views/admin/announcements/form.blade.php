@extends('layouts.admin')

@section('title', $announcement->exists ? 'Edit Pengumuman' : 'Tambah Pengumuman')

@section('actions')
    <a href="{{ route('admin.announcements.index') }}" class="btn-secondary btn-sm">
        <x-icon name="arrow-left" class="h-4 w-4" />
        Kembali
    </a>
@endsection

@section('content')
    <form method="POST"
          action="{{ $announcement->exists ? route('admin.announcements.update', $announcement) : route('admin.announcements.store') }}"
          class="card max-w-3xl p-6">
        @csrf
        @if ($announcement->exists)
            @method('PUT')
        @endif

        <div class="grid gap-5 sm:grid-cols-2">
            <div class="sm:col-span-2">
                <x-form.input name="title" label="Judul" required :value="$announcement->title" />
            </div>

            <x-form.select name="audience" label="Target Pembaca" required
                           :value="$announcement->audience?->value" :options="$audiences" :placeholder="null"
                           hint="Umum tampil di website; Pendaftar hanya di portal; Diterima hanya untuk yang lolos." />

            <x-form.select name="academic_year_id" label="Tahun Ajaran" placeholder="Semua tahun ajaran"
                           :value="$announcement->academic_year_id" :options="$academicYears->pluck('name', 'id')->all()" />

            <div class="sm:col-span-2">
                <x-form.textarea name="content" label="Isi Pengumuman" required rows="10" :value="$announcement->content"
                                 hint="Setiap baris kosong akan menjadi paragraf baru." />
            </div>

            <x-form.input name="published_at" type="datetime-local" label="Tanggal Publikasi"
                          :value="$announcement->published_at?->format('Y-m-d\TH:i')"
                          hint="Kosongkan untuk menggunakan waktu saat ini." />

            <x-form.input name="expires_at" type="datetime-local" label="Berlaku Sampai"
                          :value="$announcement->expires_at?->format('Y-m-d\TH:i')"
                          hint="Kosongkan bila tidak kedaluwarsa." />

            <div class="sm:col-span-2">
                <x-form.input name="slug" label="Slug URL" :value="$announcement->slug"
                              placeholder="dibuat otomatis dari judul"
                              hint="Kosongkan agar dibuat otomatis." />
            </div>

            <div class="sm:col-span-2">
                <x-form.checkbox name="is_published" label="Terbitkan sekarang" :checked="$announcement->is_published" />
            </div>
        </div>

        <div class="mt-6 flex gap-3 border-t border-slate-100 pt-5">
            <button type="submit" class="btn-primary">
                <x-icon name="save" class="h-4 w-4" />
                Simpan
            </button>
            <a href="{{ route('admin.announcements.index') }}" class="btn-secondary">Batal</a>
        </div>
    </form>
@endsection
