<?php

namespace App\Http\Controllers\Kecamatan;

use App\Http\Controllers\Controller;
use Illuminate\Support\Facades\DB;
use Illuminate\View\View;

class RekapController extends Controller
{
    private function managedKecamatanIds(): array
    {
        return DB::table('user_wilayah')
            ->where('user_id', auth()->id())
            ->pluck('kecamatan_id')
            ->map(fn ($x) => (int) $x)
            ->toArray();
    }

    public function index(): View
    {
        $kecamatanIds = $this->managedKecamatanIds();

        $summary = DB::table('kecamatan')
            ->leftJoin('desa', 'desa.kecamatan_id', '=', 'kecamatan.id')
            ->leftJoin('petani', 'petani.desa_id', '=', 'desa.id')
            ->leftJoin('lahan', 'lahan.desa_id', '=', 'desa.id')
            ->whereIn('kecamatan.id', $kecamatanIds)
            ->groupBy('kecamatan.id', 'kecamatan.nama_kecamatan')
            ->selectRaw('kecamatan.nama_kecamatan')
            ->selectRaw('COUNT(DISTINCT petani.id) as total_petani')
            ->selectRaw('COUNT(DISTINCT lahan.id) as total_lahan')
            ->selectRaw('COALESCE(SUM(lahan.luas_ha),0) as total_luas')
            ->orderBy('kecamatan.nama_kecamatan')
            ->get();

        $komoditas = DB::table('lahan_komoditas')
            ->join('lahan', 'lahan.id', '=', 'lahan_komoditas.lahan_id')
            ->join('desa', 'desa.id', '=', 'lahan.desa_id')
            ->join('komoditas', 'komoditas.id', '=', 'lahan_komoditas.komoditas_id')
            ->whereIn('desa.kecamatan_id', $kecamatanIds)
            ->groupBy('komoditas.id', 'komoditas.nama_komoditas')
            ->selectRaw('komoditas.nama_komoditas')
            ->selectRaw('COUNT(*) as total_lahan_komoditas')
            ->selectRaw('COALESCE(SUM(lahan_komoditas.luas_tanam_ha),0) as total_luas_tanam')
            ->orderByDesc('total_lahan_komoditas')
            ->get();

        return view('kecamatan.rekap.index', compact('summary', 'komoditas'));
    }
}
