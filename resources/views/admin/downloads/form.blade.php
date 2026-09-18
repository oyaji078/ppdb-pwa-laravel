@extends('layouts.admin')

@section('title', $download->exists ? 'Edit Berkas Unduhan' : 'Tambah Berkas Unduhan')

@section('actions')
    <a href="{{ route('admin.downloads.index') }}" class="btn-secondary btn-sm">
        <x-icon name="arrow-left" class="h-4 w-4" />
        Kembali
    </a>
@endsection

@section('content')
    <form method="POST" action="{{ $download->exists ? route('admin.downloads.update', $download) : route('admin.downloads.store') }}"
          enctype="multipart/form-data" class="card max-w-2xl p-6">
        @csrf
        @if ($download->exists)
            @method('PUT')
        @endif

        <div class="grid gap-5 sm:grid-cols-2">
            <div class="sm:col-span-2">
                <x-form.input name="title" label="Judul Berkas" required :value="$download->title"
                              placeholder="Panduan Pendaftaran PPDB" />
            </div>

            <div class="sm:col-span-2">
                <x-form.textarea name="description" label="Keterangan" rows="2" :value="$download->description" />
            </div>

            <div class="sm:col-span-2">
                <label for="file" class="form-label">
                    Berkas
                    @unless ($download->exists)
                        <span class="text-rose-600" aria-hidden="true">*</span>
                    @endunless
                </label>

                @if ($download->exists)
                    <p class="mb-2 flex items-center gap-2 rounded-lg bg-slate-50 p-3 text-sm text-slate-700">
                        <x-icon name="file-text" class="h-4 w-4 shrink-0 text-slate-400" />
                        {{ $download->original_name }} &middot; {{ $download->humanFileSize() }}
                    </p>
                @endif

                <input type="file" name="file" id="file" @required(! $download->exists)
                       accept=".pdf,.doc,.docx,.xls,.xlsx,.zip"
                       class="block w-full cursor-pointer rounded-lg text-sm text-slate-600 ring-1 ring-slate-300 ring-inset file:mr-3 file:cursor-pointer file:rounded-l-lg file:border-0 file:bg-slate-50 file:px-4 file:py-2.5 file:text-sm file:font-medium file:text-slate-700 hover:file:bg-slate-100">
                <p class="mt-1 text-xs text-slate-500">
                    PDF, DOC, DOCX, XLS, XLSX, atau ZIP. Maksimal 4 MB.
                    @if ($download->exists) Kosongkan bila tidak ingin mengganti berkas. @endif
                </p>

                @error('file')
                    <p class="form-error">
                        <x-icon name="circle-alert" class="mt-px h-3.5 w-3.5 shrink-0" />
                        <span>{{ $message }}</span>
                    </p>
                @enderror
            </div>

            <x-form.input name="sort_order" type="number" label="Urutan Tampil" :value="$download->sort_order ?? 0" min="0" />

            <div class="flex items-end pb-1">
                <x-form.checkbox name="is_published" label="Tampilkan di website"
                                 :checked="$download->exists ? $download->is_published : true" />
            </div>
        </div>

        <div class="mt-6 flex gap-3 border-t border-slate-100 pt-5">
            <button type="submit" class="btn-primary">
                <x-icon name="save" class="h-4 w-4" />
                Simpan
            </button>
            <a href="{{ route('admin.downloads.index') }}" class="btn-secondary">Batal</a>
        </div>
    </form>
@endsection
