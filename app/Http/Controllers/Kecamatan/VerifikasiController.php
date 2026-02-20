<?php

namespace App\Http\Controllers\Kecamatan;

use App\Http\Controllers\Controller;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\View\View;

class VerifikasiController extends Controller
{
    private function managedKecamatanIds(): array
    {
        return DB::table('user_wilayah')
            ->where('user_id', auth()->id())
            ->pluck('kecamatan_id')
            ->map(fn ($x) => (int) $x)
            ->toArray();
    }

    public function index(Request $request): View
    {
        $status = $request->string('status')->toString();
        $kecamatanIds = $this->managedKecamatanIds();

        $pendingUsulanCount = DB::table('usulan_perubahan_data')
            ->join('penugasan_penyuluh', 'penugasan_penyuluh.id', '=', 'usulan_perubahan_data.penugasan_id')
            ->join('lahan', 'lahan.id', '=', 'penugasan_penyuluh.lahan_id')
            ->join('desa', 'desa.id', '=', 'lahan.desa_id')
            ->whereIn('desa.kecamatan_id', $kecamatanIds)
            ->where('usulan_perubahan_data.status', 'menunggu')
            ->count();

        $kunjungan = DB::table('kunjungan_monitoring')
            ->join('penugasan_penyuluh', 'penugasan_penyuluh.id', '=', 'kunjungan_monitoring.penugasan_id')
            ->join('lahan', 'lahan.id', '=', 'penugasan_penyuluh.lahan_id')
            ->join('petani', 'petani.id', '=', 'lahan.petani_id')
            ->join('desa', 'desa.id', '=', 'lahan.desa_id')
            ->join('kecamatan', 'kecamatan.id', '=', 'desa.kecamatan_id')
            ->join('penyuluh', 'penyuluh.id', '=', 'penugasan_penyuluh.penyuluh_id')
            ->join('users', 'users.id', '=', 'penyuluh.user_id')
            ->whereIn('kecamatan.id', $kecamatanIds)
            ->when($status, fn ($q) => $q->where('kunjungan_monitoring.status_verifikasi', $status))
            ->select(
                'kunjungan_monitoring.*',
                'users.name as nama_penyuluh',
                'petani.nama_petani',
                'kecamatan.nama_kecamatan'
            )
            ->orderByDesc('kunjungan_monitoring.tanggal_kunjungan')
            ->paginate(15)
            ->withQueryString();

        return view('kecamatan.verifikasi.index', compact('kunjungan', 'status', 'pendingUsulanCount'));
    }

    public function update(Request $request, int $kunjunganId): RedirectResponse
    {
        $data = $request->validate([
            'status_verifikasi' => ['required', 'in:revisi,disetujui'],
            'catatan_verifikasi' => ['nullable', 'string', 'required_if:status_verifikasi,revisi'],
        ], [
            'catatan_verifikasi.required_if' => 'Keterangan revisi wajib diisi saat memilih respon Revisi.',
        ]);

        DB::table('kunjungan_monitoring')->where('id', $kunjunganId)->update([
            'status_verifikasi' => $data['status_verifikasi'],
            'updated_at' => now(),
        ]);

        DB::table('verifikasi_log')->insert([
            'kunjungan_id' => $kunjunganId,
            'diverifikasi_oleh_user_id' => auth()->id(),
            'status_verifikasi' => $data['status_verifikasi'],
            'catatan_verifikasi' => $data['catatan_verifikasi'] ?? null,
            'diverifikasi_at' => now(),
            'created_at' => now(),
            'updated_at' => now(),
        ]);

        return back()->with('success', 'Verifikasi laporan berhasil diperbarui.');
    }

    public function updateUsulan(Request $request, int $id): RedirectResponse
    {
        $data = $request->validate([
            'status' => ['required', 'in:disetujui,ditolak'],
            'catatan_admin' => ['nullable', 'string'],
        ]);

        $kecamatanIds = $this->managedKecamatanIds();
        $usulan = DB::table('usulan_perubahan_data')
            ->join('penugasan_penyuluh', 'penugasan_penyuluh.id', '=', 'usulan_perubahan_data.penugasan_id')
            ->join('lahan', 'lahan.id', '=', 'penugasan_penyuluh.lahan_id')
            ->join('desa', 'desa.id', '=', 'lahan.desa_id')
            ->whereIn('desa.kecamatan_id', $kecamatanIds)
            ->where('usulan_perubahan_data.id', $id)
            ->select(
                'usulan_perubahan_data.*',
                'lahan.petani_id'
            )
            ->first();

        if (! $usulan) {
            return back()->with('error', 'Usulan perubahan data tidak ditemukan.');
        }

        if ($usulan->status !== 'menunggu') {
            return back()->with('error', 'Usulan ini sudah pernah diproses.');
        }

        if ($data['status'] === 'disetujui') {
            $this->applyApprovedChange($usulan);
        }

        DB::table('usulan_perubahan_data')->where('id', $id)->update([
            'status' => $data['status'],
            'catatan_admin' => $data['catatan_admin'] ?? null,
            'diproses_oleh_user_id' => auth()->id(),
            'diproses_at' => now(),
            'updated_at' => now(),
        ]);

        DB::table('notifikasi')->insert([
            'user_id' => $usulan->diajukan_oleh_user_id,
            'judul' => 'Status usulan perubahan data',
            'pesan' => 'Usulan perubahan data Anda telah '.$data['status'].'.',
            'ref_tipe' => 'usulan_perubahan_data',
            'ref_id' => $id,
            'is_read' => false,
            'created_at' => now(),
            'updated_at' => now(),
        ]);

        return back()->with('success', 'Usulan perubahan data berhasil diproses.');
    }

    private function applyApprovedChange(object $usulan): void
    {
        $config = [
            'lahan' => [
                'table' => 'lahan',
                'fields' => ['latitude', 'longitude', 'luas_ha', 'kondisi_lahan', 'alamat_lahan'],
                'numeric' => ['latitude', 'longitude', 'luas_ha'],
            ],
            'lahan_komoditas' => [
                'table' => 'lahan_komoditas',
                'fields' => ['latitude', 'longitude', 'luas_tanam_ha', 'status_tanam', 'alamat_titik'],
                'numeric' => ['latitude', 'longitude', 'luas_tanam_ha'],
            ],
            'petani' => [
                'table' => 'petani',
                'fields' => ['no_hp', 'alamat_domisili', 'kelompok_tani'],
                'numeric' => [],
            ],
        ];

        $targetConfig = $config[$usulan->target_tipe] ?? null;
        if (! $targetConfig) {
            return;
        }

        if (! in_array($usulan->field_name, $targetConfig['fields'], true)) {
            return;
        }

        $value = $usulan->nilai_usulan;
        if (in_array($usulan->field_name, $targetConfig['numeric'], true)) {
            $value = is_null($value) || $value === '' ? null : (float) $value;
        }

        DB::table($targetConfig['table'])
            ->where('id', $usulan->target_id)
            ->update([
                $usulan->field_name => $value,
                'updated_at' => now(),
            ]);
    }
}
