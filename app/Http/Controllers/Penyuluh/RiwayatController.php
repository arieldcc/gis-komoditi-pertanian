<?php

namespace App\Http\Controllers\Penyuluh;

use App\Http\Controllers\Controller;
use Illuminate\Support\Facades\DB;
use Illuminate\View\View;

class RiwayatController extends Controller
{
    private function penyuluhId(): ?int
    {
        return DB::table('penyuluh')->where('user_id', auth()->id())->value('id');
    }

    public function index(): View
    {
        $penyuluhId = $this->penyuluhId();

        $riwayat = collect();
        if ($penyuluhId) {
            $riwayat = DB::table('kunjungan_monitoring')
                ->join('penugasan_penyuluh', 'penugasan_penyuluh.id', '=', 'kunjungan_monitoring.penugasan_id')
                ->join('lahan', 'lahan.id', '=', 'penugasan_penyuluh.lahan_id')
                ->join('petani', 'petani.id', '=', 'lahan.petani_id')
                ->leftJoin('verifikasi_log', function ($join): void {
                    $join->on('verifikasi_log.kunjungan_id', '=', 'kunjungan_monitoring.id')
                        ->whereRaw('verifikasi_log.id = (SELECT MAX(v2.id) FROM verifikasi_log v2 WHERE v2.kunjungan_id = kunjungan_monitoring.id)');
                })
                ->where('penugasan_penyuluh.penyuluh_id', $penyuluhId)
                ->select(
                    'kunjungan_monitoring.*',
                    'petani.nama_petani',
                    'verifikasi_log.catatan_verifikasi',
                    'verifikasi_log.diverifikasi_at'
                )
                ->orderByDesc('kunjungan_monitoring.tanggal_kunjungan')
                ->get();
        }

        return view('penyuluh.riwayat.index', compact('penyuluhId', 'riwayat'));
    }
}
