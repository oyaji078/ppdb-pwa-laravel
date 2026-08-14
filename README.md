# Sistem PPDB Berbasis PWA

Aplikasi Penerimaan Peserta Didik Baru: website publik, pendaftaran online,
portal calon peserta didik, verifikasi berkas, seleksi, daftar ulang, laporan,
dan panel admin. Dibangun dengan Laravel, MySQL, Blade, Tailwind CSS, Alpine.js,
dan Vite, serta dapat dipasang sebagai Progressive Web App.

Identitas sekolah (nama, logo, alamat, kontak, bahkan istilah "PPDB") tidak
ditanam di kode dan sepenuhnya diatur dari menu **Pengaturan**.

---

## Kebutuhan Sistem

| Komponen | Versi |
| --- | --- |
| PHP | 8.3+ dengan ekstensi `pdo_sqlite`/`pdo_mysql`, `mbstring`, `gd`, `fileinfo`, `openssl`, `zip`, `intl` |
| Database | SQLite 3 (pengembangan) atau MySQL 8.0+ / MariaDB 10.4+ (produksi) |
| Node.js | 20+ |
| Composer | 2.x |

## Instalasi

```bash
composer install
npm install

cp .env.example .env
php artisan key:generate

# Bawaan pengembangan memakai SQLite, jadi tidak perlu server database.
touch database/database.sqlite
php artisan migrate --seed

php artisan storage:link
npm run build
npm run start          # menjalankan server PHP + Vite sekaligus
```

`npm run start` membutuhkan perintah `php` tersedia pada PATH. Alternatifnya
jalankan `php artisan serve` dan `npm run dev` pada dua terminal terpisah.

### Memilih database

Pengembangan lokal memakai SQLite agar tidak perlu menyalakan server database.
Untuk produksi, gunakan MySQL dengan mengubah `.env`:

```dotenv
DB_CONNECTION=mysql
DB_HOST=127.0.0.1
DB_PORT=3306
DB_DATABASE=ppdb
DB_USERNAME=ppdb
DB_PASSWORD=rahasia
```

Kode berjalan pada kedua mesin tanpa perubahan lain. Satu catatan teknis:
SQLite mengabaikan `lockForUpdate()`, sehingga keamanan penomoran pendaftaran di
SQLite dijaga lewat `transaction_mode = IMMEDIATE` pada `config/database.php`
(kunci tulis diambil saat `BEGIN`). Di MySQL, penguncian barislah yang dipakai.
Keduanya sudah diuji.

Seed dasar membuat satu akun super admin, satu tahun ajaran aktif dengan dua
gelombang, tiga jalur, tiga program, delapan jenis berkas, jadwal publik, serta
konten awal website. Gelombang pertama sengaja dibuka relatif terhadap tanggal
instalasi agar alur pendaftaran langsung dapat dicoba; sesuaikan tanggalnya dari
menu **Gelombang**.

### Data contoh (opsional, jangan di produksi)

```bash
php artisan db:seed --class=DemoSeeder
```

Menambah 12 pendaftar contoh pada berbagai tahap, dua akun panitia
(`admin.ppdb` dan `verifikator`, kata sandi `Demo#12345`), berita, album, dan
pengumuman. Seeder ini menolak berjalan bila `APP_ENV=production`.

---

## Peran Pengguna

| Peran | Akses |
| --- | --- |
| `super_admin` | Seluruh menu, termasuk akun admin, pengaturan, dan penghapusan pendaftar |
| `admin_ppdb` | Konfigurasi PPDB, konten website, seleksi, publikasi hasil, daftar ulang |
| `verifier` | Verifikasi berkas dan penyelesaian verifikasi pendaftaran |

Calon peserta didik **tidak memiliki akun**. Mereka masuk ke portal dengan
**nomor pendaftaran + kode akses** melalui `/cek-status`. Sesi pendaftar dan sesi
admin terpisah secara logis: satu tidak pernah memberi akses ke area yang lain.

---

## Alur Sistem

```
Website publik → Formulir 8 langkah → Kirim
    ↓ (satu transaksi database)
Nomor pendaftaran 10 digit + kode akses 8 karakter + PDF bukti pendaftaran
    ↓
Portal pendaftar (nomor + kode akses)
    ↓
Verifikasi berkas oleh panitia → (bila perlu) revisi → Terverifikasi
    ↓
Seleksi (draft) → Publikasi → hasil terlihat pendaftar
    ↓
Daftar ulang → Laporan
```

### Nomor pendaftaran

Format 10 digit `YYGGNNNNNN`:

| Bagian | Arti | Contoh |
| --- | --- | --- |
| `YY` | Dua digit tahun masuk | `26` |
| `GG` | Dua digit kode gelombang | `01` |
| `NNNNNN` | Nomor urut per gelombang | `000128` |

Nomor dibuat **hanya saat final submit**, di dalam transaksi database, dengan
baris penghitung yang dikunci `lockForUpdate()`. Tidak menggunakan `MAX()+1`,
sehingga dua pengiriman bersamaan tidak mungkin memperoleh nomor yang sama.
Menghapus pendaftar tidak membuat nomornya dipakai ulang.

### Kode akses

Delapan karakter dari alfabet `ABCDEFGHJKLMNPQRSTUVWXYZ23456789` (tanpa `0`, `O`,
`1`, `I` agar tidak ambigu saat dicetak). Database hanya menyimpan hash-nya;
kode asli ditampilkan **satu kali** pada halaman sukses dan pada PDF bukti
pendaftaran. Panitia pun tidak dapat membacanya kembali — bila hilang, panitia
menerbitkan kode baru lewat **Reset Kode Akses**, yang sekaligus menghentikan
seluruh sesi lama pendaftar tersebut dan tercatat pada activity log.

---

## Keamanan Berkas

Berkas pendaftar **tidak pernah** berada di `public/`. Struktur penyimpanan:

```
storage/app/private/ppdb/{tahun}/{nomor-pendaftaran}/{kode-berkas}/{acak}.{ext}
```

- Nama file di disk diacak 40 karakter; nama asli hanya metadata di database.
- Setiap unduhan melewati controller yang memeriksa policy terlebih dahulu.
- Validasi ekstensi, MIME type, dan ukuran dilakukan di server, bukan hanya di JavaScript.
- Pendaftar A yang mencoba membuka berkas pendaftar B menerima `403`.
- NIK dan nomor Kartu Keluarga ditampilkan tersamar (`5203********1234`) kecuali
  bagi `admin_ppdb` dan `super_admin` pada halaman detail.

---

## PWA

- `GET /manifest.webmanifest` — dibuat dari Pengaturan, jadi nama aplikasi mengikuti nama sekolah.
- `GET /sw.js` — service worker, tidak pernah di-cache sendiri.
- `GET /offline` — halaman fallback saat luring.

Strategi cache:

| Jenis | Strategi |
| --- | --- |
| Aset build, ikon | Cache first (nama file sudah ber-hash) |
| Halaman publik | Network first, cache sebagai cadangan |
| `/admin`, `/pendaftar`, `/daftar`, `/cek-status` | **Network only, tidak pernah disimpan** |

Aksi yang membutuhkan koneksi (unggah, kirim, tindakan admin) diblokir saat
luring dengan pesan yang jelas — tidak ada keberhasilan palsu.

---

## Perintah Perawatan

```bash
# Hapus draft yang tidak pernah dikirim beserta berkasnya (default 30 hari).
php artisan ppdb:prune-drafts --days=30
php artisan ppdb:prune-drafts --dry-run
```

Sudah terjadwal harian pukul 02.00 pada `routes/console.php`; aktifkan dengan
menambahkan `php artisan schedule:run` ke cron setiap menit.

---

## Pengujian

```bash
php artisan test
```

Pengujian memakai SQLite in-memory sesuai bawaan pengembangan. Sebelum rilis ke
produksi, jalankan ulang seluruh suite di atas MySQL dengan mengubah dua baris
pada `phpunit.xml`:

```xml
<env name="DB_CONNECTION" value="mysql"/>
<env name="DB_DATABASE" value="ppdb_testing"/>
```

Buat database `ppdb_testing` sekali terlebih dahulu. Seluruh 178 tes lulus pada
kedua mesin database.

Cakupan utama:

| Berkas | Yang diuji |
| --- | --- |
| `RegistrationWizardTest` | Seluruh siklus penerimaan dari konfigurasi sampai laporan |
| `RegistrationNumberTest` | Format, urutan, keunikan, dan perilaku antrean nomor |
| `RegistrationSubmissionTest` | Transaksi submit, rollback, kode akses |
| `DuplicateApplicantTest` | Satu NISN satu pendaftaran per tahun ajaran |
| `AccessCodeAuthenticationTest` / `AccessCodeRateLimitTest` | Login pendaftar dan pembatasan percobaan |
| `ApplicantDocumentAuthorizationTest` / `AdminDocumentAuthorizationTest` | Kepemilikan dan akses berkas |
| `DocumentUploadValidationTest` | Ekstensi, MIME, ukuran, penggantian berkas |
| `DocumentVerificationTest` / `DocumentRevisionTest` | Verifikasi, catatan revisi, unggah ulang |
| `RegistrationVerificationTest` | Syarat penyelesaian verifikasi |
| `SelectionAuthorizationTest` / `SelectionPublishingTest` | Hak seleksi dan pemisahan draft vs publikasi |
| `ReregistrationTest` | Daftar ulang hanya untuk yang diterima dan sudah diumumkan |
| `PdfReceiptTest` | Bukti pendaftaran, QR, penyimpanan privat |
| `PwaPublicRouteTest` | Manifest, service worker, halaman luring |
| `SecurityHardeningTest` | Header, penyamaran identitas, isolasi sesi, disk privat |
| `AdminPageSmokeTest` | Seluruh halaman admin, portal, dan wizard dapat dirender |

---

## Struktur Kode

```
app/
├── Enums/           Status pendaftaran, seleksi, daftar ulang, berkas, peran
├── Http/
│   ├── Controllers/ Public, Registration, Applicant, Admin
│   ├── Middleware/  Sesi pendaftar, peran, no-store, security headers
│   └── Requests/    Validasi per langkah wizard
├── Models/          29 model inti beserta relasinya
├── Policies/        Izin admin atas pendaftaran dan berkas
├── Services/        Logika domain (lihat bawah)
└── Support/         Sesi pendaftar, draft wizard, pengaturan, helper
```

Service utama:

| Service | Tanggung jawab |
| --- | --- |
| `RegistrationService` | Draft, penyimpanan per langkah, transaksi final submit |
| `RegistrationNumberService` | Penomoran 10 digit yang aman terhadap konkurensi |
| `AccessCodeService` | Pembuatan, verifikasi, dan reset kode akses |
| `DocumentService` | Penyimpanan privat, validasi server-side, relokasi berkas |
| `VerificationService` | Keputusan per berkas, audit log, gerbang "Selesaikan Verifikasi" |
| `SelectionService` | Penetapan hasil, publikasi, pembatalan draft |
| `ReregistrationService` | Status daftar ulang |
| `PdfService` | Bukti pendaftaran A4 + QR, dokumen pemulihan kode akses |
| `DashboardService` | Agregat dashboard admin |
| `NotificationService` / `ActivityLogger` | Notifikasi pendaftar dan jejak audit panitia |

CRUD sederhana ditangani langsung di controller; service dipakai hanya untuk
alur yang memang berlapis.

---

## Catatan Produksi

- Setel `APP_ENV=production`, `APP_DEBUG=false`, dan `SESSION_SECURE_COOKIE=true`.
- Jalankan `php artisan config:cache route:cache view:cache` setelah deploy.
- Arahkan document root web server ke `public/`, bukan ke akar proyek.
- Pastikan `storage/app/private` tidak dapat diakses langsung dari web.
- Ganti kata sandi super admin setelah login pertama.
