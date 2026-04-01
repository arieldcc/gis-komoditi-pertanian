<?php

namespace Tests\Feature;

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use Tests\TestCase;

class DomainRegressionTest extends TestCase
{
    use RefreshDatabase;

    private int $sequence = 1;

    public function test_admin_update_penyuluh_syncs_user_status(): void
    {
        $admin = User::factory()->create([
            'role' => User::ROLE_ADMIN_DINAS,
            'is_active' => true,
        ]);
        $kecamatanId = $this->createKecamatan();
        $balaiId = $this->createBalai($kecamatanId);
        ['user' => $penyuluhUser, 'penyuluh_id' => $penyuluhId] = $this->createPenyuluhProfile($balaiId);

        $response = $this->actingAs($admin)
            ->from('/admin/balai')
            ->put(route('admin.balai.penyuluh.update', $penyuluhId), [
                'balai_id' => $balaiId,
                'nip' => '1987001',
                'jabatan' => 'Penyuluh Lapangan',
                'lokasi_penugasan' => 'Wilayah 1',
                'tugas_tambahan' => 'Pendamping',
                'is_active' => 0,
            ]);

        $response->assertRedirect('/admin/balai');
        $response->assertSessionHas('success');

        $this->assertDatabaseHas('penyuluh', [
            'id' => $penyuluhId,
            'is_active' => 0,
        ]);
        $this->assertDatabaseHas('users', [
            'id' => $penyuluhUser->id,
            'is_active' => 0,
        ]);
    }

    public function test_admin_delete_penyuluh_removes_linked_user_and_assignment(): void
    {
        $admin = User::factory()->create([
            'role' => User::ROLE_ADMIN_DINAS,
            'is_active' => true,
        ]);
        $kecamatanId = $this->createKecamatan();
        $balaiId = $this->createBalai($kecamatanId);
        $desaId = $this->createDesa($kecamatanId);
        $petaniId = $this->createPetani($desaId);
        $lahanId = $this->createLahan($petaniId, $desaId, 1.75);
        ['user' => $penyuluhUser, 'penyuluh_id' => $penyuluhId] = $this->createPenyuluhProfile($balaiId);
        $penugasanId = $this->createPenugasan($penyuluhId, $lahanId, $admin->id);

        $response = $this->actingAs($admin)
            ->from('/admin/balai')
            ->delete(route('admin.balai.penyuluh.destroy', $penyuluhId));

        $response->assertRedirect('/admin/balai');
        $response->assertSessionHas('success');

        $this->assertDatabaseMissing('penugasan_penyuluh', ['id' => $penugasanId]);
        $this->assertDatabaseMissing('penyuluh', ['id' => $penyuluhId]);
        $this->assertDatabaseMissing('users', ['id' => $penyuluhUser->id]);
    }

    public function test_admin_kecamatan_cannot_move_penyuluh_to_foreign_balai(): void
    {
        $kecamatanA = $this->createKecamatan('Kecamatan A');
        $kecamatanB = $this->createKecamatan('Kecamatan B');
        $balaiA = $this->createBalai($kecamatanA, 'Balai A');
        $balaiB = $this->createBalai($kecamatanB, 'Balai B');
        $adminKecamatan = User::factory()->create([
            'role' => User::ROLE_ADMIN_KECAMATAN,
            'is_active' => true,
        ]);
        $this->assignWilayah($adminKecamatan->id, $kecamatanA);
        ['penyuluh_id' => $penyuluhId] = $this->createPenyuluhProfile($balaiA);

        $response = $this->actingAs($adminKecamatan)
            ->from('/kecamatan/penyuluh')
            ->put(route('kecamatan.penyuluh.update', $penyuluhId), [
                'balai_id' => $balaiB,
                'nip' => '1987002',
                'jabatan' => 'PPL',
                'lokasi_penugasan' => 'Zona A',
                'tugas_tambahan' => 'Pendataan',
                'is_active' => 1,
            ]);

        $response->assertRedirect('/kecamatan/penyuluh');
        $response->assertSessionHas('error', 'Balai tujuan tidak termasuk wilayah admin kecamatan Anda.');
        $this->assertDatabaseHas('penyuluh', [
            'id' => $penyuluhId,
            'balai_id' => $balaiA,
        ]);
    }

    public function test_verifikasi_kecamatan_requires_explicit_response_and_delete_route_is_unavailable(): void
    {
        $kecamatanId = $this->createKecamatan();
        $balaiId = $this->createBalai($kecamatanId);
        $desaId = $this->createDesa($kecamatanId);
        $petaniId = $this->createPetani($desaId);
        $lahanId = $this->createLahan($petaniId, $desaId, 2.10);
        $adminKecamatan = User::factory()->create([
            'role' => User::ROLE_ADMIN_KECAMATAN,
            'is_active' => true,
        ]);
        $this->assignWilayah($adminKecamatan->id, $kecamatanId);
        ['penyuluh_id' => $penyuluhId] = $this->createPenyuluhProfile($balaiId);
        $penugasanId = $this->createPenugasan($penyuluhId, $lahanId, $adminKecamatan->id);
        $kunjunganId = $this->createKunjungan($penugasanId, 'menunggu');

        $deleteResponse = $this->actingAs($adminKecamatan)
            ->delete('/kecamatan/verifikasi/'.$kunjunganId);

        $deleteResponse->assertStatus(405);

        $postResponse = $this->actingAs($adminKecamatan)
            ->from('/kecamatan/verifikasi')
            ->post(route('kecamatan.verifikasi.update', $kunjunganId), []);

        $postResponse->assertRedirect('/kecamatan/verifikasi');
        $postResponse->assertSessionHasErrors('status_verifikasi');
        $this->assertDatabaseHas('kunjungan_monitoring', [
            'id' => $kunjunganId,
            'status_verifikasi' => 'menunggu',
        ]);
    }

    public function test_kecamatan_rekap_uses_unique_lahan_sum(): void
    {
        $kecamatanId = $this->createKecamatan();
        $desaId = $this->createDesa($kecamatanId);
        $adminKecamatan = User::factory()->create([
            'role' => User::ROLE_ADMIN_KECAMATAN,
            'is_active' => true,
        ]);
        $this->assignWilayah($adminKecamatan->id, $kecamatanId);

        $petaniA = $this->createPetani($desaId, 'Petani A');
        $petaniB = $this->createPetani($desaId, 'Petani B');
        $this->createLahan($petaniA, $desaId, 1.50);
        $this->createLahan($petaniB, $desaId, 2.50);

        $response = $this->actingAs($adminKecamatan)->get(route('kecamatan.rekap'));

        $response->assertOk();
        $summary = $response->viewData('summary');

        $this->assertNotNull($summary);
        $this->assertCount(1, $summary);
        $this->assertEquals(4.0, (float) $summary->first()->total_luas);
        $this->assertEquals(2, (int) $summary->first()->total_petani);
        $this->assertEquals(2, (int) $summary->first()->total_lahan);
    }

    public function test_generate_detail_laporan_uses_unique_lahan_sum_per_period(): void
    {
        $admin = User::factory()->create([
            'role' => User::ROLE_ADMIN_DINAS,
            'is_active' => true,
        ]);
        $kecamatanId = $this->createKecamatan();
        $desaId = $this->createDesa($kecamatanId);
        $petaniId = $this->createPetani($desaId);
        $lahanId = $this->createLahan($petaniId, $desaId, 2.50);
        $komoditasId = DB::table('komoditas')->insertGetId([
            'kode_komoditas' => 'KMD'.$this->nextSequence(),
            'nama_komoditas' => 'Komoditas '.$this->nextSequence(),
            'satuan_default' => 'kg',
            'is_active' => true,
            'created_at' => now(),
            'updated_at' => now(),
        ]);
        $lahanKomoditasId = DB::table('lahan_komoditas')->insertGetId([
            'lahan_id' => $lahanId,
            'komoditas_id' => $komoditasId,
            'tahun_tanam' => 2026,
            'luas_tanam_ha' => 2.50,
            'status_tanam' => 'panen',
            'created_at' => now(),
            'updated_at' => now(),
        ]);
        $periodeId = DB::table('periode_laporan')->insertGetId([
            'bulan' => 3,
            'tahun' => 2026,
            'tanggal_mulai' => '2026-03-01',
            'tanggal_selesai' => '2026-03-31',
            'status_periode' => 'terbuka',
            'created_at' => now(),
            'updated_at' => now(),
        ]);
        DB::table('produksi_panen')->insert([
            [
                'lahan_komoditas_id' => $lahanKomoditasId,
                'periode_id' => $periodeId,
                'tanggal_panen' => '2026-03-10',
                'jumlah_produksi' => 100,
                'created_at' => now(),
                'updated_at' => now(),
            ],
            [
                'lahan_komoditas_id' => $lahanKomoditasId,
                'periode_id' => $periodeId,
                'tanggal_panen' => '2026-03-20',
                'jumlah_produksi' => 150,
                'created_at' => now(),
                'updated_at' => now(),
            ],
        ]);
        $laporanId = DB::table('laporan_pimpinan')->insertGetId([
            'periode_id' => $periodeId,
            'generated_by_user_id' => $admin->id,
            'jenis_laporan' => 'rekap_bulanan',
            'file_url' => '/dummy/laporan.pdf',
            'generated_at' => now(),
            'created_at' => now(),
            'updated_at' => now(),
        ]);

        $response = $this->actingAs($admin)
            ->from('/admin/laporan')
            ->post(route('admin.laporan.generate_detail', $laporanId));

        $response->assertRedirect('/admin/laporan');
        $response->assertSessionHas('success');

        $detail = DB::table('laporan_pimpinan_kecamatan')
            ->where('laporan_id', $laporanId)
            ->where('kecamatan_id', $kecamatanId)
            ->first();

        $this->assertNotNull($detail);
        $this->assertEquals(250.0, (float) $detail->total_produksi);
        $this->assertEquals(2.5, (float) $detail->total_luas);
        $this->assertEquals(1, (int) $detail->total_petani);
        $this->assertEquals(1, (int) $detail->total_lahan);
    }

    public function test_admin_laporan_creates_accessible_system_pdf_url_automatically(): void
    {
        $admin = User::factory()->create([
            'role' => User::ROLE_ADMIN_DINAS,
            'is_active' => true,
        ]);

        $periodeId = DB::table('periode_laporan')->insertGetId([
            'bulan' => 4,
            'tahun' => 2026,
            'tanggal_mulai' => '2026-04-01',
            'tanggal_selesai' => '2026-04-30',
            'status_periode' => 'terbuka',
            'created_at' => now(),
            'updated_at' => now(),
        ]);

        $response = $this->actingAs($admin)
            ->from('/admin/laporan')
            ->post(route('admin.laporan.store'), [
                'periode_id' => $periodeId,
                'jenis_laporan' => 'rekap_pdf_otomatis',
            ]);

        $response->assertRedirect('/admin/laporan');
        $response->assertSessionHas('success');

        $laporan = DB::table('laporan_pimpinan')->latest('id')->first();

        $this->assertNotNull($laporan);
        $this->assertSame(
            route('laporan.preview_pdf', ['laporanId' => $laporan->id], true),
            $laporan->file_url
        );

        $pdfResponse = $this->actingAs($admin)->get(route('laporan.preview_pdf', ['laporanId' => $laporan->id]));

        $pdfResponse->assertOk();
        $pdfResponse->assertHeader('content-type', 'application/pdf');
    }

    private function assignWilayah(int $userId, int $kecamatanId): void
    {
        DB::table('user_wilayah')->insert([
            'user_id' => $userId,
            'kecamatan_id' => $kecamatanId,
            'is_primary' => true,
            'created_at' => now(),
            'updated_at' => now(),
        ]);
    }

    private function createKecamatan(?string $name = null): int
    {
        $suffix = $this->nextSequence();

        return DB::table('kecamatan')->insertGetId([
            'kode_kecamatan' => 'KEC'.$suffix,
            'nama_kecamatan' => $name ?? 'Kecamatan '.$suffix,
            'is_active' => true,
            'created_at' => now(),
            'updated_at' => now(),
        ]);
    }

    private function createDesa(int $kecamatanId, ?string $name = null): int
    {
        $suffix = $this->nextSequence();

        return DB::table('desa')->insertGetId([
            'kecamatan_id' => $kecamatanId,
            'kode_desa' => 'DES'.$suffix,
            'nama_desa' => $name ?? 'Desa '.$suffix,
            'is_active' => true,
            'created_at' => now(),
            'updated_at' => now(),
        ]);
    }

    private function createBalai(int $kecamatanId, ?string $name = null): int
    {
        $suffix = $this->nextSequence();

        return DB::table('balai_penyuluh')->insertGetId([
            'kecamatan_id' => $kecamatanId,
            'nama_balai' => $name ?? 'Balai '.$suffix,
            'alamat_balai' => 'Alamat Balai '.$suffix,
            'is_active' => true,
            'created_at' => now(),
            'updated_at' => now(),
        ]);
    }

    private function createPenyuluhProfile(int $balaiId): array
    {
        $user = User::factory()->create([
            'role' => User::ROLE_PENYULUH,
            'is_active' => true,
        ]);

        $penyuluhId = DB::table('penyuluh')->insertGetId([
            'user_id' => $user->id,
            'balai_id' => $balaiId,
            'jabatan' => 'Penyuluh Lapangan',
            'foto_penyuluh_url' => null,
            'is_active' => true,
            'created_at' => now(),
            'updated_at' => now(),
        ]);

        return [
            'user' => $user,
            'penyuluh_id' => $penyuluhId,
        ];
    }

    private function createPetani(int $desaId, ?string $name = null): int
    {
        $suffix = $this->nextSequence();

        return DB::table('petani')->insertGetId([
            'desa_id' => $desaId,
            'nama_petani' => $name ?? 'Petani '.$suffix,
            'is_active' => true,
            'created_at' => now(),
            'updated_at' => now(),
        ]);
    }

    private function createLahan(int $petaniId, int $desaId, float $luasHa): int
    {
        $suffix = $this->nextSequence();

        return DB::table('lahan')->insertGetId([
            'petani_id' => $petaniId,
            'desa_id' => $desaId,
            'alamat_lahan' => 'Lahan '.$suffix,
            'luas_ha' => $luasHa,
            'kondisi_lahan' => 'baik',
            'is_active' => true,
            'created_at' => now(),
            'updated_at' => now(),
        ]);
    }

    private function createPenugasan(int $penyuluhId, int $lahanId, int $dibuatOlehUserId): int
    {
        return DB::table('penugasan_penyuluh')->insertGetId([
            'penyuluh_id' => $penyuluhId,
            'lahan_id' => $lahanId,
            'dibuat_oleh_user_id' => $dibuatOlehUserId,
            'tanggal_mulai' => '2026-03-01',
            'status_penugasan' => 'aktif',
            'created_at' => now(),
            'updated_at' => now(),
        ]);
    }

    private function createKunjungan(int $penugasanId, string $status): int
    {
        return DB::table('kunjungan_monitoring')->insertGetId([
            'penugasan_id' => $penugasanId,
            'tanggal_kunjungan' => '2026-03-15 08:00:00',
            'kondisi_tanaman' => 'Baik',
            'catatan_umum' => 'Pemantauan awal',
            'rekomendasi' => 'Lanjutkan perawatan',
            'status_verifikasi' => $status,
            'created_at' => now(),
            'updated_at' => now(),
        ]);
    }

    private function nextSequence(): int
    {
        return $this->sequence++;
    }
}
