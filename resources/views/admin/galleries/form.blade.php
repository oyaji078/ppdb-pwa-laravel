@extends('layouts.admin')

@section('title', $gallery->exists ? 'Kelola Album' : 'Tambah Album')

@section('actions')
    <a href="{{ route('admin.galleries.index') }}" class="btn-secondary btn-sm">
        <x-icon name="arrow-left" class="h-4 w-4" />
        Kembali
    </a>
@endsection

@section('content')
    <div class="max-w-4xl space-y-5">
        <form method="POST" action="{{ $gallery->exists ? route('admin.galleries.update', $gallery) : route('admin.galleries.store') }}"
              class="card p-6">
            @csrf
            @if ($gallery->exists)
                @method('PUT')
            @endif

            <h2 class="font-semibold text-slate-900">Informasi Album</h2>

            <div class="mt-5 grid gap-5 sm:grid-cols-2">
                <div class="sm:col-span-2">
                    <x-form.input name="title" label="Judul Album" required :value="$gallery->title" />
                </div>

                <div class="sm:col-span-2">
                    <x-form.textarea name="description" label="Deskripsi" rows="2" :value="$gallery->description" />
                </div>

                <x-form.input name="sort_order" type="number" label="Urutan Tampil" :value="$gallery->sort_order ?? 0" min="0" />

                <div class="flex items-end pb-1">
                    <x-form.checkbox name="is_published" label="Tampilkan di website"
                                     :checked="$gallery->exists ? $gallery->is_published : true" />
                </div>
            </div>

            <div class="mt-6 flex gap-3 border-t border-slate-100 pt-5">
                <button type="submit" class="btn-primary">
                    <x-icon name="save" class="h-4 w-4" />
                    Simpan
                </button>
                <a href="{{ route('admin.galleries.index') }}" class="btn-secondary">Batal</a>
            </div>
        </form>

        @if ($gallery->exists)
            <div class="card p-6">
                <h2 class="font-semibold text-slate-900">Foto Album</h2>

                <form method="POST" action="{{ route('admin.galleries.images.store', $gallery) }}"
                      enctype="multipart/form-data" class="mt-4 grid gap-4 sm:grid-cols-3">
                    @csrf

                    <div class="sm:col-span-2">
                        <label for="images" class="form-label">Pilih Foto (bisa lebih dari satu)</label>
                        <input type="file" name="images[]" id="images" multiple required accept=".jpg,.jpeg,.png,.webp"
                               class="block w-full cursor-pointer rounded-lg text-sm text-slate-600 ring-1 ring-slate-300 ring-inset file:mr-3 file:cursor-pointer file:rounded-l-lg file:border-0 file:bg-slate-50 file:px-4 file:py-2.5 file:text-sm file:font-medium file:text-slate-700 hover:file:bg-slate-100">
                        <p class="mt-1 text-xs text-slate-500">JPG, PNG, atau WEBP. Maksimal 3 MB per foto, hingga 20 foto sekaligus.</p>

                        @error('images')
                            <p class="form-error">
                                <x-icon name="circle-alert" class="mt-px h-3.5 w-3.5 shrink-0" />
                                <span>{{ $message }}</span>
                            </p>
                        @enderror
                    </div>

                    <div>
                        <x-form.input name="caption" label="Keterangan (opsional)" placeholder="Berlaku untuk semua foto" />
                    </div>

                    <div class="sm:col-span-3">
                        <button type="submit" class="btn-primary">
                            <x-icon name="upload" class="h-4 w-4" />
                            Unggah Foto
                        </button>
                    </div>
                </form>

                @if ($gallery->images->isEmpty())
                    <x-empty-state icon="image-off" title="Album masih kosong"
                                   description="Unggah foto pertama untuk album ini." class="py-10" />
                @else
                    <div class="mt-6 grid grid-cols-2 gap-3 sm:grid-cols-3 lg:grid-cols-4">
                        @foreach ($gallery->images as $image)
                            <figure class="group relative overflow-hidden rounded-lg ring-1 ring-slate-200">
                                <img src="{{ Storage::disk('public')->url($image->image_path) }}"
                                     alt="{{ $image->caption }}" class="h-32 w-full object-cover" loading="lazy">

                                @if ($image->caption)
                                    <figcaption class="truncate px-2 py-1.5 text-xs text-slate-600">{{ $image->caption }}</figcaption>
                                @endif

                                <form method="POST" action="{{ route('admin.galleries.images.destroy', $image) }}"
                                      class="absolute top-1.5 right-1.5"
                                      onsubmit="return confirm('Hapus foto ini?')">
                                    @csrf
                                    @method('DELETE')
                                    <button type="submit" class="rounded-lg bg-white/90 p-1.5 text-rose-600 shadow-sm hover:bg-white">
                                        <x-icon name="trash-2" class="h-3.5 w-3.5" />
                                        <span class="sr-only">Hapus foto</span>
                                    </button>
                                </form>
                            </figure>
                        @endforeach
                    </div>
                @endif
            </div>
        @endif
    </div>
@endsection
