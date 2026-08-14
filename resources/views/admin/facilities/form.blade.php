@extends('layouts.admin')

@section('title', $facility->exists ? 'Edit Fasilitas' : 'Tambah Fasilitas')

@section('actions')
    <a href="{{ route('admin.facilities.index') }}" class="btn-secondary btn-sm">
        <x-icon name="arrow-left" class="h-4 w-4" />
        Kembali
    </a>
@endsection

@section('content')
    <form method="POST" action="{{ $facility->exists ? route('admin.facilities.update', $facility) : route('admin.facilities.store') }}"
          enctype="multipart/form-data" class="card max-w-2xl p-6">
        @csrf
        @if ($facility->exists)
            @method('PUT')
        @endif

        <div class="grid gap-5 sm:grid-cols-2">
            <div class="sm:col-span-2">
                <x-form.input name="name" label="Nama Fasilitas" required :value="$facility->name"
                              placeholder="Laboratorium Komputer" />
            </div>

            <div class="sm:col-span-2">
                <x-form.textarea name="description" label="Deskripsi" rows="3" :value="$facility->description" />
            </div>

            <x-form.input name="icon" label="Nama Ikon" :value="$facility->icon" placeholder="monitor"
                          hint="Nama ikon Lucide, contoh: monitor, library, school. Dipakai bila tidak ada gambar." />

            <x-form.input name="sort_order" type="number" label="Urutan Tampil" :value="$facility->sort_order ?? 0" min="0" />

            <div class="sm:col-span-2">
                <label for="image" class="form-label">Gambar</label>

                @if ($facility->image_path)
                    <img src="{{ Storage::disk('public')->url($facility->image_path) }}" alt=""
                         class="mb-3 h-32 w-full max-w-sm rounded-lg object-cover">
                @endif

                <input type="file" name="image" id="image" accept=".jpg,.jpeg,.png,.webp"
                       class="block w-full cursor-pointer rounded-lg text-sm text-slate-600 ring-1 ring-slate-300 ring-inset file:mr-3 file:cursor-pointer file:rounded-l-lg file:border-0 file:bg-slate-50 file:px-4 file:py-2.5 file:text-sm file:font-medium file:text-slate-700 hover:file:bg-slate-100">
                <p class="mt-1 text-xs text-slate-500">JPG, PNG, atau WEBP. Maksimal 3 MB.</p>

                @error('image')
                    <p class="form-error">
                        <x-icon name="circle-alert" class="mt-px h-3.5 w-3.5 shrink-0" />
                        <span>{{ $message }}</span>
                    </p>
                @enderror
            </div>

            <div class="sm:col-span-2">
                <x-form.checkbox name="is_active" label="Tampilkan di website"
                                 :checked="$facility->exists ? $facility->is_active : true" />
            </div>
        </div>

        <div class="mt-6 flex gap-3 border-t border-slate-100 pt-5">
            <button type="submit" class="btn-primary">
                <x-icon name="save" class="h-4 w-4" />
                Simpan
            </button>
            <a href="{{ route('admin.facilities.index') }}" class="btn-secondary">Batal</a>
        </div>
    </form>
@endsection
