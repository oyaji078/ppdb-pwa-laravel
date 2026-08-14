<?php

namespace Database\Seeders;

use App\Enums\RegistrationStatus;
use App\Enums\UserRole;
use App\Models\AcademicYear;
use App\Models\AdmissionTrack;
use App\Models\Announcement;
use App\Models\Applicant;
use App\Models\Gallery;
use App\Models\News;
use App\Models\Program;
use App\Models\Registration;
use App\Models\RegistrationWave;
use App\Models\User;
use App\Services\AccessCodeService;
use App\Services\RegistrationNumberService;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;

/**
 * Sample data for demos and manual testing. Never run in production.
 *
 *   php artisan db:seed --class=DemoSeeder
 *
 * Documents are not created because there are no real files to attach; use the
 * public form to exercise the upload and verification path.
 */
class DemoSeeder extends Seeder
{
    public function run(): void
    {
        if (app()->isProduction()) {
            $this->command?->error('DemoSeeder tidak boleh dijalankan pada environment production.');

            return;
        }

        $year = AcademicYear::current();

        if ($year === null) {
            $this->command?->error('Belum ada tahun ajaran aktif. Jalankan php artisan db:seed terlebih dahulu.');

            return;
        }

        $this->seedStaff();
        $this->seedContent($year);
        $this->seedApplicants($year);

        $this->command?->info('Data demo dibuat. Jangan gunakan pada instalasi produksi.');
    }

    private function seedStaff(): void
    {
        $staff = [
            ['admin.ppdb', 'Admin PPDB Demo', 'admin.ppdb@ppdb.test', UserRole::AdminPpdb],
            ['verifikator', 'Verifikator Demo', 'verifikator@ppdb.test', UserRole::Verifier],
        ];

        foreach ($staff as [$username, $name, $email, $role]) {
            User::query()->updateOrCreate(
                ['username' => $username],
                [
                    'name' => $name,
                    'email' => $email,
                    'password' => 'Demo#12345',
                    'role' => $role,
                    'is_active' => true,
                    'email_verified_at' => now(),
                ]
            );
        }
    }

    private function seedContent(AcademicYear $year): void
    {
        $news = [
            ['Sekolah Raih Juara Umum Olimpiade Sains Tingkat Kabupaten', 'Tim olimpiade sekolah berhasil membawa pulang piala juara umum pada ajang tahunan tingkat kabupaten.'],
            ['Pembukaan Pendaftaran Peserta Didik Baru', 'Panitia resmi membuka pendaftaran peserta didik baru melalui laman resmi sekolah.'],
            ['Kegiatan Bakti Sosial di Lingkungan Sekitar Sekolah', 'Peserta didik bersama guru mengadakan bakti sosial dan pembagian sembako bagi warga sekitar.'],
        ];

        foreach ($news as $index => [$title, $excerpt]) {
            News::query()->updateOrCreate(
                ['slug' => Str::slug($title)],
                [
                    'title' => $title,
                    'excerpt' => $excerpt,
                    'content' => $excerpt."\n\nKegiatan berlangsung dengan lancar dan mendapat sambutan hangat dari seluruh warga sekolah. "
                        .'Sekolah berkomitmen untuk terus mengadakan kegiatan serupa pada masa mendatang.',
                    'is_published' => true,
                    'published_at' => now()->subDays(($index + 1) * 3),
                ]
            );
        }

        Gallery::query()->updateOrCreate(
            ['slug' => 'kegiatan-sekolah'],
            ['title' => 'Kegiatan Sekolah', 'description' => 'Dokumentasi kegiatan harian dan acara sekolah.', 'is_published' => true]
        );

        $announcements = [
            ['Jadwal Verifikasi Berkas Gelombang 1', 'public', 'Verifikasi berkas gelombang 1 dilaksanakan pada hari kerja pukul 08.00 sampai 14.00 WITA di ruang panitia.'],
            ['Panduan Unggah Berkas', 'applicants', 'Pastikan berkas yang diunggah terbaca jelas, tidak terpotong, dan sesuai format yang ditentukan pada halaman persyaratan.'],
            ['Ketentuan Daftar Ulang', 'accepted', 'Peserta yang dinyatakan diterima wajib menyelesaikan daftar ulang sesuai jadwal dengan membawa berkas asli untuk dicocokkan.'],
        ];

        foreach ($announcements as $index => [$title, $audience, $content]) {
            Announcement::query()->updateOrCreate(
                ['slug' => Str::slug($title)],
                [
                    'academic_year_id' => $year->id,
                    'title' => $title,
                    'content' => $content,
                    'audience' => $audience,
                    'is_published' => true,
                    'published_at' => now()->subDays($index + 1),
                ]
            );
        }
    }

    private function seedApplicants(AcademicYear $year): void
    {
        $wave = RegistrationWave::query()->where('academic_year_id', $year->id)->orderBy('code')->first();
        $tracks = AdmissionTrack::query()->where('academic_year_id', $year->id)->active()->get();
        $programs = Program::query()->where('academic_year_id', $year->id)->active()->get();

        if ($wave === null || $tracks->isEmpty() || $programs->isEmpty()) {
            $this->command?->warn('Konfigurasi PPDB belum lengkap, pendaftar demo dilewati.');

            return;
        }

        $names = [
            ['Ahmad Fauzi Ramadhan', 'L'], ['Siti Nurhaliza Putri', 'P'], ['Muhammad Rizki Aditya', 'L'],
            ['Dewi Ayu Lestari', 'P'], ['Bagus Prasetyo Wibowo', 'L'], ['Nabila Zahra Amelia', 'P'],
            ['Fajar Nugroho Saputra', 'L'], ['Intan Permata Sari', 'P'], ['Rahmat Hidayat Nurdin', 'L'],
            ['Aisyah Kamila Rahma', 'P'], ['Yusuf Maulana Ibrahim', 'L'], ['Salsabila Anindya Putri', 'P'],
        ];

        $schools = ['SMP Negeri 1 Peneda', 'MTs Negeri 2 Lombok Timur', 'SMP Islam Hamzanwadi', 'SMP Negeri 3 Selong'];
        $numbers = app(RegistrationNumberService::class);
        $accessCodes = app(AccessCodeService::class);

        foreach ($names as $index => [$fullName, $gender]) {
            $nisn = str_pad((string) (9000000001 + $index), 10, '0', STR_PAD_LEFT);

            if (Applicant::query()->where('nisn', $nisn)->exists()) {
                continue;
            }

            DB::transaction(function () use (
                $index, $fullName, $gender, $nisn, $year, $wave, $tracks, $programs,
                $schools, $numbers, $accessCodes
            ): void {
                $applicant = Applicant::query()->create([
                    'nisn' => $nisn,
                    'nik' => str_pad((string) (5203010101100001 + $index), 16, '0', STR_PAD_LEFT),
                    'family_card_number' => str_pad((string) (5203010101100501 + $index), 16, '0', STR_PAD_LEFT),
                    'full_name' => $fullName,
                    'gender' => $gender,
                    'birth_place' => 'Lombok Timur',
                    'birth_date' => now()->subYears(15)->subDays($index * 11)->toDateString(),
                    'religion' => 'Islam',
                    'child_order' => ($index % 3) + 1,
                    'siblings_count' => $index % 4,
                    'phone' => '0812'.str_pad((string) (34567800 + $index), 8, '0', STR_PAD_LEFT),
                    'email' => Str::slug(Str::before($fullName, ' ')).$index.'@example.test',
                ]);

                $applicant->address()->create([
                    'province' => 'Nusa Tenggara Barat',
                    'regency' => 'Lombok Timur',
                    'district' => 'Peneda',
                    'village' => 'Peneda Gandor',
                    'postal_code' => '83651',
                    'address' => 'Jl. Pendidikan No. '.($index + 1).', Dusun Peneda, RT 00'.(($index % 5) + 1).' / RW 001',
                ]);

                $applicant->parentGuardians()->createMany([
                    [
                        'relationship' => 'father',
                        'name' => 'Bapak '.Str::before($fullName, ' '),
                        'birth_year' => 1980 + ($index % 8),
                        'education' => 'SMA/Sederajat',
                        'occupation' => ['Petani', 'Wiraswasta', 'Guru', 'Buruh'][$index % 4],
                        'monthly_income' => 'Rp1.000.000 - Rp2.000.000',
                        'phone' => '0813'.str_pad((string) (34567800 + $index), 8, '0', STR_PAD_LEFT),
                        'is_alive' => true,
                    ],
                    [
                        'relationship' => 'mother',
                        'name' => 'Ibu '.Str::before($fullName, ' '),
                        'birth_year' => 1984 + ($index % 6),
                        'education' => 'SMP/Sederajat',
                        'occupation' => 'Ibu Rumah Tangga',
                        'monthly_income' => 'Tidak Berpenghasilan',
                        'phone' => '0814'.str_pad((string) (34567800 + $index), 8, '0', STR_PAD_LEFT),
                        'is_alive' => true,
                    ],
                ]);

                $applicant->previousSchool()->create([
                    'school_name' => $schools[$index % count($schools)],
                    'npsn' => str_pad((string) (50201234 + $index), 8, '0', STR_PAD_LEFT),
                    'school_type' => $index % 3 === 1 ? 'MTs' : 'SMP',
                    'school_status' => $index % 2 === 0 ? 'Negeri' : 'Swasta',
                    'province' => 'Nusa Tenggara Barat',
                    'regency' => 'Lombok Timur',
                    'graduation_year' => (int) now()->year,
                ]);

                $registration = Registration::query()->create([
                    'applicant_id' => $applicant->id,
                    'academic_year_id' => $year->id,
                    'registration_wave_id' => $wave->id,
                    'admission_track_id' => $tracks[$index % $tracks->count()]->id,
                    'program_id' => $programs[$index % $programs->count()]->id,
                    'current_step' => 'review',
                    'statement_agreed' => true,
                ]);

                $registration->forceFill([
                    'registration_number' => $numbers->generate($year, $wave),
                    'access_code_hash' => $accessCodes->hash($accessCodes->generate()),
                    'registration_status' => $this->demoStatus($index),
                    'submitted_at' => now()->subDays(12 - $index),
                ])->save();

                if ($registration->registration_status === RegistrationStatus::Verified) {
                    $registration->forceFill(['verified_at' => now()->subDays(max(1, 10 - $index))])->save();
                }
            });
        }

        $this->command?->info(sprintf('%d pendaftar demo dibuat.', count($names)));
    }

    /**
     * Spread demo applicants across the pipeline so every admin screen has
     * something to show.
     */
    private function demoStatus(int $index): RegistrationStatus
    {
        return match ($index % 4) {
            0 => RegistrationStatus::Verified,
            1 => RegistrationStatus::UnderReview,
            2 => RegistrationStatus::RevisionRequired,
            default => RegistrationStatus::Submitted,
        };
    }
}
