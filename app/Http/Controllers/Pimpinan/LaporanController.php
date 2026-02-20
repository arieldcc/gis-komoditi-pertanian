<?php

namespace App\Http\Controllers\Pimpinan;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\View\View;

class LaporanController extends Controller
{
    public function index(Request $request): View
    {
        $laporanId = (int) $request->integer('laporan_id');

        $laporan = DB::table('laporan_pimpinan')
            ->join('periode_laporan', 'periode_laporan.id', '=', 'laporan_pimpinan.periode_id')
            ->join('users', 'users.id', '=', 'laporan_pimpinan.generated_by_user_id')
            ->select(
                'laporan_pimpinan.*',
                'periode_laporan.bulan',
                'periode_laporan.tahun',
                'users.name as generated_by'
            )
            ->orderByDesc('laporan_pimpinan.generated_at')
            ->get();

        if (! $laporanId && $laporan->isNotEmpty()) {
            $laporanId = (int) $laporan->first()->id;
        }

        $detail = collect();
        if ($laporanId > 0) {
            $detail = DB::table('laporan_pimpinan_kecamatan')
                ->join('kecamatan', 'kecamatan.id', '=', 'laporan_pimpinan_kecamatan.kecamatan_id')
                ->where('laporan_pimpinan_kecamatan.laporan_id', $laporanId)
                ->select('laporan_pimpinan_kecamatan.*', 'kecamatan.nama_kecamatan')
                ->orderByDesc('laporan_pimpinan_kecamatan.total_produksi')
                ->get();
        }

        return view('pimpinan.laporan.index', compact('laporan', 'laporanId', 'detail'));
    }
}
