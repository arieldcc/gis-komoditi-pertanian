<?php

namespace Database\Seeders;

use App\Models\User;
use App\Support\MapStyleSupport;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;

class DefaultUsersSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        $roles = [
            ['kode_role' => User::ROLE_ADMIN_DINAS, 'nama_role' => 'Admin Dinas', 'level_akses' => 1],
            ['kode_role' => User::ROLE_ADMIN_KECAMATAN, 'nama_role' => 'Admin Kecamatan', 'level_akses' => 2],
            ['kode_role' => User::ROLE_PENYULUH, 'nama_role' => 'Penyuluh', 'level_akses' => 3],
            ['kode_role' => User::ROLE_PIMPINAN_DINAS, 'nama_role' => 'Pimpinan Dinas', 'level_akses' => 4],
        ];

        foreach ($roles as $role) {
            DB::table('roles')->updateOrInsert(
                ['kode_role' => $role['kode_role']],
                [
                    'nama_role' => $role['nama_role'],
                    'level_akses' => $role['level_akses'],
                    'updated_at' => now(),
                    'created_at' => now(),
                ]
            );
        }

        $roleMap = DB::table('roles')->pluck('id', 'kode_role');

        $users = [
            [
                'name' => 'Super Admin Dinas',
                'email' => 'admin@sigkomoditas.id',
                'role' => User::ROLE_ADMIN_DINAS,
                'password' => 'Admin12345!',
            ],
            [
                'name' => 'Admin Kecamatan',
                'email' => 'admin.kecamatan@sigkomoditas.id',
                'role' => User::ROLE_ADMIN_KECAMATAN,
                'password' => 'password123',
            ],
            [
                'name' => 'Penyuluh Lapangan',
                'email' => 'penyuluh@sigkomoditas.id',
                'role' => User::ROLE_PENYULUH,
                'password' => 'password123',
            ],
            [
                'name' => 'Pimpinan Dinas',
                'email' => 'pimpinan@sigkomoditas.id',
                'role' => User::ROLE_PIMPINAN_DINAS,
                'password' => 'password123',
            ],
        ];

        foreach ($users as $item) {
            User::updateOrCreate(
                ['email' => $item['email']],
                [
                    'name' => $item['name'],
                    'role' => $item['role'],
                    'role_id' => $roleMap[$item['role']] ?? null,
                    'is_active' => true,
                    'password' => Hash::make($item['password']),
                    'email_verified_at' => now(),
                ]
            );
        }

        $kecamatanList = [
            ['kode_kecamatan' => 'KEC01', 'nama_kecamatan' => 'Tinangkung', 'centroid_lat' => -1.1452000, 'centroid_lng' => 123.1289000],
            ['kode_kecamatan' => 'KEC02', 'nama_kecamatan' => 'Tinangkung Utara', 'centroid_lat' => -1.0643000, 'centroid_lng' => 123.0804000],
            ['kode_kecamatan' => 'KEC03', 'nama_kecamatan' => 'Tinangkung Selatan', 'centroid_lat' => -1.2275000, 'centroid_lng' => 123.1737000],
            ['kode_kecamatan' => 'KEC04', 'nama_kecamatan' => 'Peling Tengah', 'centroid_lat' => -1.1963000, 'centroid_lng' => 123.2810000],
            ['kode_kecamatan' => 'KEC05', 'nama_kecamatan' => 'Liang', 'centroid_lat' => -1.0124000, 'centroid_lng' => 123.2625000],
            ['kode_kecamatan' => 'KEC06', 'nama_kecamatan' => 'Totikum', 'centroid_lat' => -1.3413000, 'centroid_lng' => 123.3317000],
            ['kode_kecamatan' => 'KEC07', 'nama_kecamatan' => 'Totikum Selatan', 'centroid_lat' => -1.4234000, 'centroid_lng' => 123.3772000],
            ['kode_kecamatan' => 'KEC08', 'nama_kecamatan' => 'Bulagi', 'centroid_lat' => -1.3127000, 'centroid_lng' => 123.5194000],
            ['kode_kecamatan' => 'KEC09', 'nama_kecamatan' => 'Bulagi Utara', 'centroid_lat' => -1.2282000, 'centroid_lng' => 123.5583000],
            ['kode_kecamatan' => 'KEC10', 'nama_kecamatan' => 'Bulagi Selatan', 'centroid_lat' => -1.3986000, 'centroid_lng' => 123.5425000],
            ['kode_kecamatan' => 'KEC11', 'nama_kecamatan' => 'Buko', 'centroid_lat' => -1.1869000, 'centroid_lng' => 123.6731000],
            ['kode_kecamatan' => 'KEC12', 'nama_kecamatan' => 'Buko Selatan', 'centroid_lat' => -1.2738000, 'centroid_lng' => 123.7084000],
        ];

        foreach ($kecamatanList as $kecamatan) {
            DB::table('kecamatan')->updateOrInsert(
                ['kode_kecamatan' => $kecamatan['kode_kecamatan']],
                [
                    'nama_kecamatan' => $kecamatan['nama_kecamatan'],
                    'alamat' => 'Kecamatan '.$kecamatan['nama_kecamatan'].', Kabupaten Banggai Kepulauan',
                    'centroid_lat' => $kecamatan['centroid_lat'],
                    'centroid_lng' => $kecamatan['centroid_lng'],
                    'is_active' => true,
                    'updated_at' => now(),
                    'created_at' => now(),
                ]
            );
        }

        $kecamatanMap = DB::table('kecamatan')->pluck('id', 'nama_kecamatan');

        foreach ($kecamatanMap as $nama => $kecamatanId) {
            $kec = collect($kecamatanList)->firstWhere('nama_kecamatan', $nama);
            $baseLat = $kec['centroid_lat'] ?? null;
            $baseLng = $kec['centroid_lng'] ?? null;

            DB::table('desa')->updateOrInsert(
                [
                    'kecamatan_id' => $kecamatanId,
                    'nama_desa' => 'Desa Utama '.$nama,
                ],
                [
                    'kode_desa' => 'DS'.str_pad((string) $kecamatanId, 2, '0', STR_PAD_LEFT),
                    'alamat' => 'Desa Utama '.$nama.', Kecamatan '.$nama.', Kabupaten Banggai Kepulauan',
                    'centroid_lat' => $baseLat ? $baseLat + 0.0065 : null,
                    'centroid_lng' => $baseLng ? $baseLng + 0.0065 : null,
                    'is_active' => true,
                    'updated_at' => now(),
                    'created_at' => now(),
                ]
            );

            DB::table('balai_penyuluh')->updateOrInsert(
                [
                    'kecamatan_id' => $kecamatanId,
                    'nama_balai' => 'Balai Penyuluh '.$nama,
                ],
                [
                    'alamat_balai' => 'Alamat BPP '.$nama,
                    'latitude' => $baseLat ? $baseLat - 0.0030 : null,
                    'longitude' => $baseLng ? $baseLng - 0.0030 : null,
                    'is_active' => true,
                    'updated_at' => now(),
                    'created_at' => now(),
                ]
            );
        }

        $komoditas = [
            ['kode_komoditas' => 'KMD001', 'nama_komoditas' => 'Pala'],
            ['kode_komoditas' => 'KMD002', 'nama_komoditas' => 'Cengkeh'],
            ['kode_komoditas' => 'KMD003', 'nama_komoditas' => 'Kelapa'],
            ['kode_komoditas' => 'KMD004', 'nama_komoditas' => 'Jambu Mente'],
        ];

        foreach ($komoditas as $item) {
            DB::table('komoditas')->updateOrInsert(
                ['kode_komoditas' => $item['kode_komoditas']],
                [
                    'nama_komoditas' => $item['nama_komoditas'],
                    'satuan_default' => 'kg',
                    'is_active' => true,
                    'updated_at' => now(),
                    'created_at' => now(),
                ]
            );
        }

        MapStyleSupport::ensureDefaultRows();

        foreach (['Hama', 'Cuaca', 'Penyakit Tanaman', 'Akses Pasar'] as $kategori) {
            DB::table('kategori_kendala')->updateOrInsert(
                ['nama_kategori' => $kategori],
                [
                    'deskripsi' => 'Kategori kendala '.$kategori,
                    'updated_at' => now(),
                    'created_at' => now(),
                ]
            );
        }

        foreach (['Bibit', 'Pupuk', 'Alat Pertanian', 'Pelatihan'] as $kategori) {
            DB::table('kategori_kebutuhan')->updateOrInsert(
                ['nama_kategori' => $kategori],
                [
                    'deskripsi' => 'Kategori kebutuhan '.$kategori,
                    'updated_at' => now(),
                    'created_at' => now(),
                ]
            );
        }

        $month = (int) now()->format('m');
        $year = (int) now()->format('Y');
        DB::table('periode_laporan')->updateOrInsert(
            ['bulan' => $month, 'tahun' => $year],
            [
                'tanggal_mulai' => now()->startOfMonth()->toDateString(),
                'tanggal_selesai' => now()->endOfMonth()->toDateString(),
                'status_periode' => 'terbuka',
                'updated_at' => now(),
                'created_at' => now(),
            ]
        );

        $adminKecamatan = User::where('email', 'admin.kecamatan@sigkomoditas.id')->first();
        $penyuluhUser = User::where('email', 'penyuluh@sigkomoditas.id')->first();
        $adminDinas = User::where('email', 'admin@sigkomoditas.id')->first();
        $balaiTinangkungId = DB::table('balai_penyuluh')
            ->join('kecamatan', 'kecamatan.id', '=', 'balai_penyuluh.kecamatan_id')
            ->where('kecamatan.nama_kecamatan', 'Tinangkung')
            ->value('balai_penyuluh.id');

        if ($adminKecamatan) {
            $kecTinangkungId = (int) ($kecamatanMap['Tinangkung'] ?? 0);
            if ($kecTinangkungId > 0) {
                DB::table('user_wilayah')->updateOrInsert(
                    [
                        'user_id' => $adminKecamatan->id,
                        'kecamatan_id' => $kecTinangkungId,
                    ],
                    [
                        'is_primary' => true,
                        'updated_at' => now(),
                        'created_at' => now(),
                    ]
                );
            }
        }

        if ($penyuluhUser && $balaiTinangkungId) {
            DB::table('penyuluh')->updateOrInsert(
                ['user_id' => $penyuluhUser->id],
                [
                    'balai_id' => $balaiTinangkungId,
                    'jabatan' => 'Penyuluh Pertanian',
                    'lokasi_penugasan' => 'Wilayah Tinangkung',
                    'is_active' => true,
                    'updated_at' => now(),
                    'created_at' => now(),
                ]
            );
        }

        if ($adminDinas) {
            $periodeId = DB::table('periode_laporan')
                ->where('bulan', $month)
                ->where('tahun', $year)
                ->value('id');

            if ($periodeId) {
                DB::table('laporan_pimpinan')->updateOrInsert(
                    [
                        'periode_id' => $periodeId,
                        'jenis_laporan' => 'Rekap Bulanan',
                    ],
                    [
                        'generated_by_user_id' => $adminDinas->id,
                        'file_url' => 'storage/laporan/rekap-bulanan.pdf',
                        'generated_at' => now(),
                        'updated_at' => now(),
                        'created_at' => now(),
                    ]
                );
            }
        }

        $tinangkungDesaId = DB::table('desa')
            ->join('kecamatan', 'kecamatan.id', '=', 'desa.kecamatan_id')
            ->where('kecamatan.nama_kecamatan', 'Tinangkung')
            ->value('desa.id');

        if ($tinangkungDesaId) {
            DB::table('petani')->updateOrInsert(
                [
                    'desa_id' => $tinangkungDesaId,
                    'nama_petani' => 'Petani Contoh Tinangkung',
                ],
                [
                    'nik' => '7207001901000001',
                    'no_hp' => '081200000001',
                    'alamat_domisili' => 'Tinangkung, Banggai Kepulauan',
                    'kelompok_tani' => 'Kelompok Tani Sejahtera',
                    'is_active' => true,
                    'updated_at' => now(),
                    'created_at' => now(),
                ]
            );

            $petaniId = DB::table('petani')
                ->where('desa_id', $tinangkungDesaId)
                ->where('nama_petani', 'Petani Contoh Tinangkung')
                ->value('id');

            if ($petaniId) {
                DB::table('lahan')->updateOrInsert(
                    [
                        'petani_id' => $petaniId,
                        'desa_id' => $tinangkungDesaId,
                        'alamat_lahan' => 'Lahan contoh Tinangkung',
                    ],
                    [
                        'luas_ha' => 1.50,
                        'kondisi_lahan' => 'baik',
                        'latitude' => -1.1387000,
                        'longitude' => 123.1379000,
                        'is_active' => true,
                        'updated_at' => now(),
                        'created_at' => now(),
                    ]
                );

                $lahanId = DB::table('lahan')
                    ->where('petani_id', $petaniId)
                    ->where('desa_id', $tinangkungDesaId)
                    ->where('alamat_lahan', 'Lahan contoh Tinangkung')
                    ->value('id');

                $komoditasPalaId = DB::table('komoditas')->where('kode_komoditas', 'KMD001')->value('id');
                if ($lahanId && $komoditasPalaId) {
                    DB::table('lahan_komoditas')->updateOrInsert(
                        [
                            'lahan_id' => $lahanId,
                            'komoditas_id' => $komoditasPalaId,
                            'tahun_tanam' => (int) now()->format('Y'),
                        ],
                        [
                            'luas_tanam_ha' => 1.20,
                            'status_tanam' => 'tanam',
                            'catatan' => 'Data awal contoh komoditas.',
                            'updated_at' => now(),
                            'created_at' => now(),
                        ]
                    );
                }
            }
        }
    }
}
