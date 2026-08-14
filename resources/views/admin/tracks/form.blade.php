@extends('layouts.admin')

@section('title', $track->exists ? 'Edit Jalur' : 'Tambah Jalur')

@section('actions')
    <a href="{{ route('admin.tracks.index') }}" class="btn-secondary btn-sm">
        <x-icon name="arrow-left" class="h-4 w-4" />
        Kembali
    </a>
@endsection

@section('content')
    <div class="grid max-w-5xl gap-5 lg:grid-cols-2">
        <form method="POST" action="{{ $track->exists ? route('admin.tracks.update', $track) : route('admin.tracks.store') }}"
              class="card p-6">
            @csrf
            @if ($track->exists)
                @method('PUT')
            @endif

            <h2 class="font-semibold text-slate-900">Informasi Jalur</h2>

            <div class="mt-5 grid gap-5 sm:grid-cols-2">
                <x-form.select name="academic_year_id" label="Tahun Ajaran" required
                               :value="$track->academic_year_id" :options="$academicYears->pluck('name', 'id')->all()" />

                <x-form.input name="code" label="Kode Jalur" required :value="$track->code"
                              placeholder="reguler" hint="Huruf kecil, angka, dan tanda hubung." />

                <div class="sm:col-span-2">
                    <x-form.input name="name" label="Nama Jalur" required :value="$track->name" placeholder="Reguler" />
                </div>

                <div class="sm:col-span-2">
                    <x-form.textarea name="description" label="Deskripsi" rows="3" :value="$track->description"
                                     hint="Ditampilkan pada halaman publik dan formulir pendaftaran." />
                </div>

                <x-form.input name="quota" type="number" label="Kuota" :value="$track->quota" min="1"
                              hint="Kosongkan bila tanpa batas." />

                <x-form.input name="sort_order" type="number" label="Urutan Tampil" :value="$track->sort_order ?? 0" min="0" />

                <div class="sm:col-span-2">
                    <x-form.checkbox name="is_active" label="Jalur aktif" :checked="$track->exists ? $track->is_active : true" />
                </div>
            </div>

            <div class="mt-6 flex gap-3 border-t border-slate-100 pt-5">
                <button type="submit" class="btn-primary">
                    <x-icon name="save" class="h-4 w-4" />
                    Simpan
                </button>
                <a href="{{ route('admin.tracks.index') }}" class="btn-secondary">Batal</a>
            </div>
        </form>

        @if ($track->exists)
            <form method="POST" action="{{ route('admin.tracks.requirements', $track) }}" class="card p-6">
                @csrf
                @method('PUT')

                <h2 class="font-semibold text-slate-900">Persyaratan Berkas</h2>
                <p class="prose-content mt-1">
                    Centang berkas yang berlaku untuk jalur ini, lalu tandai mana yang wajib.
                </p>

                @if ($documentTypes->isEmpty())
                    <x-empty-state icon="file-x" title="Belum ada jenis berkas"
                                   description="Tambahkan jenis berkas terlebih dahulu pada menu Persyaratan Berkas." class="py-8">
                        <a href="{{ route('admin.document-types.create') }}" class="btn-secondary btn-sm">
                            <x-icon name="plus" class="h-3.5 w-3.5" />
                            Tambah Jenis Berkas
                        </a>
                    </x-empty-state>
                @else
                    <ul class="mt-4 divide-y divide-slate-100">
                        @foreach ($documentTypes as $type)
                            @php
                                $isSelected = array_key_exists($type->id, $selectedRequirements);
                                $isRequired = (bool) ($selectedRequirements[$type->id] ?? false);
                            @endphp

                            <li class="flex flex-wrap items-center justify-between gap-3 py-3"
                                x-data="{ selected: {{ $isSelected ? 'true' : 'false' }} }">
                                <label class="flex min-w-0 flex-1 cursor-pointer items-start gap-2.5">
                                    <input type="checkbox" name="documents[]" value="{{ $type->id }}" x-model="selected"
                                           class="mt-0.5 h-4 w-4 shrink-0 rounded border-slate-300 text-brand-600 focus:ring-brand-600">
                                    <span class="min-w-0">
                                        <span class="block text-sm font-medium text-slate-900">{{ $type->name }}</span>
                                        <span class="block text-xs text-slate-500">
                                            {{ $type->extensionLabel() }} &middot; maks {{ $type->humanMaxSize() }}
                                        </span>
                                    </span>
                                </label>

                                <label class="flex shrink-0 cursor-pointer items-center gap-2 text-xs"
                                       :class="selected ? 'text-slate-700' : 'text-slate-300'">
                                    <input type="checkbox" name="required[]" value="{{ $type->id }}"
                                           @checked($isRequired) :disabled="! selected"
                                           class="h-4 w-4 rounded border-slate-300 text-rose-600 focus:ring-rose-600 disabled:opacity-40">
                                    Wajib
                                </label>
                            </li>
                        @endforeach
                    </ul>

                    <button type="submit" class="btn-primary mt-5">
                        <x-icon name="save" class="h-4 w-4" />
                        Simpan Persyaratan
                    </button>
                @endif
            </form>
        @else
            <div class="card">
                <x-empty-state icon="file-stack" title="Persyaratan berkas"
                               description="Simpan jalur terlebih dahulu, lalu Anda dapat menentukan persyaratan berkasnya." />
            </div>
        @endif
    </div>
@endsection
