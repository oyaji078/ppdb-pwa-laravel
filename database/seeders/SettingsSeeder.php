<?php

namespace Database\Seeders;

use App\Models\Setting;
use App\Support\SettingsRepository;
use Illuminate\Database\Seeder;

class SettingsSeeder extends Seeder
{
    public function run(): void
    {
        $definitions = [
            ['school_name', 'SMA Islam Hamzanwadi Peneda', 'string', 'school', 'Nama Sekolah'],
            ['school_short_name', 'SMA Islam Hamzanwadi', 'string', 'school', 'Nama Singkat'],
            ['school_logo', '', 'string', 'school', 'Logo Sekolah'],
            ['school_address', 'Peneda, Kabupaten Lombok Timur, Nusa Tenggara Barat', 'string', 'school', 'Alamat Sekolah'],
            ['school_phone', '(0376) 000000', 'string', 'school', 'Telepon Sekolah'],
            ['school_email', 'info@smaislamhamzanwadi.sch.id', 'string', 'school', 'Email Sekolah'],
            ['school_website', '', 'string', 'school', 'Website Sekolah'],

            ['admission_name', 'PPDB', 'string', 'admission', 'Istilah Penerimaan'],
            ['admission_tagline', 'Penerimaan Peserta Didik Baru', 'string', 'admission', 'Tagline Penerimaan'],

            ['contact_whatsapp', '', 'string', 'contact', 'WhatsApp Panitia'],
            ['contact_email', 'ppdb@smaislamhamzanwadi.sch.id', 'string', 'contact', 'Email Panitia'],
            ['contact_person', 'Panitia PPDB', 'string', 'contact', 'Nama Kontak Panitia'],

            ['max_upload_size', '2048', 'integer', 'system', 'Ukuran Unggah Maksimal (KB)'],
            ['maintenance_message', '', 'string', 'system', 'Pesan Pemeliharaan'],

            ['hero_title', 'Bergabung Bersama Kami', 'string', 'homepage', 'Judul Hero'],
            ['hero_subtitle', 'Wujudkan masa depan gemilang dengan pendidikan berkualitas yang memadukan ilmu pengetahuan dan akhlak mulia.', 'string', 'homepage', 'Subjudul Hero'],
            [
                'faq',
                implode("\n", [
                    'Siapa saja yang dapat mendaftar?|Lulusan SMP/MTs/Paket B tahun berjalan maupun tahun sebelumnya yang memenuhi persyaratan usia dan administrasi.',
                    'Apakah pendaftaran dipungut biaya?|Pendaftaran melalui sistem ini tidak dipungut biaya apa pun.',
                    'Bagaimana jika saya lupa kode akses?|Hubungi panitia PPDB dengan membawa identitas diri. Panitia dapat menerbitkan kode akses baru.',
                    'Berapa lama proses verifikasi berkas?|Verifikasi dilakukan pada hari kerja sesuai jadwal yang tercantum pada halaman jadwal PPDB.',
                ]),
                'string',
                'homepage',
                'Pertanyaan yang Sering Diajukan',
            ],
        ];

        foreach ($definitions as [$key, $value, $type, $group, $label]) {
            Setting::query()->updateOrCreate(
                ['key' => $key],
                ['value' => $value, 'type' => $type, 'group' => $group, 'label' => $label]
            );
        }

        app(SettingsRepository::class)->flush();
    }
}
