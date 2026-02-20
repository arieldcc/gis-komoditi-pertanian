<?php

namespace Database\Seeders;

use App\Models\User;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;

class DummyOperationalSeeder extends Seeder
{
    public function run(): void
    {
        DB::transaction(function (): void {
            $now = now();
            $currentYear = (int) $now->format('Y');

            $roleMap = DB::table('roles')->pluck('id', 'kode_role');

            $extraUsers = [
                [
                    'name' => 'Penyuluh Tinangkung 2',
                    'email' => 'penyuluh2@sigkomoditas.id',
                    'role' => User::ROLE_PENYULUH,
                    'password' => 'password123',
                ],
                [
                    'name' => 'Penyuluh Tinangkung Utara',
                    'email' => 'penyuluh3@sigkomoditas.id',
                    'role' => User::ROLE_PENYULUH,
                    'password' => 'password123',
                ],
            ];

            foreach ($extraUsers as $user) {
                User::updateOrCreate(
                    ['email' => $user['email']],
                    [
                        'name' => $user['name'],
                        'role' => $user['role'],
                        'role_id' => $roleMap[$user['role']] ?? null,
                        'is_active' => true,
                        'password' => Hash::make($user['password']),
                        'email_verified_at' => $now,
                    ]
                );
            }

            $adminDinasId = (int) User::where('email', 'admin@sigkomoditas.id')->value('id');
            $adminKecamatanId = (int) User::where('email', 'admin.kecamatan@sigkomoditas.id')->value('id');

            $penyuluhUserIds = User::query()
                ->whereIn('email', [
                    'penyuluh@sigkomoditas.id',
                    'penyuluh2@sigkomoditas.id',
                    'penyuluh3@sigkomoditas.id',
                ])
                ->pluck('id', 'email');

            $kecTinangkungId = (int) DB::table('kecamatan')->where('nama_kecamatan', 'Tinangkung')->value('id');
            $kecTinangkungUtaraId = (int) DB::table('kecamatan')->where('nama_kecamatan', 'Tinangkung Utara')->value('id');

            if ($kecTinangkungId > 0) {
                DB::table('kecamatan')->where('id', $kecTinangkungId)->update([
                    'alamat' => 'Kecamatan Tinangkung, Kabupaten Banggai Kepulauan',
                    'updated_at' => $now,
                ]);
            }

            if ($kecTinangkungUtaraId > 0) {
                DB::table('kecamatan')->where('id', $kecTinangkungUtaraId)->update([
                    'alamat' => 'Kecamatan Tinangkung Utara, Kabupaten Banggai Kepulauan',
                    'updated_at' => $now,
                ]);
            }

            if ($adminKecamatanId > 0 && $kecTinangkungId > 0) {
                DB::table('user_wilayah')->updateOrInsert(
                    ['user_id' => $adminKecamatanId, 'kecamatan_id' => $kecTinangkungId],
                    ['is_primary' => true, 'updated_at' => $now, 'created_at' => $now]
                );
            }

            if ($adminKecamatanId > 0 && $kecTinangkungUtaraId > 0) {
                DB::table('user_wilayah')->updateOrInsert(
                    ['user_id' => $adminKecamatanId, 'kecamatan_id' => $kecTinangkungUtaraId],
                    ['is_primary' => false, 'updated_at' => $now, 'created_at' => $now]
                );
            }

            $desaTinangkungId = $this->upsertDesa(
                kecamatanId: $kecTinangkungId,
                kode: 'DSA-'.$kecTinangkungId.'-9001',
                nama: 'Desa Produktif Tinangkung',
                lat: -1.1421000,
                lng: 123.1332000
            );

            $desaTinangkungUtaraId = $this->upsertDesa(
                kecamatanId: $kecTinangkungUtaraId,
                kode: 'DSA-'.$kecTinangkungUtaraId.'-9001',
                nama: 'Desa Binaan Tinangkung Utara',
                lat: -1.0608000,
                lng: 123.0873000
            );

            $balaiTinangkungId = (int) DB::table('balai_penyuluh')
                ->where('kecamatan_id', $kecTinangkungId)
                ->value('id');
            $balaiTinangkungUtaraId = (int) DB::table('balai_penyuluh')
                ->where('kecamatan_id', $kecTinangkungUtaraId)
                ->value('id');

            if ($balaiTinangkungId > 0) {
                DB::table('balai_penyuluh')->where('id', $balaiTinangkungId)->update([
                    'foto_balai_url' => 'https://picsum.photos/seed/balai-tinangkung/800/450',
                    'updated_at' => $now,
                ]);
            }

            if ($balaiTinangkungUtaraId > 0) {
                DB::table('balai_penyuluh')->where('id', $balaiTinangkungUtaraId)->update([
                    'foto_balai_url' => 'https://picsum.photos/seed/balai-tinangkung-utara/800/450',
                    'updated_at' => $now,
                ]);
            }

            $penyuluhProfiles = [
                [
                    'email' => 'penyuluh@sigkomoditas.id',
                    'balai_id' => $balaiTinangkungId,
                    'nip' => 'PENY-7201001',
                    'jabatan' => 'Penyuluh Utama',
                    'lokasi_penugasan' => 'Tinangkung',
                ],
                [
                    'email' => 'penyuluh2@sigkomoditas.id',
                    'balai_id' => $balaiTinangkungId,
                    'nip' => 'PENY-7201002',
                    'jabatan' => 'Penyuluh Pendamping',
                    'lokasi_penugasan' => 'Tinangkung',
                ],
                [
                    'email' => 'penyuluh3@sigkomoditas.id',
                    'balai_id' => $balaiTinangkungUtaraId,
                    'nip' => 'PENY-7201003',
                    'jabatan' => 'Penyuluh Wilayah',
                    'lokasi_penugasan' => 'Tinangkung Utara',
                ],
            ];

            foreach ($penyuluhProfiles as $profile) {
                $userId = (int) ($penyuluhUserIds[$profile['email']] ?? 0);
                if ($userId <= 0 || (int) $profile['balai_id'] <= 0) {
                    continue;
                }

                DB::table('penyuluh')->updateOrInsert(
                    ['user_id' => $userId],
                    [
                        'balai_id' => $profile['balai_id'],
                        'nip' => $profile['nip'],
                        'jabatan' => $profile['jabatan'],
                        'lokasi_penugasan' => $profile['lokasi_penugasan'],
                        'is_active' => true,
                        'updated_at' => $now,
                        'created_at' => $now,
                    ]
                );
            }

            $penyuluhByEmail = DB::table('penyuluh')
                ->join('users', 'users.id', '=', 'penyuluh.user_id')
                ->whereIn('users.email', ['penyuluh@sigkomoditas.id', 'penyuluh2@sigkomoditas.id', 'penyuluh3@sigkomoditas.id'])
                ->pluck('penyuluh.id', 'users.email');

            $petaniRecords = [
                [
                    'desa_id' => $desaTinangkungId,
                    'nik' => '7207010101900001',
                    'nama_petani' => 'Rahmat Luwuk',
                    'no_hp' => '081200000101',
                    'alamat_domisili' => 'Desa Produktif Tinangkung',
                    'kelompok_tani' => 'Kelompok Makmur 1',
                ],
                [
                    'desa_id' => $desaTinangkungId,
                    'nik' => '7207010101900002',
                    'nama_petani' => 'Aisyah Peling',
                    'no_hp' => '081200000102',
                    'alamat_domisili' => 'Desa Produktif Tinangkung',
                    'kelompok_tani' => 'Kelompok Makmur 2',
                ],
                [
                    'desa_id' => $desaTinangkungUtaraId,
                    'nik' => '7207010101900003',
                    'nama_petani' => 'Yusuf Bulagi',
                    'no_hp' => '081200000103',
                    'alamat_domisili' => 'Desa Binaan Tinangkung Utara',
                    'kelompok_tani' => 'Kelompok Maju Tani',
                ],
            ];

            foreach ($petaniRecords as $petani) {
                if ((int) $petani['desa_id'] <= 0) {
                    continue;
                }

                DB::table('petani')->updateOrInsert(
                    ['nik' => $petani['nik']],
                    [
                        'desa_id' => $petani['desa_id'],
                        'nama_petani' => $petani['nama_petani'],
                        'no_hp' => $petani['no_hp'],
                        'alamat_domisili' => $petani['alamat_domisili'],
                        'kelompok_tani' => $petani['kelompok_tani'],
                        'is_active' => true,
                        'updated_at' => $now,
                        'created_at' => $now,
                    ]
                );
            }

            $petaniIds = DB::table('petani')
                ->whereIn('nik', ['7207010101900001', '7207010101900002', '7207010101900003'])
                ->pluck('id', 'nik');

            $lahanRecords = [
                [
                    'petani_nik' => '7207010101900001',
                    'desa_id' => $desaTinangkungId,
                    'alamat_lahan' => 'Blok A Tinangkung',
                    'luas_ha' => 1.70,
                    'kondisi_lahan' => 'baik',
                    'latitude' => -1.1412000,
                    'longitude' => 123.1349000,
                    'foto_lahan_url' => 'https://picsum.photos/seed/lahan-a/800/450',
                ],
                [
                    'petani_nik' => '7207010101900002',
                    'desa_id' => $desaTinangkungId,
                    'alamat_lahan' => 'Blok B Tinangkung',
                    'luas_ha' => 1.10,
                    'kondisi_lahan' => 'sedang',
                    'latitude' => -1.1438000,
                    'longitude' => 123.1315000,
                    'foto_lahan_url' => 'https://picsum.photos/seed/lahan-b/800/450',
                ],
                [
                    'petani_nik' => '7207010101900003',
                    'desa_id' => $desaTinangkungUtaraId,
                    'alamat_lahan' => 'Blok C Tinangkung Utara',
                    'luas_ha' => 2.30,
                    'kondisi_lahan' => 'baik',
                    'latitude' => -1.0616000,
                    'longitude' => 123.0888000,
                    'foto_lahan_url' => 'https://picsum.photos/seed/lahan-c/800/450',
                ],
            ];

            foreach ($lahanRecords as $lahan) {
                $petaniId = (int) ($petaniIds[$lahan['petani_nik']] ?? 0);
                if ($petaniId <= 0 || (int) $lahan['desa_id'] <= 0) {
                    continue;
                }

                DB::table('lahan')->updateOrInsert(
                    ['petani_id' => $petaniId, 'alamat_lahan' => $lahan['alamat_lahan']],
                    [
                        'desa_id' => $lahan['desa_id'],
                        'luas_ha' => $lahan['luas_ha'],
                        'kondisi_lahan' => $lahan['kondisi_lahan'],
                        'latitude' => $lahan['latitude'],
                        'longitude' => $lahan['longitude'],
                        'foto_lahan_url' => $lahan['foto_lahan_url'],
                        'is_active' => true,
                        'updated_at' => $now,
                        'created_at' => $now,
                    ]
                );
            }

            $lahanByAddress = DB::table('lahan')
                ->whereIn('alamat_lahan', ['Blok A Tinangkung', 'Blok B Tinangkung', 'Blok C Tinangkung Utara'])
                ->pluck('id', 'alamat_lahan');

            $komoditasByCode = DB::table('komoditas')
                ->whereIn('kode_komoditas', ['KMD001', 'KMD002', 'KMD003', 'KMD004'])
                ->pluck('id', 'kode_komoditas');

            $lahanKomoditasRecords = [
                ['alamat_lahan' => 'Blok A Tinangkung', 'kode_komoditas' => 'KMD001', 'tahun_tanam' => $currentYear, 'luas_tanam_ha' => 1.20, 'status_tanam' => 'tanam'],
                ['alamat_lahan' => 'Blok A Tinangkung', 'kode_komoditas' => 'KMD002', 'tahun_tanam' => $currentYear, 'luas_tanam_ha' => 0.50, 'status_tanam' => 'tanam'],
                ['alamat_lahan' => 'Blok B Tinangkung', 'kode_komoditas' => 'KMD003', 'tahun_tanam' => $currentYear, 'luas_tanam_ha' => 0.90, 'status_tanam' => 'panen'],
                ['alamat_lahan' => 'Blok C Tinangkung Utara', 'kode_komoditas' => 'KMD004', 'tahun_tanam' => $currentYear, 'luas_tanam_ha' => 1.80, 'status_tanam' => 'tanam'],
            ];

            foreach ($lahanKomoditasRecords as $row) {
                $lahanId = (int) ($lahanByAddress[$row['alamat_lahan']] ?? 0);
                $komoditasId = (int) ($komoditasByCode[$row['kode_komoditas']] ?? 0);
                if ($lahanId <= 0 || $komoditasId <= 0) {
                    continue;
                }

                DB::table('lahan_komoditas')->updateOrInsert(
                    [
                        'lahan_id' => $lahanId,
                        'komoditas_id' => $komoditasId,
                        'tahun_tanam' => $row['tahun_tanam'],
                    ],
                    [
                        'luas_tanam_ha' => $row['luas_tanam_ha'],
                        'status_tanam' => $row['status_tanam'],
                        'catatan' => 'Dummy untuk uji monitoring dan produksi.',
                        'updated_at' => $now,
                        'created_at' => $now,
                    ]
                );
            }

            $penugasanRecords = [
                ['email' => 'penyuluh@sigkomoditas.id', 'alamat_lahan' => 'Blok A Tinangkung', 'tanggal_mulai' => $now->copy()->subDays(30)->toDateString()],
                ['email' => 'penyuluh2@sigkomoditas.id', 'alamat_lahan' => 'Blok B Tinangkung', 'tanggal_mulai' => $now->copy()->subDays(25)->toDateString()],
                ['email' => 'penyuluh3@sigkomoditas.id', 'alamat_lahan' => 'Blok C Tinangkung Utara', 'tanggal_mulai' => $now->copy()->subDays(20)->toDateString()],
            ];

            foreach ($penugasanRecords as $row) {
                $penyuluhId = (int) ($penyuluhByEmail[$row['email']] ?? 0);
                $lahanId = (int) ($lahanByAddress[$row['alamat_lahan']] ?? 0);
                if ($penyuluhId <= 0 || $lahanId <= 0 || $adminKecamatanId <= 0) {
                    continue;
                }

                DB::table('penugasan_penyuluh')->updateOrInsert(
                    [
                        'penyuluh_id' => $penyuluhId,
                        'lahan_id' => $lahanId,
                        'tanggal_mulai' => $row['tanggal_mulai'],
                    ],
                    [
                        'dibuat_oleh_user_id' => $adminKecamatanId,
                        'tanggal_selesai' => null,
                        'status_penugasan' => 'aktif',
                        'updated_at' => $now,
                        'created_at' => $now,
                    ]
                );
            }

            $penugasanByLahan = DB::table('penugasan_penyuluh')
                ->join('lahan', 'lahan.id', '=', 'penugasan_penyuluh.lahan_id')
                ->whereIn('lahan.alamat_lahan', ['Blok A Tinangkung', 'Blok B Tinangkung', 'Blok C Tinangkung Utara'])
                ->pluck('penugasan_penyuluh.id', 'lahan.alamat_lahan');

            $kunjunganRecords = [
                [
                    'alamat_lahan' => 'Blok A Tinangkung',
                    'tanggal_kunjungan' => $now->copy()->subDays(10)->toDateTimeString(),
                    'kondisi_tanaman' => 'Pertumbuhan baik, daun hijau merata.',
                    'catatan_umum' => 'Drainase lahan cukup baik.',
                    'rekomendasi' => 'Lanjut pemupukan tahap kedua.',
                    'latitude' => -1.1412000,
                    'longitude' => 123.1349000,
                    'status_verifikasi' => 'disetujui',
                ],
                [
                    'alamat_lahan' => 'Blok B Tinangkung',
                    'tanggal_kunjungan' => $now->copy()->subDays(8)->toDateTimeString(),
                    'kondisi_tanaman' => 'Ada indikasi serangan ulat daun.',
                    'catatan_umum' => 'Sebagian area butuh penyemprotan.',
                    'rekomendasi' => 'Lakukan pengendalian hama terpadu.',
                    'latitude' => -1.1438000,
                    'longitude' => 123.1315000,
                    'status_verifikasi' => 'menunggu',
                ],
                [
                    'alamat_lahan' => 'Blok C Tinangkung Utara',
                    'tanggal_kunjungan' => $now->copy()->subDays(6)->toDateTimeString(),
                    'kondisi_tanaman' => 'Tanaman stabil namun butuh air tambahan.',
                    'catatan_umum' => 'Curah hujan menurun.',
                    'rekomendasi' => 'Optimalkan irigasi sederhana.',
                    'latitude' => -1.0616000,
                    'longitude' => 123.0888000,
                    'status_verifikasi' => 'revisi',
                ],
            ];

            foreach ($kunjunganRecords as $row) {
                $penugasanId = (int) ($penugasanByLahan[$row['alamat_lahan']] ?? 0);
                if ($penugasanId <= 0) {
                    continue;
                }

                DB::table('kunjungan_monitoring')->updateOrInsert(
                    [
                        'penugasan_id' => $penugasanId,
                        'tanggal_kunjungan' => $row['tanggal_kunjungan'],
                    ],
                    [
                        'kondisi_tanaman' => $row['kondisi_tanaman'],
                        'catatan_umum' => $row['catatan_umum'],
                        'rekomendasi' => $row['rekomendasi'],
                        'latitude' => $row['latitude'],
                        'longitude' => $row['longitude'],
                        'status_verifikasi' => $row['status_verifikasi'],
                        'updated_at' => $now,
                        'created_at' => $now,
                    ]
                );
            }

            $kunjunganByLahan = DB::table('kunjungan_monitoring')
                ->join('penugasan_penyuluh', 'penugasan_penyuluh.id', '=', 'kunjungan_monitoring.penugasan_id')
                ->join('lahan', 'lahan.id', '=', 'penugasan_penyuluh.lahan_id')
                ->whereIn('lahan.alamat_lahan', ['Blok A Tinangkung', 'Blok B Tinangkung', 'Blok C Tinangkung Utara'])
                ->pluck('kunjungan_monitoring.id', 'lahan.alamat_lahan');

            $kategoriKendalaId = (int) DB::table('kategori_kendala')->where('nama_kategori', 'Hama')->value('id');
            $kategoriKebutuhanId = (int) DB::table('kategori_kebutuhan')->where('nama_kategori', 'Pupuk')->value('id');

            $kunjunganBlokB = (int) ($kunjunganByLahan['Blok B Tinangkung'] ?? 0);
            if ($kunjunganBlokB > 0 && $kategoriKendalaId > 0) {
                DB::table('kendala_kunjungan')->updateOrInsert(
                    [
                        'kunjungan_id' => $kunjunganBlokB,
                        'kategori_kendala_id' => $kategoriKendalaId,
                    ],
                    [
                        'deskripsi_kendala' => 'Serangan ulat daun pada 30% area tanam.',
                        'tingkat_keparahan' => 'sedang',
                        'perlu_tindak_lanjut' => true,
                        'updated_at' => $now,
                        'created_at' => $now,
                    ]
                );
            }

            if ($kunjunganBlokB > 0 && $kategoriKebutuhanId > 0) {
                DB::table('kebutuhan_kunjungan')->updateOrInsert(
                    [
                        'kunjungan_id' => $kunjunganBlokB,
                        'kategori_kebutuhan_id' => $kategoriKebutuhanId,
                    ],
                    [
                        'deskripsi_kebutuhan' => 'Kebutuhan pupuk NPK untuk fase pemulihan.',
                        'jumlah' => 120,
                        'satuan' => 'kg',
                        'prioritas' => 'tinggi',
                        'updated_at' => $now,
                        'created_at' => $now,
                    ]
                );
            }

            $periodCurrentId = $this->upsertPeriode(
                bulan: (int) $now->format('m'),
                tahun: (int) $now->format('Y'),
                mulai: $now->copy()->startOfMonth()->toDateString(),
                selesai: $now->copy()->endOfMonth()->toDateString()
            );

            $prevDate = $now->copy()->subMonth();
            $periodPrevId = $this->upsertPeriode(
                bulan: (int) $prevDate->format('m'),
                tahun: (int) $prevDate->format('Y'),
                mulai: $prevDate->copy()->startOfMonth()->toDateString(),
                selesai: $prevDate->copy()->endOfMonth()->toDateString()
            );

            $lahanKomoditasByLahan = DB::table('lahan_komoditas')
                ->join('lahan', 'lahan.id', '=', 'lahan_komoditas.lahan_id')
                ->whereIn('lahan.alamat_lahan', ['Blok A Tinangkung', 'Blok B Tinangkung', 'Blok C Tinangkung Utara'])
                ->select('lahan_komoditas.id', 'lahan.alamat_lahan')
                ->get()
                ->groupBy('alamat_lahan')
                ->map(fn ($items) => (int) $items->first()->id);

            $produksiRecords = [
                [
                    'alamat_lahan' => 'Blok A Tinangkung',
                    'periode_id' => $periodCurrentId,
                    'kunjungan_id' => (int) ($kunjunganByLahan['Blok A Tinangkung'] ?? 0),
                    'tanggal_panen' => $now->copy()->subDays(4)->toDateString(),
                    'jumlah_produksi' => 1450.00,
                    'produktivitas_kg_ha' => 852.94,
                    'harga_jual' => 72000.00,
                ],
                [
                    'alamat_lahan' => 'Blok B Tinangkung',
                    'periode_id' => $periodCurrentId,
                    'kunjungan_id' => (int) ($kunjunganByLahan['Blok B Tinangkung'] ?? 0),
                    'tanggal_panen' => $now->copy()->subDays(3)->toDateString(),
                    'jumlah_produksi' => 980.00,
                    'produktivitas_kg_ha' => 890.91,
                    'harga_jual' => 6400.00,
                ],
                [
                    'alamat_lahan' => 'Blok C Tinangkung Utara',
                    'periode_id' => $periodPrevId,
                    'kunjungan_id' => (int) ($kunjunganByLahan['Blok C Tinangkung Utara'] ?? 0),
                    'tanggal_panen' => $prevDate->copy()->subDays(6)->toDateString(),
                    'jumlah_produksi' => 1675.00,
                    'produktivitas_kg_ha' => 728.26,
                    'harga_jual' => 38500.00,
                ],
            ];

            foreach ($produksiRecords as $row) {
                $lahanKomoditasId = (int) ($lahanKomoditasByLahan[$row['alamat_lahan']] ?? 0);
                if ($lahanKomoditasId <= 0 || (int) $row['periode_id'] <= 0) {
                    continue;
                }

                DB::table('produksi_panen')->updateOrInsert(
                    [
                        'lahan_komoditas_id' => $lahanKomoditasId,
                        'periode_id' => $row['periode_id'],
                        'tanggal_panen' => $row['tanggal_panen'],
                    ],
                    [
                        'kunjungan_id' => $row['kunjungan_id'] > 0 ? $row['kunjungan_id'] : null,
                        'jumlah_produksi' => $row['jumlah_produksi'],
                        'produktivitas_kg_ha' => $row['produktivitas_kg_ha'],
                        'harga_jual' => $row['harga_jual'],
                        'catatan' => 'Dummy hasil panen untuk uji analitik.',
                        'updated_at' => $now,
                        'created_at' => $now,
                    ]
                );
            }

            if ($adminKecamatanId > 0) {
                foreach (['Blok A Tinangkung' => 'disetujui', 'Blok C Tinangkung Utara' => 'revisi'] as $alamat => $status) {
                    $kunjunganId = (int) ($kunjunganByLahan[$alamat] ?? 0);
                    if ($kunjunganId <= 0) {
                        continue;
                    }

                    DB::table('verifikasi_log')->updateOrInsert(
                        [
                            'kunjungan_id' => $kunjunganId,
                            'status_verifikasi' => $status,
                        ],
                        [
                            'diverifikasi_oleh_user_id' => $adminKecamatanId,
                            'catatan_verifikasi' => $status === 'disetujui'
                                ? 'Laporan lengkap dan tervalidasi.'
                                : 'Lengkapi foto lahan terbaru.',
                            'diverifikasi_at' => $now,
                            'updated_at' => $now,
                            'created_at' => $now,
                        ]
                    );
                }
            }

            if ($adminDinasId > 0 && $periodCurrentId > 0) {
                DB::table('laporan_pimpinan')->updateOrInsert(
                    [
                        'periode_id' => $periodCurrentId,
                        'jenis_laporan' => 'Rekap Bulanan Dummy',
                    ],
                    [
                        'generated_by_user_id' => $adminDinasId,
                        'file_url' => 'storage/laporan/rekap-bulanan-dummy.pdf',
                        'generated_at' => $now,
                        'updated_at' => $now,
                        'created_at' => $now,
                    ]
                );
            }

            $laporanCurrentId = (int) DB::table('laporan_pimpinan')
                ->where('periode_id', $periodCurrentId)
                ->where('jenis_laporan', 'Rekap Bulanan Dummy')
                ->value('id');

            if ($laporanCurrentId > 0) {
                $summary = DB::table('produksi_panen')
                    ->join('lahan_komoditas', 'lahan_komoditas.id', '=', 'produksi_panen.lahan_komoditas_id')
                    ->join('lahan', 'lahan.id', '=', 'lahan_komoditas.lahan_id')
                    ->join('desa', 'desa.id', '=', 'lahan.desa_id')
                    ->leftJoin('petani', 'petani.id', '=', 'lahan.petani_id')
                    ->where('produksi_panen.periode_id', $periodCurrentId)
                    ->groupBy('desa.kecamatan_id')
                    ->selectRaw('desa.kecamatan_id')
                    ->selectRaw('COALESCE(SUM(produksi_panen.jumlah_produksi),0) as total_produksi')
                    ->selectRaw('COALESCE(SUM(lahan.luas_ha),0) as total_luas')
                    ->selectRaw('COUNT(DISTINCT petani.id) as total_petani')
                    ->selectRaw('COUNT(DISTINCT lahan.id) as total_lahan')
                    ->get();

                foreach ($summary as $item) {
                    DB::table('laporan_pimpinan_kecamatan')->updateOrInsert(
                        [
                            'laporan_id' => $laporanCurrentId,
                            'kecamatan_id' => $item->kecamatan_id,
                        ],
                        [
                            'total_produksi' => $item->total_produksi,
                            'total_luas' => $item->total_luas,
                            'total_petani' => $item->total_petani,
                            'total_lahan' => $item->total_lahan,
                            'updated_at' => $now,
                            'created_at' => $now,
                        ]
                    );
                }
            }

            if ($adminKecamatanId > 0) {
                DB::table('notifikasi')->updateOrInsert(
                    [
                        'user_id' => $adminKecamatanId,
                        'judul' => 'Laporan Monitoring Baru',
                    ],
                    [
                        'pesan' => 'Ada laporan kunjungan baru yang menunggu verifikasi.',
                        'ref_tipe' => 'kunjungan_monitoring',
                        'ref_id' => (int) ($kunjunganByLahan['Blok B Tinangkung'] ?? 0),
                        'is_read' => false,
                        'read_at' => null,
                        'updated_at' => $now,
                        'created_at' => $now,
                    ]
                );
            }

            $kunjunganBlokA = (int) ($kunjunganByLahan['Blok A Tinangkung'] ?? 0);
            if ($kunjunganBlokA > 0 && $adminDinasId > 0) {
                DB::table('lampiran_media')->updateOrInsert(
                    [
                        'kunjungan_id' => $kunjunganBlokA,
                        'file_url' => 'https://picsum.photos/seed/kunjungan-a/1200/700',
                    ],
                    [
                        'lahan_id' => (int) ($lahanByAddress['Blok A Tinangkung'] ?? 0),
                        'balai_id' => null,
                        'uploaded_by_user_id' => $adminDinasId,
                        'file_type' => 'image',
                        'taken_at' => $now->copy()->subDays(10),
                        'uploaded_at' => $now,
                        'updated_at' => $now,
                        'created_at' => $now,
                    ]
                );
            }
        });
    }

    private function upsertPeriode(int $bulan, int $tahun, string $mulai, string $selesai): int
    {
        DB::table('periode_laporan')->updateOrInsert(
            ['bulan' => $bulan, 'tahun' => $tahun],
            [
                'tanggal_mulai' => $mulai,
                'tanggal_selesai' => $selesai,
                'status_periode' => 'terbuka',
                'updated_at' => now(),
                'created_at' => now(),
            ]
        );

        return (int) DB::table('periode_laporan')
            ->where('bulan', $bulan)
            ->where('tahun', $tahun)
            ->value('id');
    }

    private function upsertDesa(int $kecamatanId, string $kode, string $nama, float $lat, float $lng): int
    {
        if ($kecamatanId <= 0) {
            return 0;
        }

        DB::table('desa')->updateOrInsert(
            ['kecamatan_id' => $kecamatanId, 'nama_desa' => $nama],
            [
                'kode_desa' => $kode,
                'alamat' => 'Desa '.$nama.', Kabupaten Banggai Kepulauan',
                'centroid_lat' => $lat,
                'centroid_lng' => $lng,
                'is_active' => true,
                'updated_at' => now(),
                'created_at' => now(),
            ]
        );

        return (int) DB::table('desa')
            ->where('kecamatan_id', $kecamatanId)
            ->where('nama_desa', $nama)
            ->value('id');
    }
}
