@extends('layouts.admin')

@section('title', 'Pengaturan')
@section('subheading', 'Identitas sekolah dan konfigurasi umum sistem.')

@section('content')
    <form method="POST" action="{{ route('admin.settings.update') }}" enctype="multipart/form-data"
          class="max-w-3xl space-y-5">
        @csrf
        @method('PUT')

        {{-- Identitas sekolah --}}
        <section class="card p-6">
            <h2 class="font-semibold text-slate-900">Identitas Sekolah</h2>
            <p class="prose-content mt-1">Digunakan di seluruh halaman publik, portal pendaftar, dan dokumen PDF.</p>

            <div class="mt-5 grid gap-5 sm:grid-cols-2">
                <div class="sm:col-span-2">
                    <x-form.input name="school_name" label="Nama Sekolah" required :value="$values['school_name']" />
                </div>

                <x-form.input name="school_short_name" label="Nama Singkat" :value="$values['school_short_name']"
                              hint="Dipakai sebagai nama aplikasi saat dipasang sebagai PWA." />

                <x-form.input name="school_phone" label="Telepon" :value="$values['school_phone']" />

                <div class="sm:col-span-2">
                    <x-form.textarea name="school_address" label="Alamat" rows="2" :value="$values['school_address']" />
                </div>

                <x-form.input name="school_email" type="email" label="Email Sekolah" :value="$values['school_email']" />

                <x-form.input name="school_website" type="url" label="Website" :value="$values['school_website']"
                              placeholder="https://sekolah.sch.id" />

                <div class="sm:col-span-2">
                    <label for="logo" class="form-label">Logo Sekolah</label>

                    @if ($settings->logoUrl())
                        <img src="{{ $settings->logoUrl() }}" alt="Logo saat ini"
                             class="mb-3 h-20 w-20 rounded-lg bg-slate-50 object-contain p-1 ring-1 ring-slate-200">
                    @endif

                    <input type="file" name="logo" id="logo" accept=".jpg,.jpeg,.png,.webp"
                           class="block w-full cursor-pointer rounded-lg text-sm text-slate-600 ring-1 ring-slate-300 ring-inset file:mr-3 file:cursor-pointer file:rounded-l-lg file:border-0 file:bg-slate-50 file:px-4 file:py-2.5 file:text-sm file:font-medium file:text-slate-700 hover:file:bg-slate-100">
                    <p class="mt-1 text-xs text-slate-500">JPG, PNG, atau WEBP. Maksimal 1 MB. Disarankan bentuk persegi.</p>

                    @error('logo')
                        <p class="form-error">
                            <x-icon name="circle-alert" class="mt-px h-3.5 w-3.5 shrink-0" />
                            <span>{{ $message }}</span>
                        </p>
                    @enderror
                </div>
            </div>
        </section>

        {{-- Penerimaan --}}
        <section class="card p-6">
            <h2 class="font-semibold text-slate-900">Istilah Penerimaan</h2>
            <p class="prose-content mt-1">Sesuaikan bila sekolah menggunakan istilah selain PPDB, misalnya PMBM.</p>

            <div class="mt-5 grid gap-5 sm:grid-cols-2">
                <x-form.input name="admission_name" label="Istilah Penerimaan" required :value="$values['admission_name']"
                              placeholder="PPDB" hint="Contoh: PPDB, PMBM, SPMB." />

                <x-form.input name="admission_tagline" label="Kepanjangan / Tagline" :value="$values['admission_tagline']"
                              placeholder="Penerimaan Peserta Didik Baru" />
            </div>
        </section>

        {{-- Kontak panitia --}}
        <section class="card p-6">
            <h2 class="font-semibold text-slate-900">Kontak Panitia</h2>

            <div class="mt-5 grid gap-5 sm:grid-cols-2">
                <x-form.input name="contact_person" label="Nama Kontak" :value="$values['contact_person']" />

                <x-form.input name="contact_whatsapp" label="Nomor WhatsApp" :value="$values['contact_whatsapp']"
                              placeholder="081234567890" hint="Otomatis diubah menjadi tautan wa.me." />

                <div class="sm:col-span-2">
                    <x-form.input name="contact_email" type="email" label="Email Panitia" :value="$values['contact_email']" />
                </div>
            </div>
        </section>

        {{-- Beranda --}}
        <section class="card p-6">
            <h2 class="font-semibold text-slate-900">Konten Beranda</h2>

            <div class="mt-5 grid gap-5">
                <x-form.input name="hero_title" label="Judul Hero" :value="$values['hero_title']"
                              placeholder="Bergabung Bersama Kami" />

                <x-form.textarea name="hero_subtitle" label="Subjudul Hero" rows="2" :value="$values['hero_subtitle']" />

                <x-form.textarea name="faq" label="Pertanyaan yang Sering Diajukan" rows="6" :value="$values['faq']"
                                 hint="Satu baris per pertanyaan dengan format: Pertanyaan|Jawaban" />
            </div>
        </section>

        {{-- Sistem --}}
        <section class="card p-6">
            <h2 class="font-semibold text-slate-900">Sistem</h2>

            <div class="mt-5">
                <x-form.textarea name="maintenance_message" label="Pesan Pemeliharaan" rows="2"
                                 :value="$values['maintenance_message']"
                                 hint="Ditampilkan pada halaman 503 saat sistem dalam mode pemeliharaan." />
            </div>
        </section>

        <div class="card flex gap-3 p-5">
            <button type="submit" class="btn-primary">
                <x-icon name="save" class="h-4 w-4" />
                Simpan Pengaturan
            </button>
        </div>
    </form>
@endsection
