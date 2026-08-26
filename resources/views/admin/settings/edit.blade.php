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

                {{-- Banner --}}
                <div class="rounded-lg border border-slate-200 p-5">
                    <h3 class="text-sm font-semibold text-slate-900">Banner Beranda</h3>
                    <p class="mt-1 text-sm text-slate-600">
                        Gambar latar untuk bagian atas beranda, menggantikan warna polos.
                        Ukuran ideal 1920&times;1080 piksel, maksimal 3 MB.
                    </p>

                    @if ($settings->heroBannerUrl())
                        <div class="mt-4">
                            <img src="{{ $settings->heroBannerUrl() }}" alt="Banner beranda saat ini"
                                 class="h-32 w-full rounded-lg object-cover ring-1 ring-slate-200">

                            <div class="mt-3">
                                <x-form.checkbox name="remove_hero_banner" label="Hapus banner, kembali ke warna polos" />
                            </div>
                        </div>
                    @else
                        <p class="mt-3 text-xs text-slate-500">Belum ada banner. Beranda memakai warna polos.</p>
                    @endif

                    <div class="mt-4 grid gap-5 sm:grid-cols-2">
                        <div>
                            <label for="hero_banner" class="form-label">
                                {{ $settings->heroBannerUrl() ? 'Ganti Banner' : 'Unggah Banner' }}
                            </label>
                            <input type="file" name="hero_banner" id="hero_banner" accept=".jpg,.jpeg,.png,.webp"
                                   class="block w-full cursor-pointer rounded-lg text-sm text-slate-600 ring-1 ring-slate-300 ring-inset file:mr-3 file:cursor-pointer file:rounded-l-lg file:border-0 file:bg-slate-50 file:px-4 file:py-2.5 file:text-sm file:font-medium file:text-slate-700 hover:file:bg-slate-100">

                            @error('hero_banner')
                                <p class="form-error">
                                    <x-icon name="circle-alert" class="mt-px h-3.5 w-3.5 shrink-0" />
                                    <span>{{ $message }}</span>
                                </p>
                            @enderror
                        </div>

                        <x-form.input name="hero_overlay" type="number" label="Kegelapan Lapisan (%)"
                                      :value="$values['hero_overlay'] ?: '60'" min="0" max="90" step="5"
                                      inputmode="numeric"
                                      hint="Semakin besar, semakin gelap banner agar tulisan tetap terbaca." />
                    </div>
                </div>

                <x-form.textarea name="faq" label="Pertanyaan yang Sering Diajukan" rows="6" :value="$values['faq']"
                                 hint="Satu baris per pertanyaan dengan format: Pertanyaan|Jawaban" />
            </div>
        </section>

        {{-- Lokasi --}}
        <section class="card p-6">
            <h2 class="font-semibold text-slate-900">Lokasi Sekolah (Google Maps)</h2>
            <p class="mt-1 text-sm text-slate-600">
                Peta ditampilkan pada halaman Kontak. Buka Google Maps, klik kanan pada lokasi
                sekolah, lalu klik koordinat yang muncul untuk menyalinnya &mdash; angka pertama
                adalah Lintang, angka kedua Bujur.
            </p>

            <div class="mt-5 grid gap-5 sm:grid-cols-2">
                <x-form.input name="map_latitude" label="Lintang (Latitude)" :value="$values['map_latitude']"
                              inputmode="decimal" placeholder="-8.652900"
                              hint="Antara -90 sampai 90." />

                <x-form.input name="map_longitude" label="Bujur (Longitude)" :value="$values['map_longitude']"
                              inputmode="decimal" placeholder="116.542100"
                              hint="Antara -180 sampai 180." />

                <x-form.input name="map_place_name" label="Nama Lokasi" :value="$values['map_place_name']"
                              placeholder="Kosongkan untuk memakai nama sekolah" />

                <x-form.input name="map_zoom" type="number" label="Tingkat Perbesaran" :value="$values['map_zoom'] ?: '16'"
                              min="3" max="21" step="1" inputmode="numeric"
                              hint="3 = seluruh negara, 21 = sangat dekat." />
            </div>

            @if ($settings->hasMapLocation())
                <div class="mt-5">
                    <p class="mb-2 text-sm font-medium text-slate-700">Pratinjau</p>
                    <div class="overflow-hidden rounded-lg ring-1 ring-slate-200">
                        <iframe src="{{ $settings->mapEmbedUrl() }}" title="Pratinjau lokasi sekolah"
                                class="h-56 w-full" loading="lazy" referrerpolicy="no-referrer-when-downgrade"></iframe>
                    </div>
                </div>
            @endif
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

        {{-- Email --}}
        <section class="card p-6">
            <h2 class="font-semibold text-slate-900">Email (SMTP)</h2>
            <p class="mt-1 text-sm text-slate-600">
                Dipakai untuk verifikasi email pendaftar dan pemberitahuan perubahan status.
                Pengaturan di sini menimpa konfigurasi <code class="text-xs">.env</code>, sehingga
                berlaku sama saat dijalankan lokal maupun setelah hosting.
            </p>

            <div class="mt-5 space-y-5">
                <x-form.checkbox name="mail_enabled" label="Aktifkan pengiriman email"
                                 :checked="$values['mail_enabled'] === '1'"
                                 hint="Bila dimatikan, sistem tetap berjalan normal tanpa mengirim email apa pun." />

                <x-form.checkbox name="mail_require_verification" label="Wajibkan verifikasi email sebelum kirim formulir"
                                 :checked="$values['mail_require_verification'] === '1'"
                                 hint="Pendaftar tetap dapat mengisi formulir, tetapi tidak dapat mengirimnya sebelum email diverifikasi." />

                <div class="grid gap-5 sm:grid-cols-2">
                    <x-form.select name="mail_mailer" label="Metode Pengiriman" required
                                   :value="$values['mail_mailer'] ?: 'smtp'"
                                   :options="['smtp' => 'SMTP (server email sungguhan)', 'log' => 'Log (uji coba, email ditulis ke berkas log)']"
                                   :placeholder="null" />

                    <x-form.select name="mail_encryption" label="Enkripsi"
                                   :value="$values['mail_encryption'] ?: 'tls'"
                                   :options="['tls' => 'TLS (umumnya port 587)', 'ssl' => 'SSL (umumnya port 465)', 'none' => 'Tanpa enkripsi']"
                                   :placeholder="null" />

                    <x-form.input name="mail_host" label="Host SMTP" :value="$values['mail_host']"
                                  placeholder="smtp.gmail.com" autocomplete="off"
                                  hint="Nama server, bukan alamat email. Gmail: smtp.gmail.com" />

                    <x-form.input name="mail_port" type="number" label="Port" :value="$values['mail_port'] ?: '587'"
                                  min="1" max="65535" />

                    <x-form.input name="mail_username" label="Username" :value="$values['mail_username']"
                                  autocomplete="off" placeholder="akun@sekolah.sch.id" />

                    <x-form.input name="mail_password" type="password" label="Kata Sandi SMTP"
                                  autocomplete="new-password"
                                  :hint="$hasMailPassword
                                      ? 'Sudah tersimpan. Kosongkan bila tidak ingin mengubah.'
                                      : 'Belum ada kata sandi tersimpan.'" />

                    <x-form.input name="mail_from_address" type="email" label="Email Pengirim"
                                  :value="$values['mail_from_address']"
                                  hint="Kosongkan untuk memakai email sekolah." />

                    <x-form.input name="mail_from_name" label="Nama Pengirim" :value="$values['mail_from_name']"
                                  hint="Kosongkan untuk memakai nama sekolah." />
                </div>
            </div>
        </section>

        <div class="card flex gap-3 p-5">
            <button type="submit" class="btn-primary">
                <x-icon name="save" class="h-4 w-4" />
                Simpan Pengaturan
            </button>
        </div>
    </form>

    {{-- Separate form: posting a test must not save unrelated pending edits. --}}
    <form method="POST" action="{{ route('admin.settings.test-mail') }}" class="card mt-5 p-6">
        @csrf

        <h2 class="font-semibold text-slate-900">Uji Coba Pengiriman Email</h2>
        <p class="mt-1 text-sm text-slate-600">
            Simpan pengaturan di atas terlebih dahulu, lalu kirim email percobaan untuk
            memastikan kredensial sudah benar.
        </p>

        <div class="mt-4 flex flex-col gap-3 sm:flex-row sm:items-end">
            <div class="sm:w-80">
                <x-form.input name="test_email" type="email" label="Kirim ke" required
                              :value="old('test_email', auth()->user()?->email)" />
            </div>

            <button type="submit" class="btn-secondary">
                <x-icon name="send" class="h-4 w-4" />
                Kirim Email Uji
            </button>
        </div>
    </form>
@endsection
