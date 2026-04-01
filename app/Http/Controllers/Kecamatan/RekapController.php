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

        $petaniSummary = DB::table('desa')
            ->leftJoin('petani', 'petani.desa_id', '=', 'desa.id')
            ->groupBy('desa.kecamatan_id')
            ->selectRaw('desa.kecamatan_id')
            ->selectRaw('COUNT(DISTINCT petani.id) as total_petani');

        $lahanSummary = DB::table('desa')
            ->leftJoin('lahan', 'lahan.desa_id', '=', 'desa.id')
            ->groupBy('desa.kecamatan_id')
            ->selectRaw('desa.kecamatan_id')
            ->selectRaw('COUNT(DISTINCT lahan.id) as total_lahan')
            ->selectRaw('COALESCE(SUM(lahan.luas_ha),0) as total_luas');

        $summary = DB::table('kecamatan')
            ->leftJoinSub($petaniSummary, 'petani_summary', function ($join): void {
                $join->on('petani_summary.kecamatan_id', '=', 'kecamatan.id');
            })
            ->leftJoinSub($lahanSummary, 'lahan_summary', function ($join): void {
                $join->on('lahan_summary.kecamatan_id', '=', 'kecamatan.id');
            })
            ->whereIn('kecamatan.id', $kecamatanIds)
            ->selectRaw('kecamatan.nama_kecamatan')
            ->selectRaw('COALESCE(petani_summary.total_petani,0) as total_petani')
            ->selectRaw('COALESCE(lahan_summary.total_lahan,0) as total_lahan')
            ->selectRaw('COALESCE(lahan_summary.total_luas,0) as total_luas')
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
