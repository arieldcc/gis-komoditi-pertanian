<?php

namespace Tests\Feature;

use App\Models\User;
use App\Support\AdminCascadeDeleteService;
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
        ['user' => $penyuluhUser, 'penyuluh_id' => $penyuluhId] = $this->createPenyuluhProfile($balaiId);

        $response = $this->actingAs($admin)
            ->from('/admin/balai')
            ->delete(route('admin.balai.penyuluh.destroy', $penyuluhId));

        $response->assertRedirect('/admin/balai');
        $response->assertSessionHas('success');

        $this->assertDatabaseMissing('penyuluh', ['id' => $penyuluhId]);
        $this->assertDatabaseMissing('users', ['id' => $penyuluhUser->id]);
    }

    public function test_admin_cannot_delete_penyuluh_that_still_has_penugasan(): void
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
        $response->assertSessionHas('error', 'Data penyuluh tidak dapat dihapus karena masih memiliki data penugasan.');
        $this->assertDatabaseHas('penugasan_penyuluh', ['id' => $penugasanId]);
        $this->assertDatabaseHas('penyuluh', ['id' => $penyuluhId]);
        $this->assertDatabaseHas('users', ['id' => $penyuluhUser->id]);
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

    public function test_admin_cannot_delete_komoditas_that_is_still_used(): void
    {
        $admin = User::factory()->create([
            'role' => User::ROLE_ADMIN_DINAS,
            'is_active' => true,
        ]);
        $kecamatanId = $this->createKecamatan();
        $desaId = $this->createDesa($kecamatanId);
        $petaniId = $this->createPetani($desaId);
        $lahanId = $this->createLahan($petaniId, $desaId, 1.25);
        $komoditasId = DB::table('komoditas')->insertGetId([
            'kode_komoditas' => 'KMD'.$this->nextSequence(),
            'nama_komoditas' => 'Komoditas Pakai '.$this->nextSequence(),
            'satuan_default' => 'kg',
            'is_active' => true,
            'created_at' => now(),
            'updated_at' => now(),
        ]);

        DB::table('lahan_komoditas')->insert([
            'lahan_id' => $lahanId,
            'komoditas_id' => $komoditasId,
            'tahun_tanam' => 2026,
            'luas_tanam_ha' => 1.25,
            'status_tanam' => 'tanam',
            'created_at' => now(),
            'updated_at' => now(),
        ]);

        $response = $this->actingAs($admin)
            ->from('/admin/komoditas')
            ->delete(route('admin.komoditas.destroy', $komoditasId));

        $response->assertRedirect('/admin/komoditas');
        $response->assertSessionHas('error', 'Komoditas tidak dapat dihapus karena sudah dipakai pada data lahan komoditas.');
        $this->assertDatabaseHas('komoditas', ['id' => $komoditasId]);
    }

    public function test_kecamatan_cannot_delete_petani_that_still_has_lahan(): void
    {
        $adminKecamatan = User::factory()->create([
            'role' => User::ROLE_ADMIN_KECAMATAN,
            'is_active' => true,
        ]);
        $kecamatanId = $this->createKecamatan();
        $desaId = $this->createDesa($kecamatanId);
        $this->assignWilayah($adminKecamatan->id, $kecamatanId);
        $petaniId = $this->createPetani($desaId);
        $this->createLahan($petaniId, $desaId, 1.10);

        $response = $this->actingAs($adminKecamatan)
            ->from('/kecamatan/petani-lahan')
            ->delete(route('kecamatan.petani_lahan.petani.destroy', $petaniId));

        $response->assertRedirect('/kecamatan/petani-lahan');
        $response->assertSessionHas('error', 'Petani tidak dapat dihapus karena masih memiliki data lahan.');
        $this->assertDatabaseHas('petani', ['id' => $petaniId]);
    }

    public function test_admin_force_delete_requires_exact_confirmation_key(): void
    {
        $admin = User::factory()->create([
            'role' => User::ROLE_ADMIN_DINAS,
            'is_active' => true,
        ]);
        $kecamatanId = $this->createKecamatan();

        $response = $this->actingAs($admin)->deleteJson(
            route('admin.cascade_delete.destroy', ['entity' => 'kecamatan', 'id' => $kecamatanId]),
            ['confirmation_key' => 'SALAH']
        );

        $response->assertStatus(422);
        $response->assertJson([
            'message' => 'Kunci konfirmasi tidak sesuai. Ketik persis '.app(AdminCascadeDeleteService::class)->confirmationKey('kecamatan', $kecamatanId).'.',
        ]);

        $this->assertDatabaseHas('kecamatan', ['id' => $kecamatanId]);
    }

    public function test_admin_force_delete_kecamatan_removes_descendant_operational_data(): void
    {
        $admin = User::factory()->create([
            'role' => User::ROLE_ADMIN_DINAS,
            'is_active' => true,
        ]);

        $kecamatanId = $this->createKecamatan();
        $desaId = $this->createDesa($kecamatanId);
        $balaiId = $this->createBalai($kecamatanId);
        $adminKecamatan = User::factory()->create([
            'role' => User::ROLE_ADMIN_KECAMATAN,
            'is_active' => true,
        ]);
        $this->assignWilayah($adminKecamatan->id, $kecamatanId);

        ['user' => $penyuluhUser, 'penyuluh_id' => $penyuluhId] = $this->createPenyuluhProfile($balaiId);
        $petaniId = $this->createPetani($desaId);
        $lahanId = $this->createLahan($petaniId, $desaId, 2.35);
        $komoditasId = $this->createKomoditas();
        $lahanKomoditasId = DB::table('lahan_komoditas')->insertGetId([
            'lahan_id' => $lahanId,
            'komoditas_id' => $komoditasId,
            'tahun_tanam' => 2026,
            'luas_tanam_ha' => 2.35,
            'status_tanam' => 'tanam',
            'created_at' => now(),
            'updated_at' => now(),
        ]);
        $penugasanId = $this->createPenugasan($penyuluhId, $lahanId, $adminKecamatan->id);
        $kunjunganId = $this->createKunjungan($penugasanId, 'menunggu');

        DB::table('produksi_panen')->insert([
            'lahan_komoditas_id' => $lahanKomoditasId,
            'periode_id' => $this->createPeriode(),
            'kunjungan_id' => $kunjunganId,
            'tanggal_panen' => '2026-03-25',
            'jumlah_produksi' => 300,
            'created_at' => now(),
            'updated_at' => now(),
        ]);

        $usulanId = DB::table('usulan_perubahan_data')->insertGetId([
            'penugasan_id' => $penugasanId,
            'kunjungan_id' => $kunjunganId,
            'diajukan_oleh_user_id' => $penyuluhUser->id,
            'target_tipe' => 'lahan',
            'target_id' => $lahanId,
            'field_name' => 'luas_ha',
            'nilai_lama' => '2.35',
            'nilai_usulan' => '2.50',
            'alasan' => 'Koreksi data lapangan',
            'status' => 'menunggu',
            'waktu_pengajuan' => now(),
            'created_at' => now(),
            'updated_at' => now(),
        ]);

        DB::table('notifikasi')->insert([
            [
                'user_id' => $adminKecamatan->id,
                'judul' => 'Laporan Monitoring Baru',
                'pesan' => 'Ada laporan baru.',
                'ref_tipe' => 'kunjungan_monitoring',
                'ref_id' => $kunjunganId,
                'is_read' => false,
                'created_at' => now(),
                'updated_at' => now(),
            ],
            [
                'user_id' => $penyuluhUser->id,
                'judul' => 'Status usulan perubahan data',
                'pesan' => 'Usulan diproses.',
                'ref_tipe' => 'usulan_perubahan_data',
                'ref_id' => $usulanId,
                'is_read' => false,
                'created_at' => now(),
                'updated_at' => now(),
            ],
        ]);

        DB::table('laporan_pimpinan')->insert([
            'periode_id' => $this->createPeriode(4),
            'generated_by_user_id' => $admin->id,
            'jenis_laporan' => 'rekap_uji',
            'file_url' => '/dummy/laporan-uji.pdf',
            'generated_at' => now(),
            'created_at' => now(),
            'updated_at' => now(),
        ]);

        DB::table('laporan_pimpinan_kecamatan')->insert([
            'laporan_id' => DB::table('laporan_pimpinan')->max('id'),
            'kecamatan_id' => $kecamatanId,
            'total_produksi' => 300,
            'total_luas' => 2.35,
            'total_petani' => 1,
            'total_lahan' => 1,
            'created_at' => now(),
            'updated_at' => now(),
        ]);

        DB::table('password_reset_tokens')->insert([
            'email' => $penyuluhUser->email,
            'token' => 'dummy-token',
            'created_at' => now(),
        ]);

        $response = $this->actingAs($admin)->deleteJson(
            route('admin.cascade_delete.destroy', ['entity' => 'kecamatan', 'id' => $kecamatanId]),
            ['confirmation_key' => app(AdminCascadeDeleteService::class)->confirmationKey('kecamatan', $kecamatanId)]
        );

        $response->assertOk();

        $this->assertDatabaseMissing('kecamatan', ['id' => $kecamatanId]);
        $this->assertDatabaseMissing('desa', ['id' => $desaId]);
        $this->assertDatabaseMissing('balai_penyuluh', ['id' => $balaiId]);
        $this->assertDatabaseMissing('user_wilayah', ['kecamatan_id' => $kecamatanId]);
        $this->assertDatabaseMissing('users', ['id' => $adminKecamatan->id]);
        $this->assertDatabaseMissing('penyuluh', ['id' => $penyuluhId]);
        $this->assertDatabaseMissing('users', ['id' => $penyuluhUser->id]);
        $this->assertDatabaseMissing('petani', ['id' => $petaniId]);
        $this->assertDatabaseMissing('lahan', ['id' => $lahanId]);
        $this->assertDatabaseMissing('lahan_komoditas', ['id' => $lahanKomoditasId]);
        $this->assertDatabaseMissing('penugasan_penyuluh', ['id' => $penugasanId]);
        $this->assertDatabaseMissing('kunjungan_monitoring', ['id' => $kunjunganId]);
        $this->assertDatabaseMissing('produksi_panen', ['kunjungan_id' => $kunjunganId]);
        $this->assertDatabaseMissing('usulan_perubahan_data', ['id' => $usulanId]);
        $this->assertDatabaseMissing('laporan_pimpinan_kecamatan', ['kecamatan_id' => $kecamatanId]);
        $this->assertDatabaseMissing('notifikasi', ['ref_tipe' => 'kunjungan_monitoring', 'ref_id' => $kunjunganId]);
        $this->assertDatabaseMissing('notifikasi', ['ref_tipe' => 'usulan_perubahan_data', 'ref_id' => $usulanId]);
        $this->assertDatabaseMissing('password_reset_tokens', ['email' => $penyuluhUser->email]);
    }

    public function test_admin_force_delete_komoditas_removes_downstream_rows_but_preserves_lahan(): void
    {
        $admin = User::factory()->create([
            'role' => User::ROLE_ADMIN_DINAS,
            'is_active' => true,
        ]);

        $kecamatanId = $this->createKecamatan();
        $desaId = $this->createDesa($kecamatanId);
        $petaniId = $this->createPetani($desaId);
        $lahanId = $this->createLahan($petaniId, $desaId, 1.40);
        $komoditasId = $this->createKomoditas();
        $lahanKomoditasId = DB::table('lahan_komoditas')->insertGetId([
            'lahan_id' => $lahanId,
            'komoditas_id' => $komoditasId,
            'tahun_tanam' => 2026,
            'luas_tanam_ha' => 1.40,
            'status_tanam' => 'panen',
            'created_at' => now(),
            'updated_at' => now(),
        ]);

        DB::table('map_marker_styles')->insert([
            'style_code' => 'komoditas:'.$komoditasId,
            'scope' => 'komoditas',
            'komoditas_id' => $komoditasId,
            'label' => 'Komoditas Uji',
            'icon_symbol' => 'U',
            'icon_color' => '#ffffff',
            'bg_color' => '#198754',
            'size' => 28,
            'created_at' => now(),
            'updated_at' => now(),
        ]);

        DB::table('produksi_panen')->insert([
            'lahan_komoditas_id' => $lahanKomoditasId,
            'periode_id' => $this->createPeriode(5),
            'tanggal_panen' => '2026-05-18',
            'jumlah_produksi' => 120,
            'created_at' => now(),
            'updated_at' => now(),
        ]);

        $response = $this->actingAs($admin)->deleteJson(
            route('admin.cascade_delete.destroy', ['entity' => 'komoditas', 'id' => $komoditasId]),
            ['confirmation_key' => app(AdminCascadeDeleteService::class)->confirmationKey('komoditas', $komoditasId)]
        );

        $response->assertOk();

        $this->assertDatabaseMissing('komoditas', ['id' => $komoditasId]);
        $this->assertDatabaseMissing('lahan_komoditas', ['id' => $lahanKomoditasId]);
        $this->assertDatabaseMissing('produksi_panen', ['lahan_komoditas_id' => $lahanKomoditasId]);
        $this->assertDatabaseMissing('map_marker_styles', ['komoditas_id' => $komoditasId]);
        $this->assertDatabaseHas('lahan', ['id' => $lahanId]);
        $this->assertDatabaseHas('petani', ['id' => $petaniId]);
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

    private function createKomoditas(): int
    {
        $suffix = $this->nextSequence();

        return DB::table('komoditas')->insertGetId([
            'kode_komoditas' => 'KMD'.$suffix,
            'nama_komoditas' => 'Komoditas '.$suffix,
            'satuan_default' => 'kg',
            'is_active' => true,
            'created_at' => now(),
            'updated_at' => now(),
        ]);
    }

    private function createPeriode(int $bulan = 3): int
    {
        return DB::table('periode_laporan')->insertGetId([
            'bulan' => $bulan,
            'tahun' => 2026,
            'tanggal_mulai' => sprintf('2026-%02d-01', $bulan),
            'tanggal_selesai' => sprintf('2026-%02d-28', $bulan),
            'status_periode' => 'terbuka',
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
