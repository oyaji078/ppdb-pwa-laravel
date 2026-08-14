<?php

namespace Database\Seeders;

use App\Models\AcademicYear;
use App\Models\AdmissionTrack;
use App\Models\DocumentType;
use App\Models\PpdbSchedule;
use App\Models\Program;
use App\Models\RegistrationWave;
use Illuminate\Database\Seeder;

/**
 * Baseline PPDB configuration: one academic year with two waves, three tracks,
 * example programs, the standard document checklist and a public schedule.
 *
 * Everything here is editable from the admin panel afterwards.
 */
class PpdbConfigurationSeeder extends Seeder
{
    public function run(): void
    {
        // Intake year rolls over once the current school year is under way.
        $startYear = (int) now()->year + (now()->month >= 8 ? 1 : 0);

        $year = AcademicYear::query()->updateOrCreate(
            ['name' => sprintf('%d/%d', $startYear, $startYear + 1)],
            [
                'start_year' => $startYear,
                'end_year' => $startYear + 1,
                'is_active' => true,
                'registration_open' => true,
            ]
        );

        AcademicYear::query()->where('id', '!=', $year->id)->update(['is_active' => false]);

        $waves = $this->seedWaves($year);
        $tracks = $this->seedTracks($year);
        $this->seedPrograms($year);
        $documentTypes = $this->seedDocumentTypes($year);
        $this->seedTrackRequirements($tracks, $documentTypes);
        $this->seedSchedules($year, $waves);
    }

    /**
     * Windows are anchored to today so a fresh install always has one open
     * wave to register against. Real dates are set by the admin afterwards.
     *
     * @return array<string, RegistrationWave>
     */
    private function seedWaves(AcademicYear $year): array
    {
        $today = now()->startOfDay();

        $definitions = [
            ['Gelombang 1', '01', $today->copy()->subMonth(), $today->copy()->addMonths(2)->endOfDay(), 150],
            ['Gelombang 2', '02', $today->copy()->addMonths(2)->addDay(), $today->copy()->addMonths(4)->endOfDay(), 100],
        ];

        $waves = [];

        foreach ($definitions as [$name, $code, $start, $end, $quota]) {
            $waves[$code] = RegistrationWave::query()->updateOrCreate(
                ['academic_year_id' => $year->id, 'code' => $code],
                [
                    'name' => $name,
                    'start_at' => $start,
                    'end_at' => $end,
                    'quota' => $quota,
                    'is_active' => true,
                ]
            );
        }

        return $waves;
    }

    /**
     * @return array<string, AdmissionTrack>
     */
    private function seedTracks(AcademicYear $year): array
    {
        $definitions = [
            ['Reguler', 'reguler', 'Jalur pendaftaran umum untuk seluruh calon peserta didik.', 200, 1],
            ['Prestasi', 'prestasi', 'Jalur bagi calon peserta didik dengan prestasi akademik maupun non-akademik.', 40, 2],
            ['Afirmasi', 'afirmasi', 'Jalur bagi calon peserta didik dari keluarga kurang mampu.', 30, 3],
        ];

        $tracks = [];

        foreach ($definitions as [$name, $code, $description, $quota, $order]) {
            $tracks[$code] = AdmissionTrack::query()->updateOrCreate(
                ['academic_year_id' => $year->id, 'code' => $code],
                [
                    'name' => $name,
                    'description' => $description,
                    'quota' => $quota,
                    'is_active' => true,
                    'sort_order' => $order,
                ]
            );
        }

        return $tracks;
    }

    private function seedPrograms(AcademicYear $year): void
    {
        $definitions = [
            ['Matematika dan Ilmu Pengetahuan Alam', 'mipa', 'Peminatan sains untuk calon peserta didik yang berminat pada bidang eksakta.', 96, 1],
            ['Ilmu Pengetahuan Sosial', 'ips', 'Peminatan sosial dengan penekanan pada ekonomi, sosiologi, dan geografi.', 96, 2],
            ['Keagamaan', 'keagamaan', 'Peminatan keagamaan dengan penguatan ilmu-ilmu keislaman.', 64, 3],
        ];

        foreach ($definitions as [$name, $code, $description, $quota, $order]) {
            Program::query()->updateOrCreate(
                ['academic_year_id' => $year->id, 'code' => $code],
                [
                    'name' => $name,
                    'description' => $description,
                    'quota' => $quota,
                    'is_active' => true,
                    'sort_order' => $order,
                ]
            );
        }
    }

    /**
     * @return array<string, DocumentType>
     */
    private function seedDocumentTypes(AcademicYear $year): array
    {
        $definitions = [
            ['Pas Foto', 'pas-foto', 'Pas foto berwarna terbaru ukuran 3x4 dengan latar belakang polos.', true, ['jpg', 'jpeg', 'png'], 1024, 1],
            ['Kartu Keluarga', 'kk', 'Hasil pindai atau foto Kartu Keluarga yang masih berlaku.', true, ['pdf', 'jpg', 'jpeg', 'png'], 2048, 2],
            ['Akta Kelahiran', 'akta', 'Hasil pindai atau foto akta kelahiran.', true, ['pdf', 'jpg', 'jpeg', 'png'], 2048, 3],
            ['Bukti NISN', 'nisn', 'Tangkapan layar atau surat keterangan NISN dari sekolah asal.', true, ['pdf', 'jpg', 'jpeg', 'png'], 2048, 4],
            ['Ijazah / SKL', 'ijazah', 'Ijazah atau Surat Keterangan Lulus dari sekolah asal.', true, ['pdf', 'jpg', 'jpeg', 'png'], 2048, 5],
            ['Rapor Semester 1-5', 'rapor', 'Hasil pindai rapor semester 1 sampai 5 dalam satu berkas PDF.', true, ['pdf'], 4096, 6],
            ['Sertifikat Prestasi', 'sertifikat-prestasi', 'Sertifikat prestasi akademik maupun non-akademik.', false, ['pdf', 'jpg', 'jpeg', 'png'], 2048, 7],
            ['Bukti Afirmasi (KIP/KKS/PKH/SKTM)', 'afirmasi', 'Salah satu dari KIP, KKS, PKH, atau SKTM dari pemerintah desa/kelurahan.', false, ['pdf', 'jpg', 'jpeg', 'png'], 2048, 8],
        ];

        $types = [];

        foreach ($definitions as [$name, $code, $description, $required, $extensions, $maxSize, $order]) {
            $types[$code] = DocumentType::query()->updateOrCreate(
                ['academic_year_id' => $year->id, 'code' => $code],
                [
                    'name' => $name,
                    'description' => $description,
                    'is_required' => $required,
                    'requires_verification' => true,
                    'allowed_extensions' => $extensions,
                    'max_size_kb' => $maxSize,
                    'sort_order' => $order,
                    'is_active' => true,
                ]
            );
        }

        return $types;
    }

    /**
     * @param  array<string, AdmissionTrack>  $tracks
     * @param  array<string, DocumentType>  $types
     */
    private function seedTrackRequirements(array $tracks, array $types): void
    {
        $base = ['pas-foto', 'kk', 'akta', 'nisn', 'ijazah', 'rapor'];

        $matrix = [
            'reguler' => array_fill_keys($base, true),
            'prestasi' => array_merge(array_fill_keys($base, true), ['sertifikat-prestasi' => true]),
            'afirmasi' => array_merge(array_fill_keys($base, true), ['afirmasi' => true]),
        ];

        foreach ($matrix as $trackCode => $requirements) {
            $track = $tracks[$trackCode] ?? null;

            if ($track === null) {
                continue;
            }

            $sync = [];

            foreach ($requirements as $typeCode => $isRequired) {
                if (isset($types[$typeCode])) {
                    $sync[$types[$typeCode]->id] = ['is_required' => $isRequired];
                }
            }

            $track->documentTypes()->sync($sync);
        }
    }

    /**
     * Mirrors the wave windows so the public timeline matches the wave that is
     * actually open.
     *
     * @param  array<string, RegistrationWave>  $waves
     */
    private function seedSchedules(AcademicYear $year, array $waves): void
    {
        $wave1 = $waves['01'];
        $wave2 = $waves['02'];

        $definitions = [
            ['Pendaftaran Gelombang 1', '01', $wave1->start_at, $wave1->end_at, 'Pengisian formulir dan unggah berkas gelombang pertama.', 1],
            ['Verifikasi Berkas Gelombang 1', '01', $wave1->start_at->copy()->addDays(3), $wave1->end_at->copy()->addDays(5), 'Panitia memeriksa kelengkapan dan keabsahan berkas.', 2],
            ['Seleksi Gelombang 1', '01', $wave1->end_at->copy()->addDays(6), $wave1->end_at->copy()->addDays(10), 'Proses seleksi oleh panitia berdasarkan berkas terverifikasi.', 3],
            ['Pengumuman Gelombang 1', '01', $wave1->end_at->copy()->addDays(12), $wave1->end_at->copy()->addDays(13), 'Pengumuman hasil seleksi gelombang pertama.', 4],
            ['Daftar Ulang Gelombang 1', '01', $wave1->end_at->copy()->addDays(14), $wave1->end_at->copy()->addDays(25), 'Peserta yang diterima menyelesaikan daftar ulang.', 5],
            ['Pendaftaran Gelombang 2', '02', $wave2->start_at, $wave2->end_at, 'Pengisian formulir dan unggah berkas gelombang kedua.', 6],
            ['Pengumuman Gelombang 2', '02', $wave2->end_at->copy()->addDays(5), $wave2->end_at->copy()->addDays(6), 'Pengumuman hasil seleksi gelombang kedua.', 7],
        ];

        foreach ($definitions as [$title, $waveCode, $start, $end, $description, $order]) {
            PpdbSchedule::query()->updateOrCreate(
                ['academic_year_id' => $year->id, 'title' => $title],
                [
                    'registration_wave_id' => $waves[$waveCode]->id ?? null,
                    'description' => $description,
                    'start_at' => $start,
                    'end_at' => $end,
                    'is_public' => true,
                    'sort_order' => $order,
                ]
            );
        }
    }
}
