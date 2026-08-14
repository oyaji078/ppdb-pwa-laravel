@extends('layouts.admin')

@section('title', $documentType->exists ? 'Edit Jenis Berkas' : 'Tambah Jenis Berkas')

@section('actions')
    <a href="{{ route('admin.document-types.index') }}" class="btn-secondary btn-sm">
        <x-icon name="arrow-left" class="h-4 w-4" />
        Kembali
    </a>
@endsection

@section('content')
    <form method="POST"
          action="{{ $documentType->exists ? route('admin.document-types.update', $documentType) : route('admin.document-types.store') }}"
          class="card max-w-2xl p-6">
        @csrf
        @if ($documentType->exists)
            @method('PUT')
        @endif

        <div class="grid gap-5 sm:grid-cols-2">
            <x-form.select name="academic_year_id" label="Tahun Ajaran" required
                           :value="$documentType->academic_year_id" :options="$academicYears->pluck('name', 'id')->all()" />

            <x-form.input name="code" label="Kode Berkas" required :value="$documentType->code"
                          placeholder="kartu-keluarga" hint="Huruf kecil, angka, dan tanda hubung." />

            <div class="sm:col-span-2">
                <x-form.input name="name" label="Nama Berkas" required :value="$documentType->name"
                              placeholder="Kartu Keluarga" />
            </div>

            <div class="sm:col-span-2">
                <x-form.textarea name="description" label="Petunjuk untuk Pendaftar" rows="2"
                                 :value="$documentType->description"
                                 placeholder="Hasil pindai atau foto Kartu Keluarga yang masih berlaku." />
            </div>

            <div class="sm:col-span-2">
                <fieldset>
                    <legend class="form-label">
                        Format yang Diizinkan
                        <span class="text-rose-600" aria-hidden="true">*</span>
                    </legend>

                    @php $selectedExtensions = old('allowed_extensions', $documentType->extensions()); @endphp

                    <div class="mt-2 flex flex-wrap gap-2">
                        @foreach ($availableExtensions as $extension)
                            <label class="flex cursor-pointer items-center gap-2 rounded-lg border border-slate-200 px-3.5 py-2 transition-colors has-checked:border-brand-500 has-checked:bg-brand-50/60 hover:bg-slate-50">
                                <input type="checkbox" name="allowed_extensions[]" value="{{ $extension }}"
                                       @checked(in_array($extension, (array) $selectedExtensions, true))
                                       class="h-4 w-4 rounded border-slate-300 text-brand-600 focus:ring-brand-600">
                                <span class="text-sm font-medium text-slate-800 uppercase">{{ $extension }}</span>
                            </label>
                        @endforeach
                    </div>

                    @error('allowed_extensions')
                        <p class="form-error">
                            <x-icon name="circle-alert" class="mt-px h-3.5 w-3.5 shrink-0" />
                            <span>{{ $message }}</span>
                        </p>
                    @enderror
                </fieldset>
            </div>

            <x-form.input name="max_size_kb" type="number" label="Ukuran Maksimal (KB)" required
                          :value="$documentType->max_size_kb" min="100"
                          max="{{ config('ppdb.uploads.absolute_max_size_kb') }}"
                          hint="1024 KB = 1 MB. Batas sistem {{ number_format(config('ppdb.uploads.absolute_max_size_kb') / 1024, 0) }} MB." />

            <x-form.input name="sort_order" type="number" label="Urutan Tampil"
                          :value="$documentType->sort_order ?? 0" min="0" />

            <div class="space-y-4 sm:col-span-2">
                <x-form.checkbox name="is_required" label="Wajib secara default"
                                 :checked="$documentType->exists ? $documentType->is_required : true"
                                 hint="Nilai awal saat berkas ini ditambahkan ke jalur. Status wajib per jalur tetap dapat diubah." />

                <x-form.checkbox name="requires_verification" label="Perlu diverifikasi panitia"
                                 :checked="$documentType->exists ? $documentType->requires_verification : true" />

                <x-form.checkbox name="is_active" label="Jenis berkas aktif"
                                 :checked="$documentType->exists ? $documentType->is_active : true" />
            </div>
        </div>

        <div class="mt-6 flex gap-3 border-t border-slate-100 pt-5">
            <button type="submit" class="btn-primary">
                <x-icon name="save" class="h-4 w-4" />
                Simpan
            </button>
            <a href="{{ route('admin.document-types.index') }}" class="btn-secondary">Batal</a>
        </div>
    </form>
@endsection
