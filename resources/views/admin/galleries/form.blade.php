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
              class="card p-6" enctype="multipart/form-data"
              @unless ($gallery->exists)
                  data-gallery-create
                  {{-- Where the photos go once the album has an id. Built here
                       so the route stays defined in one place. --}}
                  data-images-url="{{ route('admin.galleries.images.store', ['gallery' => '__id__']) }}"
              @endunless>
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

            {{-- Photos belong to an album, so they can only be stored once it
                 exists. Rather than making that the operator's problem by
                 sending them away to a second screen, the picker is offered
                 here and the page saves the album first, then the photos. --}}
            @unless ($gallery->exists)
                <h2 class="mt-8 border-t border-slate-100 pt-6 font-semibold text-slate-900">Foto Album</h2>
                <p class="mt-1 text-sm text-slate-600">Boleh dikosongkan; foto bisa ditambahkan kapan saja setelah album dibuat.</p>

                <div class="mt-4 grid gap-4 sm:grid-cols-3">
                    @include('partials.gallery-image-picker', ['creating' => true])
                </div>
            @endunless

            <div class="mt-6 flex gap-3 border-t border-slate-100 pt-5">
                <button type="submit" class="btn-primary">
                    <x-icon name="save" class="h-4 w-4" />
                    <span data-gallery-submit-label>Simpan</span>
                </button>
                <a href="{{ route('admin.galleries.index') }}" class="btn-secondary">Batal</a>
            </div>
        </form>

        @if ($gallery->exists)
            <div class="card p-6">
                <h2 class="font-semibold text-slate-900">Foto Album</h2>

                <form method="POST" action="{{ route('admin.galleries.images.store', $gallery) }}"
                      enctype="multipart/form-data" class="mt-4 grid gap-4 sm:grid-cols-3"
                      data-gallery-upload>
                    @csrf

                    @include('partials.gallery-image-picker', ['creating' => false])

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
