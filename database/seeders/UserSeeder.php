<?php

namespace Database\Seeders;

use App\Models\User;
use App\Models\Opd;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Hash;
use Spatie\Permission\Models\Role;

class UserSeeder extends Seeder
{
    public function run(): void
    {
        // ✅ Pastikan semua role sudah ada
        Role::firstOrCreate(['name' => 'opd', 'guard_name' => 'web']);
        Role::firstOrCreate(['name' => 'verifikator', 'guard_name' => 'web']);
        Role::firstOrCreate(['name' => 'monitoring', 'guard_name' => 'web']);

        // Data OPD
        $opdData = [
            1 => ['name' => 'Badan Kepegawaian dan Pengembangan Sumber Daya Manusia', 'code' => 'BKPSDM'],
            2 => ['name' => 'Badan Kesatuan Bangsa dan Politik', 'code' => 'BAKESBANGPOL'],
            3 => ['name' => 'Badan Penanggulangan Bencana Daerah', 'code' => 'BPBD'],
            4 => ['name' => 'Badan Pengelolaan Pendapatan, Keuangan dan Aset Daerah', 'code' => 'BPPKAD'],
            5 => ['name' => 'Badan Perencanaan, Penelitian dan Pengembangan Daerah', 'code' => 'BAPELITBANGDA'],
            6 => ['name' => 'Bagian Administrasi Pembangunan', 'code' => 'BAGAPEM'],
            7 => ['name' => 'Bagian Administrasi Pemerintahan', 'code' => 'BAGPEM'],
            8 => ['name' => 'Bagian Hukum', 'code' => 'BAGKUM'],
            9 => ['name' => 'Bagian Kesejahteraan Rakyat', 'code' => 'BAGKESRA'],
            10 => ['name' => 'Bagian Organisasi', 'code' => 'BAGOR'],
            11 => ['name' => 'Bagian Pengadaan Barang dan Jasa', 'code' => 'BAGPBJ'],
            12 => ['name' => 'Bagian Perekonomian dan Sumber Daya Alam', 'code' => 'BAGSDA'],
            13 => ['name' => 'Bagian Protokol dan Komunikasi Pimpinan', 'code' => 'BAGPROKOPIM'],
            14 => ['name' => 'Bagian Umum', 'code' => 'BAGUM'],
            15 => ['name' => 'Dinas Kepemudaan, Olahraga dan Pariwisata', 'code' => 'PARIWISATA'],
            16 => ['name' => 'Dinas Kependudukan dan Pencatatan Sipil', 'code' => 'CAPIL'],
            17 => ['name' => 'Dinas Kesehatan', 'code' => 'DINKES'],
            18 => ['name' => 'Dinas Ketahanan Pangan', 'code' => 'KETPANG'],
            19 => ['name' => 'Dinas Komunikasi, Informatika, Statistik dan Persandian', 'code' => 'DISKOMINFO'],
            20 => ['name' => 'Dinas Koperasi, Usaha Mikro, Perdagangan Dan Perindustrian', 'code' => 'DKUPP'],
            21 => ['name' => 'Dinas Lingkungan Hidup', 'code' => 'DLH'],
            22 => ['name' => 'Dinas Pekerjaan Umum dan Penataan Ruang', 'code' => 'PUPR'],
            23 => ['name' => 'Dinas Pemberdayaan Masyarakat dan Desa', 'code' => 'PMD'],
            24 => ['name' => 'Dinas Pemberdayaan Perempuan, Perlindungan Anak, Pengendalian Penduduk dan Keluarga Berencana', 'code' => 'DPPPKB'],
            25 => ['name' => 'Dinas Penanaman Modal dan Pelayanan Terpadu Satu Pintu', 'code' => 'DPMPTSP'],
            26 => ['name' => 'Dinas Pendidikan dan Kebudayaan', 'code' => 'DIKDAYA'],
            27 => ['name' => 'Dinas Perhubungan', 'code' => 'DISHUB'],
            28 => ['name' => 'Dinas Perikanan', 'code' => 'PERIKANAN'],
            29 => ['name' => 'Dinas Perpustakaan dan Kearsipan', 'code' => 'ARSIP'],
            30 => ['name' => 'Dinas Pertanian', 'code' => 'PERTANIAN'],
            31 => ['name' => 'Dinas Perumahan, Kawasan Permukiman dan Pertanahan', 'code' => 'DPKPP'],
            32 => ['name' => 'Dinas Sosial', 'code' => 'DINSOS'],
            33 => ['name' => 'Dinas Tenaga Kerja', 'code' => 'DISNAKER'],
            34 => ['name' => 'Inspektorat', 'code' => 'INSPEKTORAT'],
            35 => ['name' => 'Kecamatan Bantaran', 'code' => 'BANTARAN'],
            36 => ['name' => 'Kecamatan Banyuanyar', 'code' => 'BANYUAANYAR'],
            37 => ['name' => 'Kecamatan Besuk', 'code' => 'BESUK'],
            38 => ['name' => 'Kecamatan Dringu', 'code' => 'DRINGU'],
            39 => ['name' => 'Kecamatan Gading', 'code' => 'GADING'],
            40 => ['name' => 'Kecamatan Gending', 'code' => 'GENDING'],
            41 => ['name' => 'Kecamatan Kotaanyar', 'code' => 'KOTAANYAR'],
            42 => ['name' => 'Kecamatan Kraksaan', 'code' => 'KRAKSAAN'],
            43 => ['name' => 'Kecamatan Krejengan', 'code' => 'KREJENGAN'],
            44 => ['name' => 'Kecamatan Krucil', 'code' => 'KRUCIL'],
            45 => ['name' => 'Kecamatan Kuripan', 'code' => 'KURIPAN'],
            46 => ['name' => 'Kecamatan Leces', 'code' => 'LECES'],
            47 => ['name' => 'Kecamatan Lumbang', 'code' => 'LUMBANG'],
            48 => ['name' => 'Kecamatan Maron', 'code' => 'MARON'],
            49 => ['name' => 'Kecamatan Paiton', 'code' => 'PAITON'],
            50 => ['name' => 'Kecamatan Pajarakan', 'code' => 'PAJARAKAN'],
            51 => ['name' => 'Kecamatan Pakuniran', 'code' => 'PAKUNIRAN'],
            52 => ['name' => 'Kecamatan Sukapura', 'code' => 'SUKAPURA'],
            53 => ['name' => 'Kecamatan Sumber', 'code' => 'SUMBER'],
            54 => ['name' => 'Kecamatan Sumberasih', 'code' => 'SUMBERASIH'],
            55 => ['name' => 'Kecamatan Tegalsiwalan', 'code' => 'TEGALSIWALAN'],
            56 => ['name' => 'Kecamatan Tiris', 'code' => 'TIRIS'],
            57 => ['name' => 'Kecamatan Tongas', 'code' => 'TONGAS'],
            58 => ['name' => 'Kecamatan Wonomerto', 'code' => 'WONOMERTO'],
            59 => ['name' => 'Puskesmas Bago', 'code' => 'PKMBAGO'],
            60 => ['name' => 'Puskesmas Bantaran', 'code' => 'PKMBANTARAN'],
            61 => ['name' => 'Puskesmas Banyuanyar', 'code' => 'PKMBANYUANYAR'],
            62 => ['name' => 'Puskesmas Besuk', 'code' => 'PKMBESUK'],
            63 => ['name' => 'Puskesmas Condong', 'code' => 'PKMCONDONG'],
            64 => ['name' => 'Puskesmas Curahtulis', 'code' => 'PKMCURAHTULIS'],
            65 => ['name' => 'Puskesmas Dringu', 'code' => 'PKMDRINGU'],
            66 => ['name' => 'Puskesmas Gending', 'code' => 'PKMGENDING'],
            67 => ['name' => 'Puskesmas Glagah', 'code' => 'PKMGLAGAH'],
            68 => ['name' => 'Puskesmas Jabungsisir', 'code' => 'PKMJABUNGSISIR'],
            69 => ['name' => 'Puskesmas Jorongan', 'code' => 'PKMJORONGAN'],
            70 => ['name' => 'Puskesmas Klenang Kidul', 'code' => 'PKMKLENANG'],
            71 => ['name' => 'Puskesmas Kotaanyar', 'code' => 'PKMKOTAANYAR'],
            72 => ['name' => 'Puskesmas Kraksaan', 'code' => 'PKMKRAKSAAN'],
            73 => ['name' => 'Puskesmas Krejengan', 'code' => 'PKMKREJENGAN'],
            74 => ['name' => 'Puskesmas Krucil', 'code' => 'PKMKRUCIL'],
            75 => ['name' => 'Puskesmas Kuripan', 'code' => 'PKMKURIPAN'],
            76 => ['name' => 'Puskesmas Leces', 'code' => 'PKMLECES'],
            77 => ['name' => 'Puskesmas Lumbang', 'code' => 'PKMLUMBANG'],
            78 => ['name' => 'Puskesmas Maron', 'code' => 'PKMMARON'],
            79 => ['name' => 'Puskesmas Paiton', 'code' => 'PKMPAITON'],
            80 => ['name' => 'Puskesmas Pajarakan', 'code' => 'PKMPAJARAKAN'],
            81 => ['name' => 'Puskesmas Pakuniran', 'code' => 'PKMPAKUNIRAN'],
            82 => ['name' => 'Puskesmas Ranugedang', 'code' => 'PKMRANUGEDANG'],
            83 => ['name' => 'Puskesmas Sukapura', 'code' => 'PKMSUKAPURA'],
            84 => ['name' => 'Puskesmas Suko', 'code' => 'PKMSUKO'],
            85 => ['name' => 'Puskesmas Sumber', 'code' => 'PKMSUMBER'],
            86 => ['name' => 'Puskesmas Sumberasih', 'code' => 'PKMSUMBERASIH'],
            87 => ['name' => 'Puskesmas Tegalsiwalan', 'code' => 'PKMTEGALSIWALAN'],
            88 => ['name' => 'Puskesmas Tiris', 'code' => 'PKMTIRIS'],
            89 => ['name' => 'Puskesmas Tongas', 'code' => 'PKMTONGAS'],
            90 => ['name' => 'Puskesmas Wangkal', 'code' => 'PKMWANGKAL'],
            91 => ['name' => 'Puskesmas Wonomerto', 'code' => 'PKMWONOMERTO'],
            92 => ['name' => 'RSUD Tongas', 'code' => 'RSTONGAS'],
            93 => ['name' => 'RSUD Waluyojati', 'code' => 'RSWALUYO'],
            94 => ['name' => 'Satuan Polisi Pamong Praja', 'code' => 'POLPP'],
            95 => ['name' => 'Sekretariat DPRD', 'code' => 'SEKWAN'],
        ];

        // ✅ Buat user OPD (tidak duplikat)
        foreach ($opdData as $no => $opd) {

            // Simpan OPD ke tabel opds
            Opd::firstOrCreate([
                'name' => $opd['name'],
                'code' => $opd['code'],
            ]);

            // Membuat user OPD
            $email = strtolower($opd['code']) . '@gmail.com';
            $password = Hash::make($opd['code'] . sprintf('%02d', $no));

            $user = User::firstOrCreate(
                ['email' => $email],
                [
                    'name' => $opd['name'],
                    'opd_code' => $opd['code'],  // SIMPAN CODE DI USER
                    'password' => $password,
                    'email_verified_at' => now(),
                    
                ]
            );

            $user->update([
                'opd_code' => $opd['code'],
            ]);

            if (!$user->hasRole('opd')) {
                $user->assignRole('opd');
            }
        }

        // ✅ Buat Verifikator
        $verifikator = User::firstOrCreate(
            ['email' => 'pemdaprobolinggo01@gmail.com'],
            [
                'name' => 'Verifikator',
                'password' => Hash::make('verifikator123'),
                'email_verified_at' => now(),
            ]
        );

        if (!$verifikator->hasRole('verifikator')) {
            $verifikator->assignRole('verifikator');
        }

        // ✅ Buat Monitoring
        $monitoring = User::firstOrCreate(
            ['email' => 'pemdaprobolinggo02@gmail.com'],
            [
                'name' => 'Monitoring',
                'password' => Hash::make('monitoring123'),
                'email_verified_at' => now(),
            ]
        );

        if (!$monitoring->hasRole('monitoring')) {
            $monitoring->assignRole('monitoring');
        }

        $this->command->info('✅ UserSeeder: semua user & role berhasil dibuat tanpa duplikat.');
    }
}
