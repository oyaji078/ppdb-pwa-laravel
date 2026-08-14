@extends('layouts.admin')

@section('title', $news->exists ? 'Edit Berita' : 'Tulis Berita')

@section('actions')
    <a href="{{ route('admin.news.index') }}" class="btn-secondary btn-sm">
        <x-icon name="arrow-left" class="h-4 w-4" />
        Kembali
    </a>
@endsection

@section('content')
    <form method="POST" action="{{ $news->exists ? route('admin.news.update', $news) : route('admin.news.store') }}"
          enctype="multipart/form-data" class="card max-w-3xl p-6">
        @csrf
        @if ($news->exists)
            @method('PUT')
        @endif

        <div class="grid gap-5 sm:grid-cols-2">
            <div class="sm:col-span-2">
                <x-form.input name="title" label="Judul Berita" required :value="$news->title" />
            </div>

            <div class="sm:col-span-2">
                <x-form.textarea name="excerpt" label="Ringkasan" rows="2" :value="$news->excerpt"
                                 hint="Tampil pada daftar berita dan kartu di beranda." />
            </div>

            <div class="sm:col-span-2">
                <x-form.textarea name="content" label="Isi Berita" required rows="12" :value="$news->content"
                                 hint="Setiap baris kosong akan menjadi paragraf baru." />
            </div>

            <div class="sm:col-span-2">
                <label for="cover" class="form-label">Gambar Sampul</label>

                @if ($news->cover_path)
                    <img src="{{ Storage::disk('public')->url($news->cover_path) }}" alt=""
                         class="mb-3 h-36 w-full max-w-md rounded-lg object-cover">
                @endif

                <input type="file" name="cover" id="cover" accept=".jpg,.jpeg,.png,.webp"
                       class="block w-full cursor-pointer rounded-lg text-sm text-slate-600 ring-1 ring-slate-300 ring-inset file:mr-3 file:cursor-pointer file:rounded-l-lg file:border-0 file:bg-slate-50 file:px-4 file:py-2.5 file:text-sm file:font-medium file:text-slate-700 hover:file:bg-slate-100">
                <p class="mt-1 text-xs text-slate-500">JPG, PNG, atau WEBP. Maksimal 3 MB.</p>

                @error('cover')
                    <p class="form-error">
                        <x-icon name="circle-alert" class="mt-px h-3.5 w-3.5 shrink-0" />
                        <span>{{ $message }}</span>
                    </p>
                @enderror
            </div>

            <x-form.input name="published_at" type="datetime-local" label="Tanggal Publikasi"
                          :value="$news->published_at?->format('Y-m-d\TH:i')"
                          hint="Kosongkan untuk menggunakan waktu saat ini." />

            <x-form.input name="slug" label="Slug URL" :value="$news->slug"
                          placeholder="dibuat otomatis dari judul" />

            <div class="sm:col-span-2">
                <x-form.checkbox name="is_published" label="Terbitkan sekarang" :checked="$news->is_published" />
            </div>
        </div>

        <div class="mt-6 flex gap-3 border-t border-slate-100 pt-5">
            <button type="submit" class="btn-primary">
                <x-icon name="save" class="h-4 w-4" />
                Simpan
            </button>
            <a href="{{ route('admin.news.index') }}" class="btn-secondary">Batal</a>
        </div>
    </form>
@endsection
