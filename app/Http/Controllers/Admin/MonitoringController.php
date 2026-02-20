<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\View\View;

class MonitoringController extends Controller
{
    public function index(Request $request): View
    {
        $status = $request->string('status')->toString();

        $kunjungan = DB::table('kunjungan_monitoring')
            ->join('penugasan_penyuluh', 'penugasan_penyuluh.id', '=', 'kunjungan_monitoring.penugasan_id')
            ->join('penyuluh', 'penyuluh.id', '=', 'penugasan_penyuluh.penyuluh_id')
            ->join('users', 'users.id', '=', 'penyuluh.user_id')
            ->join('lahan', 'lahan.id', '=', 'penugasan_penyuluh.lahan_id')
            ->join('petani', 'petani.id', '=', 'lahan.petani_id')
            ->leftJoin('verifikasi_log', function ($join): void {
                $join->on('verifikasi_log.kunjungan_id', '=', 'kunjungan_monitoring.id')
                    ->whereRaw('verifikasi_log.id = (SELECT MAX(v2.id) FROM verifikasi_log v2 WHERE v2.kunjungan_id = kunjungan_monitoring.id)');
            })
            ->when($status, fn ($q) => $q->where('kunjungan_monitoring.status_verifikasi', $status))
            ->select(
                'kunjungan_monitoring.*',
                'users.name as nama_penyuluh',
                'petani.nama_petani',
                'verifikasi_log.catatan_verifikasi',
                'verifikasi_log.diverifikasi_at'
            )
            ->orderByDesc('kunjungan_monitoring.tanggal_kunjungan')
            ->paginate(15)
            ->withQueryString();

        return view('admin.monitoring.index', [
            'kunjungan' => $kunjungan,
            'statusFilter' => $status,
        ]);
    }

    public function verify(Request $request, int $kunjunganId): RedirectResponse
    {
        $data = $request->validate([
            'status_verifikasi' => ['required', 'in:menunggu,revisi,ditolak,disetujui'],
            'catatan_verifikasi' => ['nullable', 'string'],
        ]);

        DB::table('kunjungan_monitoring')
            ->where('id', $kunjunganId)
            ->update([
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

        return back()->with('success', 'Status verifikasi kunjungan berhasil diperbarui.');
    }
}
