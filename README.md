# Sistem PPDB Berbasis PWA

Aplikasi Penerimaan Peserta Didik Baru: website publik, pendaftaran online,
portal calon peserta didik, verifikasi berkas, seleksi, daftar ulang, laporan,
dan panel admin. Dibangun dengan Laravel, Blade, Tailwind CSS, Alpine.js, dan
Vite, serta dapat dipasang sebagai Progressive Web App.

Identitas sekolah (nama, logo, alamat, kontak, bahkan istilah "PPDB") tidak
ditanam di kode dan sepenuhnya diatur dari menu **Pengaturan**.

---

## Daftar Isi

1. [Kebutuhan Sistem](#1-kebutuhan-sistem)
2. [Instalasi Cepat](#2-instalasi-cepat)
3. [Instalasi Langkah demi Langkah](#3-instalasi-langkah-demi-langkah)
4. [Konfigurasi Database](#4-konfigurasi-database)
5. [Migrasi Database](#5-migrasi-database)
6. [Seeder](#6-seeder)
7. [Pindah SQLite ke MySQL](#7-pindah-sqlite-ke-mysql)
8. [Menjalankan Aplikasi](#8-menjalankan-aplikasi)
9. [Akun dan Cara Login](#9-akun-dan-cara-login)
10. [Skema Database](#10-skema-database)
11. [Alur Sistem](#11-alur-sistem)
12. [Keamanan Berkas](#12-keamanan-berkas)
13. [PWA](#13-pwa)
14. [Perawatan Berkala](#14-perawatan-berkala)
15. [Pengujian](#15-pengujian)
16. [Struktur Kode](#16-struktur-kode)
17. [Deploy ke Produksi](#17-deploy-ke-produksi)
18. [Pemecahan Masalah](#18-pemecahan-masalah)

---

## 1. Kebutuhan Sistem

| Komponen | Versi minimal | Catatan |
| --- | --- | --- |
| PHP | 8.3 | Diuji pada 8.5.9 |
| Composer | 2.x | |
| Node.js | 20 | Beserta npm |
| Database | SQLite 3 **atau** MySQL 8.0 / MariaDB 10.4 | SQLite untuk pengembangan, MySQL untuk produksi |

### Ekstensi PHP yang wajib aktif

```
pdo_sqlite   pdo_mysql   mbstring   openssl   fileinfo
gd           zip         intl       curl      json
```

Periksa dengan:

```bash
php -m
```

Bila ada yang belum aktif, buka `php.ini` lalu hapus tanda `;` pada baris
ekstensi terkait, misalnya:

```ini
extension=pdo_mysql
extension=gd
extension=intl
```

Cari lokasi `php.ini` dengan `php --ini`. Restart terminal setelah mengubahnya.

`gd` dipakai untuk membuat QR Code pada bukti pendaftaran, `zip` untuk ekspor
XLSX, dan `intl` untuk format tanggal Bahasa Indonesia.

---

## 2. Instalasi Cepat

Untuk pengembangan lokal dengan SQLite — tidak perlu menyalakan server database.

```bash
git clone https://github.com/oyaji078/ppdb-pwa-laravel.git
cd ppdb-pwa-laravel

composer install
npm install

cp .env.example .env
php artisan key:generate

touch database/database.sqlite
php artisan migrate --seed

php artisan storage:link
npm run build
npm run start
```

Buka http://localhost:8000. Login panitia di `/admin/login`.

> Pada Windows PowerShell, ganti `touch database/database.sqlite` dengan
> `New-Item database/database.sqlite -ItemType File`.

---

## 3. Instalasi Langkah demi Langkah

### 3.1 Ambil kode dan dependensi

```bash
git clone https://github.com/oyaji078/ppdb-pwa-laravel.git
cd ppdb-pwa-laravel

composer install     # dependensi PHP
npm install          # dependensi frontend
```

Untuk server produksi, gunakan:

```bash
composer install --no-dev --optimize-autoloader
```

### 3.2 Siapkan berkas environment

```bash
cp .env.example .env
php artisan key:generate
```

`php artisan key:generate` mengisi `APP_KEY`. Tanpa ini, sesi dan enkripsi tidak
berjalan dan aplikasi akan menolak start.

Sesuaikan isi `.env` seperlunya:

```dotenv
APP_NAME="PPDB Online"
APP_ENV=local            # production saat rilis
APP_DEBUG=true           # false saat rilis
APP_URL=http://localhost:8000
APP_TIMEZONE=Asia/Makassar
```

Sesuaikan `APP_TIMEZONE` dengan zona waktu sekolah (`Asia/Jakarta`,
`Asia/Makassar`, atau `Asia/Jayapura`). Zona ini dipakai pada cap waktu
pendaftaran dan bukti pendaftaran.

### 3.3 Siapkan database

Lihat [bagian 4](#4-konfigurasi-database) untuk pilihan SQLite atau MySQL.

### 3.4 Jalankan migrasi dan seeder

```bash
php artisan migrate --seed
```

### 3.5 Tautkan storage publik

```bash
php artisan storage:link
```

Membuat symlink `public/storage` → `storage/app/public` agar logo sekolah,
gambar berita, dan foto galeri dapat diakses browser. Berkas pendaftar **tidak**
melewati folder ini — lihat [bagian 12](#12-keamanan-berkas).

Bila hosting melarang symlink, salin manual isinya atau jalankan
`php artisan storage:link --relative`.

### 3.6 Bangun aset frontend

```bash
npm run build       # untuk produksi / sekali jalan
npm run dev         # untuk pengembangan dengan hot reload
```

---

## 4. Konfigurasi Database

### 4.1 Opsi A — SQLite (bawaan pengembangan)

Tidak perlu server database. Cukup buat berkasnya:

```bash
touch database/database.sqlite
```

`.env`:

```dotenv
DB_CONNECTION=sqlite
DB_FOREIGN_KEYS=true
```

`DB_DATABASE` sengaja dikosongkan agar Laravel memakai
`database/database.sqlite`. Berkas ini sudah masuk `.gitignore`.

### 4.2 Opsi B — MySQL / MariaDB (produksi)

Buat database dan pengguna khusus:

```sql
CREATE DATABASE ppdb CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci;
CREATE USER 'ppdb'@'localhost' IDENTIFIED BY 'kata-sandi-yang-kuat';
GRANT ALL PRIVILEGES ON ppdb.* TO 'ppdb'@'localhost';
FLUSH PRIVILEGES;
```

`.env`:

```dotenv
DB_CONNECTION=mysql
DB_HOST=127.0.0.1
DB_PORT=3306
DB_DATABASE=ppdb
DB_USERNAME=ppdb
DB_PASSWORD=kata-sandi-yang-kuat
```

Gunakan `127.0.0.1`, bukan `localhost`, agar PHP memakai TCP dan tidak mencari
socket Unix yang mungkin tidak ada di Windows.

Uji koneksi:

```bash
php artisan db:show
```

### 4.3 Catatan teknis penomoran pendaftaran

Nomor pendaftaran harus aman terhadap dua pengiriman bersamaan. Mekanismenya
berbeda per mesin database dan sudah ditangani otomatis:

| Mesin | Mekanisme |
| --- | --- |
| MySQL / MariaDB | Baris penghitung dikunci dengan `SELECT ... FOR UPDATE` (`lockForUpdate()`) |
| SQLite | `lockForUpdate()` diabaikan SQLite, sehingga kunci tulis diambil saat `BEGIN` melalui `transaction_mode = IMMEDIATE` pada `config/database.php`, ditambah `busy_timeout` agar penulis kedua menunggu, bukan gagal |

Tidak ada yang perlu diubah saat berpindah mesin. Keduanya sudah diuji.

---

## 5. Migrasi Database

### 5.1 Perintah yang sering dipakai

```bash
# Jalankan migrasi yang belum pernah dijalankan
php artisan migrate

# Lihat migrasi mana yang sudah/belum jalan
php artisan migrate:status

# Jalankan migrasi + seluruh seeder dasar
php artisan migrate --seed

# Kosongkan seluruh tabel lalu migrasi ulang dari nol  (MENGHAPUS SEMUA DATA)
php artisan migrate:fresh

# Sama seperti di atas, sekaligus mengisi data awal   (MENGHAPUS SEMUA DATA)
php artisan migrate:fresh --seed

# Batalkan batch migrasi terakhir
php artisan migrate:rollback

# Batalkan sejumlah langkah tertentu
php artisan migrate:rollback --step=3
```

Di server produksi Laravel akan meminta konfirmasi. Tambahkan `--force` bila
dijalankan dari skrip otomatis:

```bash
php artisan migrate --force
```

> `migrate:fresh` dan `migrate:refresh` menghapus seluruh isi database.
> Jangan pernah menjalankannya di produksi tanpa cadangan.

### 5.2 Urutan migrasi

Migrasi dinamai berurutan agar foreign key terbentuk pada urutan yang benar:

| Kelompok | Berkas | Isi |
| --- | --- | --- |
| Bawaan Laravel | `0001_01_01_*` | `users`, `cache`, `jobs`, `sessions` |
| Pengguna | `2026_01_01_000001` | Menambah `username`, `role`, `is_active`, soft delete pada `users` |
| Konfigurasi | `2026_01_01_0000{10..13}` | `academic_years`, `registration_waves`, `admission_tracks`, `programs` |
| Data pendaftar | `2026_01_01_0000{20..23}` | `applicants`, `applicant_addresses`, `parent_guardians`, `previous_schools` |
| Pendaftaran | `2026_01_01_0000{30,31}` | `registrations`, `registration_counters` |
| Berkas | `2026_01_01_0000{40..43}` | `document_types`, pivot persyaratan, `registration_documents`, log verifikasi |
| Hasil | `2026_01_01_0000{50..52}` | `selection_results`, `reregistrations`, `generated_documents` |
| Informasi | `2026_01_01_0000{60..62}` | `notifications`, `ppdb_schedules`, `announcements` |
| CMS | `2026_01_01_000070` | 8 tabel konten website + `settings` |
| Audit | `2026_01_01_000080` | `activity_logs` |

### 5.3 Menambah migrasi baru

```bash
php artisan make:migration tambah_kolom_x_pada_tabel_y
php artisan migrate
```

Jangan mengubah berkas migrasi yang sudah pernah jalan di produksi; buat migrasi
baru sebagai gantinya.

---

## 6. Seeder

### 6.1 Seeder dasar (aman untuk produksi)

```bash
php artisan db:seed
```

Menjalankan empat seeder secara berurutan:

| Seeder | Isi |
| --- | --- |
| `SettingsSeeder` | Identitas sekolah, kontak, teks beranda, FAQ |
| `AdminUserSeeder` | Satu akun super admin |
| `PpdbConfigurationSeeder` | 1 tahun ajaran aktif, 2 gelombang, 3 jalur, 3 program, 8 jenis berkas, jadwal publik |
| `CmsSeeder` | Profil sekolah, program unggulan, fasilitas |

Kata sandi super admin diambil dari `.env` supaya tidak ada kredensial tertanam
di dalam kode:

```dotenv
SEED_SUPER_ADMIN_USERNAME=superadmin
SEED_SUPER_ADMIN_EMAIL=superadmin@sekolah.sch.id
SEED_SUPER_ADMIN_PASSWORD=kata-sandi-awal-yang-kuat
```

Bila `SEED_SUPER_ADMIN_PASSWORD` dikosongkan, digunakan `Admin#12345`. **Ganti
kata sandi setelah login pertama.**

Gelombang pertama sengaja dibuka relatif terhadap tanggal instalasi agar alur
pendaftaran langsung bisa dicoba. Sesuaikan tanggal sebenarnya dari menu
**Gelombang**.

### 6.2 Data contoh (jangan di produksi)

```bash
php artisan db:seed --class=DemoSeeder
```

Menambah 12 pendaftar contoh pada berbagai tahap, dua akun panitia
(`admin.ppdb` dan `verifikator`, kata sandi `Demo#12345`), berita, album, dan
pengumuman. Seeder ini menolak berjalan bila `APP_ENV=production`.

Kode akses pendaftar contoh dibuat acak dan hanya disimpan sebagai hash,
sehingga tidak dapat dilihat. Untuk mencoba portal pendaftar, daftar sendiri
lewat `/daftar` atau gunakan **Reset Kode Akses** pada detail pendaftar.

### 6.3 Menjalankan ulang satu seeder

```bash
php artisan db:seed --class=SettingsSeeder
```

Seluruh seeder memakai `updateOrCreate`, jadi aman dijalankan berulang tanpa
menggandakan data.

---

## 7. Pindah SQLite ke MySQL

Jika mulai dengan SQLite lalu ingin naik ke MySQL.

### 7.1 Bila data lama tidak perlu dipertahankan

```bash
# 1. Siapkan database MySQL (lihat bagian 4.2)
# 2. Ubah .env ke MySQL
# 3. Bersihkan konfigurasi yang ter-cache
php artisan config:clear

# 4. Migrasi dari nol dan isi data awal
php artisan migrate:fresh --seed
```

### 7.2 Bila data lama perlu dipindahkan

Cara paling aman adalah memindahkan tabel per tabel melalui Laravel, sehingga
tipe data dan urutan foreign key ditangani Eloquent.

**Langkah 1 — cadangkan lebih dulu.**

```bash
cp database/database.sqlite database/database.sqlite.backup
```

**Langkah 2 — tambahkan koneksi sementara ke `config/database.php`:**

```php
'sqlite_lama' => [
    'driver' => 'sqlite',
    'database' => database_path('database.sqlite.backup'),
    'prefix' => '',
    'foreign_key_constraints' => false,
],
```

**Langkah 3 — arahkan `.env` ke MySQL, lalu siapkan skemanya:**

```bash
php artisan config:clear
php artisan migrate:fresh          # tanpa --seed, karena data datang dari SQLite
```

**Langkah 4 — salin isi tabel sesuai urutan ketergantungan:**

```bash
php artisan tinker
```

```php
$tables = [
    'users', 'settings',
    'academic_years', 'registration_waves', 'admission_tracks', 'programs',
    'document_types', 'admission_track_document_requirements',
    'applicants', 'applicant_addresses', 'parent_guardians', 'previous_schools',
    'registrations', 'registration_counters',
    'registration_documents', 'document_verification_logs',
    'selection_results', 'reregistrations', 'generated_documents',
    'notifications', 'ppdb_schedules', 'announcements',
    'school_profiles', 'school_programs', 'facilities',
    'news', 'galleries', 'gallery_images', 'downloads',
    'activity_logs',
];

Schema::disableForeignKeyConstraints();

foreach ($tables as $table) {
    DB::connection('sqlite_lama')->table($table)->orderBy('id')->chunk(500, function ($rows) use ($table) {
        DB::table($table)->insert(collect($rows)->map(fn ($r) => (array) $r)->all());
    });
    echo $table.' : '.DB::table($table)->count().PHP_EOL;
}

Schema::enableForeignKeyConstraints();
```

Tabel `notifications`, `document_verification_logs`, dan `activity_logs` tidak
memiliki kolom `id` berurutan pada beberapa kasus; bila `orderBy('id')` gagal,
ganti dengan `orderBy('created_at')`.

**Langkah 5 — pindahkan juga berkas fisiknya.** Isi `storage/app/private/` dan
`storage/app/public/` tidak tersimpan di database, jadi salin apa adanya ke
server baru.

**Langkah 6 — verifikasi, lalu bersihkan.**

```bash
php artisan db:show --counts
```

Cocokkan jumlah baris, lalu hapus koneksi `sqlite_lama` dari
`config/database.php` dan hapus berkas `.sqlite.backup`.

---

## 8. Menjalankan Aplikasi

```bash
npm run start        # server PHP + Vite sekaligus (butuh perintah `php` di PATH)
```

Atau dua terminal terpisah:

```bash
php artisan serve    # http://localhost:8000
npm run dev          # Vite dengan hot reload
```

Bila `php` belum dikenali pada Windows PowerShell, tambahkan sekali:

```powershell
[Environment]::SetEnvironmentVariable(
    'Path',
    "C:\tools\php855;C:\Composer;" + [Environment]::GetEnvironmentVariable('Path','User'),
    'User'
)
```

Sesuaikan `C:\tools\php855` dengan lokasi PHP Anda, lalu buka ulang PowerShell.

---

## 9. Akun dan Cara Login

### Panitia / admin

**http://localhost:8000/admin/login** — dapat memakai username atau email.

| Username | Kata sandi | Peran | Akses |
| --- | --- | --- | --- |
| `superadmin` | dari `.env` (bawaan `Admin#12345`) | Super Admin | Seluruh menu, akun admin, pengaturan, hapus pendaftar |
| `admin.ppdb` | `Demo#12345` | Admin PPDB | Konfigurasi PPDB, konten, seleksi, publikasi, daftar ulang |
| `verifikator` | `Demo#12345` | Verifikator | Verifikasi berkas dan penyelesaian verifikasi |

Dua akun terakhir hanya ada bila `DemoSeeder` dijalankan. Hapus sebelum sistem
dipakai sungguhan.

### Calon peserta didik

**http://localhost:8000/cek-status** — masuk dengan **nomor pendaftaran + kode
akses** yang diperoleh setelah mengirim formulir. Tidak ada username/password.

---

## 10. Skema Database

38 tabel: 29 tabel domain, 1 tabel penghitung nomor, dan 8 tabel bawaan Laravel
(`users`, `sessions`, `cache`, `cache_locks`, `jobs`, `job_batches`,
`failed_jobs`, `password_reset_tokens`).

### Relasi inti

```
Applicant
├── hasOne  ApplicantAddress
├── hasMany ParentGuardian        (father / mother / guardian)
├── hasOne  PreviousSchool
└── hasMany Registration

Registration
├── belongsTo Applicant, AcademicYear, RegistrationWave, AdmissionTrack, Program
├── hasMany   RegistrationDocument
├── hasOne    SelectionResult
├── hasOne    Reregistration
├── hasMany   Notification
└── hasMany   GeneratedDocument

RegistrationDocument
├── belongsTo Registration, DocumentType
└── hasMany   DocumentVerificationLog
```

### Kolom status pada `registrations`

Status sengaja dipisah tiga kolom agar setiap tahap dapat ditelusuri sendiri:

| Kolom | Nilai |
| --- | --- |
| `registration_status` | `draft`, `submitted`, `under_review`, `revision_required`, `verified` |
| `selection_status` | `pending`, `reserve`, `accepted`, `rejected` |
| `reregistration_status` | `not_required`, `pending`, `completed`, `expired`, `withdrawn` |

`selection_status` baru berubah dari `pending` **setelah hasil dipublikasikan**,
sehingga keputusan yang masih draft tidak pernah bocor ke pendaftar maupun ke
hasil ekspor laporan.

### Soft delete

`users`, `applicants`, `registrations`, `programs`, `admission_tracks`,
`document_types`, `announcements`, dan `news` memakai soft delete. Penghapusan
permanen pendaftar hanya tersedia bagi super admin.

---

## 11. Alur Sistem

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

Nomor dibuat **hanya saat final submit**, di dalam transaksi database, memakai
baris penghitung terkunci. Tidak memakai `MAX()+1`, sehingga dua pengiriman
bersamaan tidak mungkin memperoleh nomor sama. Menghapus pendaftar tidak
membuat nomornya dipakai ulang.

### Kode akses

Delapan karakter dari alfabet `ABCDEFGHJKLMNPQRSTUVWXYZ23456789` (tanpa `0`,
`O`, `1`, `I` agar tidak ambigu saat dicetak). Database hanya menyimpan
hash-nya; kode asli ditampilkan **satu kali** pada halaman sukses dan pada PDF
bukti pendaftaran.

Panitia pun tidak dapat membacanya kembali. Bila hilang, panitia menerbitkan
kode baru lewat **Reset Kode Akses**, yang sekaligus menghentikan seluruh sesi
lama pendaftar tersebut dan tercatat pada activity log.

---

## 12. Keamanan Berkas

Berkas pendaftar **tidak pernah** berada di `public/`:

```
storage/app/private/ppdb/{tahun}/{nomor-pendaftaran}/{kode-berkas}/{acak}.{ext}
```

- Nama berkas di disk diacak 40 karakter; nama asli hanya metadata di database.
- Setiap unduhan melewati controller yang memeriksa policy lebih dulu.
- Validasi ekstensi, MIME type, dan ukuran dilakukan di server, bukan hanya di JavaScript.
- Pendaftar A yang mencoba membuka berkas pendaftar B menerima `403`.
- NIK dan nomor Kartu Keluarga ditampilkan tersamar (`5203********1234`) kecuali
  bagi Admin PPDB dan Super Admin pada halaman detail.

Pastikan `storage/app/private` tidak dapat diakses langsung dari web. Document
root web server harus menunjuk ke `public/`, bukan ke akar proyek.

---

## 13. PWA

| Rute | Isi |
| --- | --- |
| `GET /manifest.webmanifest` | Dibuat dari Pengaturan, sehingga nama aplikasi mengikuti nama sekolah |
| `GET /sw.js` | Service worker, tidak pernah di-cache sendiri |
| `GET /offline` | Halaman cadangan saat luring |

Strategi cache:

| Jenis | Strategi |
| --- | --- |
| Aset build, ikon | Cache first (nama berkas sudah ber-hash) |
| Halaman publik | Network first, cache sebagai cadangan |
| `/admin`, `/pendaftar`, `/daftar`, `/cek-status` | **Network only, tidak pernah disimpan** |

Aksi yang membutuhkan koneksi (unggah, kirim, tindakan admin) diblokir saat
luring dengan pesan yang jelas — tidak ada keberhasilan palsu.

---

## 14. Perawatan Berkala

```bash
# Hapus draft yang tidak pernah dikirim beserta berkasnya (bawaan 30 hari)
php artisan ppdb:prune-drafts --days=30

# Lihat yang akan dihapus tanpa menghapus
php artisan ppdb:prune-drafts --dry-run
```

Draft terbengkalai menahan NISN dan berkas yang sudah diunggah, sehingga siswa
yang sama tidak bisa mendaftar ulang. Perintah ini melepaskannya.

Sudah terjadwal harian pukul 02.00 pada `routes/console.php`. Aktifkan dengan
menambahkan satu baris cron:

```cron
* * * * * cd /path/ke/proyek && php artisan schedule:run >> /dev/null 2>&1
```

---

## 15. Pengujian

```bash
php artisan test                       # seluruh suite
php artisan test --filter=NamaTest     # satu berkas
```

178 tes, 1093 assertion. Bawaannya berjalan di SQLite in-memory. Sebelum rilis,
jalankan ulang di atas MySQL dengan mengubah dua baris pada `phpunit.xml`:

```xml
<env name="DB_CONNECTION" value="mysql"/>
<env name="DB_DATABASE" value="ppdb_testing"/>
```

Buat database `ppdb_testing` sekali lebih dulu. Seluruh tes lulus pada kedua
mesin database.

| Berkas | Yang diuji |
| --- | --- |
| `RegistrationWizardTest` | Siklus penuh dari konfigurasi sampai laporan |
| `RegistrationNumberTest` | Format, urutan, keunikan, dan antrean nomor |
| `RegistrationSubmissionTest` | Transaksi submit, rollback, kode akses |
| `DuplicateApplicantTest` | Satu NISN satu pendaftaran per tahun ajaran |
| `AccessCodeAuthenticationTest`, `AccessCodeRateLimitTest` | Login pendaftar dan pembatasan percobaan |
| `ApplicantDocumentAuthorizationTest`, `AdminDocumentAuthorizationTest` | Kepemilikan dan akses berkas |
| `DocumentUploadValidationTest` | Ekstensi, MIME, ukuran, penggantian berkas |
| `DocumentVerificationTest`, `DocumentRevisionTest` | Verifikasi, catatan revisi, unggah ulang |
| `RegistrationVerificationTest` | Syarat penyelesaian verifikasi |
| `SelectionAuthorizationTest`, `SelectionPublishingTest` | Hak seleksi, pemisahan draft dan publikasi |
| `ReregistrationTest` | Daftar ulang hanya untuk yang diterima dan sudah diumumkan |
| `PdfReceiptTest` | Bukti pendaftaran, QR, penyimpanan privat |
| `ReportExportTest` | Ekspor PDF, CSV, XLSX dan kebocoran hasil belum terbit |
| `PwaPublicRouteTest` | Manifest, service worker, halaman luring |
| `SecurityHardeningTest` | Header, penyamaran identitas, isolasi sesi, disk privat |
| `AdminPageSmokeTest` | Seluruh halaman admin, portal, dan wizard dapat dirender |
| `ExampleTest` | Website tetap tampil pada database kosong |

Pemeriksaan gaya kode:

```bash
./vendor/bin/pint          # perbaiki
./vendor/bin/pint --test   # periksa saja
```

---

## 16. Struktur Kode

```
app/
├── Console/Commands/  Perintah perawatan
├── Enums/             Status pendaftaran, seleksi, daftar ulang, berkas, peran
├── Http/
│   ├── Controllers/   Public, Registration, Applicant, Admin
│   ├── Middleware/    Sesi pendaftar, peran, no-store, security headers
│   └── Requests/      Validasi per langkah wizard
├── Models/            Model beserta relasinya
├── Policies/          Izin admin atas pendaftaran dan berkas
├── Services/          Logika domain
├── Support/           Sesi pendaftar, draft wizard, pengaturan, helper
└── View/Composers/    Data bersama layout portal

database/
├── migrations/        Skema
└── seeders/           Data awal dan data contoh

resources/
├── css/js/            Tailwind, Alpine, Chart.js, service worker helper
└── views/             Blade: public, registration, applicant, admin, pdf, errors
```

Service utama:

| Service | Tanggung jawab |
| --- | --- |
| `RegistrationService` | Draft, penyimpanan per langkah, transaksi final submit |
| `RegistrationNumberService` | Penomoran 10 digit yang aman terhadap konkurensi |
| `AccessCodeService` | Pembuatan, verifikasi, dan reset kode akses |
| `DocumentService` | Penyimpanan privat, validasi server-side, relokasi berkas |
| `VerificationService` | Keputusan per berkas, audit log, gerbang penyelesaian verifikasi |
| `SelectionService` | Penetapan hasil, publikasi, pembatalan draft |
| `ReregistrationService` | Status daftar ulang |
| `PdfService` | Bukti pendaftaran A4 + QR, dokumen pemulihan kode akses |
| `DashboardService` | Agregat dashboard admin |
| `NotificationService`, `ActivityLogger` | Notifikasi pendaftar dan jejak audit panitia |

CRUD sederhana ditangani langsung di controller; service dipakai hanya untuk
alur yang memang berlapis.

---

## 17. Deploy ke Produksi

```bash
git pull

composer install --no-dev --optimize-autoloader
npm ci && npm run build

php artisan migrate --force

php artisan config:cache
php artisan route:cache
php artisan view:cache
```

Daftar periksa sebelum rilis:

- [ ] `APP_ENV=production` dan `APP_DEBUG=false`
- [ ] `APP_KEY` sudah dibuat dan tidak pernah diganti setelah ada data
- [ ] `APP_URL` memakai domain sebenarnya dengan HTTPS
- [ ] `SESSION_SECURE_COOKIE=true`
- [ ] Database MySQL dengan pengguna khusus, bukan `root`
- [ ] Document root web server menunjuk ke `public/`
- [ ] `storage/` dan `bootstrap/cache/` dapat ditulis web server
- [ ] `php artisan storage:link` sudah dijalankan
- [ ] Cron `schedule:run` aktif
- [ ] Kata sandi super admin sudah diganti
- [ ] Akun `DemoSeeder` sudah dihapus bila sempat dipakai
- [ ] Cadangan database terjadwal

Setelah mengubah `.env` di server yang memakai cache konfigurasi, jalankan
`php artisan config:cache` ulang. Perubahan `.env` tidak terbaca selama
konfigurasi masih ter-cache.

---

## 18. Pemecahan Masalah

| Gejala | Penyebab dan solusi |
| --- | --- |
| `could not find driver` | Ekstensi `pdo_mysql` atau `pdo_sqlite` belum aktif di `php.ini` |
| `SQLSTATE[HY000] [1045] Access denied` | Username/kata sandi MySQL pada `.env` salah |
| `SQLSTATE[HY000] [2002]` | Server MySQL mati, atau `DB_HOST=localhost` diganti `127.0.0.1` |
| `no such table: ...` | Migrasi belum dijalankan: `php artisan migrate` |
| `database is locked` (SQLite) | Ada proses lain memakai berkas yang sama; tutup Tinker/tes yang berjalan |
| Perubahan `.env` tidak terbaca | `php artisan config:clear` |
| Halaman tanpa gaya / 404 aset | `npm run build` belum dijalankan, atau `APP_URL` salah |
| Logo dan gambar tidak muncul | `php artisan storage:link` belum dijalankan |
| `The stream or file ... could not be opened` | `storage/` tidak dapat ditulis; perbaiki kepemilikan folder |
| `419 Page Expired` | Halaman terbuka terlalu lama, muat ulang; periksa juga `SESSION_DOMAIN` |
| QR Code tidak muncul di PDF | Ekstensi `gd` belum aktif |
| Ekspor XLSX gagal | Ekstensi `zip` belum aktif |
| Tanggal tampil dalam Bahasa Inggris | Ekstensi `intl` belum aktif, atau `APP_LOCALE` bukan `id` |
| `php` tidak dikenali di PowerShell | PHP belum ada di PATH, lihat [bagian 8](#8-menjalankan-aplikasi) |

Log aplikasi ada di `storage/logs/laravel.log`.
