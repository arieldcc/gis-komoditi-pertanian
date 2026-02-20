<?php

namespace App\Http\Controllers\Pimpinan;

use App\Http\Controllers\Controller;
use Illuminate\Support\Facades\DB;
use Illuminate\View\View;

class AnalitikController extends Controller
{
    public function index(): View
    {
        $trenKomoditas = DB::table('produksi_panen')
            ->join('lahan_komoditas', 'lahan_komoditas.id', '=', 'produksi_panen.lahan_komoditas_id')
            ->join('komoditas', 'komoditas.id', '=', 'lahan_komoditas.komoditas_id')
            ->groupBy('komoditas.id', 'komoditas.nama_komoditas')
            ->selectRaw('komoditas.nama_komoditas')
            ->selectRaw('COALESCE(SUM(produksi_panen.jumlah_produksi),0) as total_produksi')
            ->selectRaw('COALESCE(AVG(produksi_panen.harga_jual),0) as rata_harga')
            ->orderByDesc('total_produksi')
            ->get();

        $trenPeriode = DB::table('produksi_panen')
            ->join('periode_laporan', 'periode_laporan.id', '=', 'produksi_panen.periode_id')
            ->groupBy('periode_laporan.id', 'periode_laporan.bulan', 'periode_laporan.tahun')
            ->selectRaw('periode_laporan.bulan, periode_laporan.tahun')
            ->selectRaw('COALESCE(SUM(produksi_panen.jumlah_produksi),0) as total_produksi')
            ->orderBy('periode_laporan.tahun')
            ->orderBy('periode_laporan.bulan')
            ->get();

        $ringkasan = [
            'total_produksi' => DB::table('produksi_panen')->sum('jumlah_produksi'),
            'rata_harga' => DB::table('produksi_panen')->avg('harga_jual') ?: 0,
            'komoditas_aktif' => DB::table('komoditas')->where('is_active', true)->count(),
            'laporan_terbit' => DB::table('laporan_pimpinan')->count(),
        ];

        return view('pimpinan.analitik.index', compact('trenKomoditas', 'trenPeriode', 'ringkasan'));
    }
}
