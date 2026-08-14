@extends('layouts.admin')

@section('title', 'Profil Sekolah')
@section('subheading', 'Isi halaman profil pada website publik.')

@section('content')
    @if ($sections->isEmpty())
        <div class="card">
            <x-empty-state icon="landmark" title="Belum ada bagian profil"
                           description="Bagian profil dibuat melalui seeder awal. Hubungi pengembang bila bagian ini kosong." />
        </div>
    @else
        <div class="max-w-3xl space-y-5">
            @foreach ($sections as $section)
                <form method="POST" action="{{ route('admin.school-profile.update', $section) }}"
                      enctype="multipart/form-data" class="card p-6">
                    @csrf
                    @method('PUT')

                    <div class="flex items-center justify-between gap-3">
                        <h2 class="font-semibold text-slate-900">{{ $section->title }}</h2>
                        <x-badge class="bg-slate-100 text-slate-600 ring-slate-200">{{ $section->section }}</x-badge>
                    </div>

                    <div class="mt-5 grid gap-5 sm:grid-cols-2">
                        <div class="sm:col-span-2">
                            <x-form.input :name="'title'" :id="'title-'.$section->id" label="Judul Bagian" required
                                          :value="$section->title" />
                        </div>

                        <div class="sm:col-span-2">
                            <x-form.textarea :name="'content'" :id="'content-'.$section->id" label="Isi" rows="6"
                                             :value="$section->content"
                                             hint="Setiap baris kosong akan menjadi paragraf baru." />
                        </div>

                        <div class="sm:col-span-2">
                            <label for="image-{{ $section->id }}" class="form-label">Gambar Pendukung</label>

                            @if ($section->image_path)
                                <img src="{{ Storage::disk('public')->url($section->image_path) }}" alt=""
                                     class="mb-3 h-32 w-full max-w-sm rounded-lg object-cover">
                            @endif

                            <input type="file" name="image" id="image-{{ $section->id }}" accept=".jpg,.jpeg,.png,.webp"
                                   class="block w-full cursor-pointer rounded-lg text-sm text-slate-600 ring-1 ring-slate-300 ring-inset file:mr-3 file:cursor-pointer file:rounded-l-lg file:border-0 file:bg-slate-50 file:px-4 file:py-2.5 file:text-sm file:font-medium file:text-slate-700 hover:file:bg-slate-100">
                        </div>

                        <x-form.input :name="'sort_order'" :id="'sort-'.$section->id" type="number"
                                      label="Urutan Tampil" :value="$section->sort_order" min="0" />

                        <div class="flex items-end pb-1">
                            <x-form.checkbox :name="'is_active'" :id="'active-'.$section->id"
                                             label="Tampilkan di website" :checked="$section->is_active" />
                        </div>
                    </div>

                    <button type="submit" class="btn-primary mt-6">
                        <x-icon name="save" class="h-4 w-4" />
                        Simpan Bagian Ini
                    </button>
                </form>
            @endforeach
        </div>
    @endif
@endsection
