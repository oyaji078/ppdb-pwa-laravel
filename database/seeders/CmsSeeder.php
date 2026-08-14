<?php

namespace Database\Seeders;

use App\Models\Facility;
use App\Models\SchoolProfile;
use App\Models\SchoolProgram;
use Illuminate\Database\Seeder;
use Illuminate\Support\Str;

/**
 * Starter content for the public website so no page renders empty on a fresh
 * install. All of it is editable from the CMS.
 */
class CmsSeeder extends Seeder
{
    public function run(): void
    {
        $this->seedProfile();
        $this->seedPrograms();
        $this->seedFacilities();
    }

    private function seedProfile(): void
    {
        $sections = [
            [
                'sejarah',
                'Sejarah Singkat',
                'Sekolah ini berdiri sebagai wujud komitmen masyarakat dalam menyediakan pendidikan menengah yang memadukan penguasaan ilmu pengetahuan dengan pembinaan akhlak. Sejak awal berdiri, sekolah terus berkembang baik dari sisi jumlah peserta didik, mutu pembelajaran, maupun kelengkapan sarana.',
                1,
            ],
            [
                'visi',
                'Visi',
                'Terwujudnya peserta didik yang beriman, berakhlak mulia, berprestasi, serta mampu bersaing di tingkat regional maupun nasional.',
                2,
            ],
            [
                'misi',
                'Misi',
                "Menyelenggarakan pembelajaran yang aktif, kreatif, dan menyenangkan.\nMenanamkan nilai keislaman dalam seluruh aktivitas sekolah.\nMengembangkan potensi akademik dan non-akademik peserta didik.\nMembangun budaya disiplin, jujur, dan bertanggung jawab.\nMeningkatkan mutu tenaga pendidik secara berkelanjutan.",
                3,
            ],
            [
                'sambutan',
                'Sambutan Kepala Sekolah',
                'Assalamu alaikum warahmatullahi wabarakatuh. Selamat datang di laman resmi penerimaan peserta didik baru. Kami berkomitmen menghadirkan proses penerimaan yang transparan, mudah diakses, dan adil bagi seluruh calon peserta didik. Semoga laman ini memudahkan Ananda dan orang tua dalam mengikuti setiap tahapan.',
                4,
            ],
        ];

        foreach ($sections as [$section, $title, $content, $order]) {
            SchoolProfile::query()->updateOrCreate(
                ['section' => $section],
                ['title' => $title, 'content' => $content, 'sort_order' => $order, 'is_active' => true]
            );
        }
    }

    private function seedPrograms(): void
    {
        $programs = [
            ['Program Tahfiz', 'Pembinaan hafalan Al-Quran terjadwal dengan pendampingan pembimbing.', 'book-open-text', 1],
            ['Kelas Olimpiade', 'Pembinaan intensif bagi peserta didik yang disiapkan mengikuti kompetisi sains dan matematika.', 'trophy', 2],
            ['Literasi Digital', 'Pembekalan keterampilan komputer, perkantoran, dan literasi media untuk seluruh peserta didik.', 'monitor', 3],
            ['Bahasa Asing', 'Penguatan Bahasa Inggris dan Bahasa Arab melalui kegiatan pembiasaan harian.', 'languages', 4],
        ];

        foreach ($programs as [$name, $excerpt, $icon, $order]) {
            SchoolProgram::query()->updateOrCreate(
                ['slug' => Str::slug($name)],
                [
                    'name' => $name,
                    'excerpt' => $excerpt,
                    'description' => $excerpt,
                    'icon' => $icon,
                    'sort_order' => $order,
                    'is_active' => true,
                ]
            );
        }
    }

    private function seedFacilities(): void
    {
        $facilities = [
            ['Ruang Kelas Representatif', 'Ruang belajar yang bersih, terang, dan nyaman untuk kegiatan pembelajaran harian.', 'school', 1],
            ['Laboratorium Komputer', 'Perangkat komputer dengan koneksi internet untuk praktik informatika dan asesmen berbasis komputer.', 'monitor', 2],
            ['Perpustakaan', 'Koleksi buku pelajaran, referensi, dan bacaan umum yang dapat dipinjam peserta didik.', 'library', 3],
            ['Musala', 'Tempat ibadah untuk salat berjamaah dan kegiatan keagamaan sekolah.', 'moon-star', 4],
            ['Lapangan Olahraga', 'Sarana kegiatan olahraga dan upacara bendera.', 'volleyball', 5],
            ['Ruang UKS', 'Layanan kesehatan dasar bagi peserta didik selama kegiatan sekolah.', 'heart-pulse', 6],
        ];

        foreach ($facilities as [$name, $description, $icon, $order]) {
            Facility::query()->updateOrCreate(
                ['slug' => Str::slug($name)],
                [
                    'name' => $name,
                    'description' => $description,
                    'icon' => $icon,
                    'sort_order' => $order,
                    'is_active' => true,
                ]
            );
        }
    }
}
