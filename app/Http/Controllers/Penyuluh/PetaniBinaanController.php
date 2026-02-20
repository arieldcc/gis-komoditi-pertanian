<?php

namespace App\Http\Controllers\Penyuluh;

use App\Http\Controllers\Controller;
use Illuminate\Support\Facades\DB;
use Illuminate\View\View;

class PetaniBinaanController extends Controller
{
    private function penyuluhId(): ?int
    {
        return DB::table('penyuluh')->where('user_id', auth()->id())->value('id');
    }

    public function index(): View
    {
        $penyuluhId = $this->penyuluhId();

        $data = collect();
        if ($penyuluhId) {
            $data = DB::table('penugasan_penyuluh')
                ->join('lahan', 'lahan.id', '=', 'penugasan_penyuluh.lahan_id')
                ->join('petani', 'petani.id', '=', 'lahan.petani_id')
                ->join('desa', 'desa.id', '=', 'lahan.desa_id')
                ->leftJoin('lahan_komoditas', 'lahan_komoditas.lahan_id', '=', 'lahan.id')
                ->leftJoin('komoditas', 'komoditas.id', '=', 'lahan_komoditas.komoditas_id')
                ->where('penugasan_penyuluh.penyuluh_id', $penyuluhId)
                ->where('penugasan_penyuluh.status_penugasan', 'aktif')
                ->select(
                    'penugasan_penyuluh.id as penugasan_id',
                    'petani.nama_petani',
                    'petani.no_hp',
                    'desa.nama_desa',
                    'lahan.luas_ha',
                    'lahan.kondisi_lahan',
                    DB::raw('GROUP_CONCAT(DISTINCT komoditas.nama_komoditas SEPARATOR ", ") as komoditas')
                )
                ->groupBy(
                    'penugasan_penyuluh.id',
                    'petani.nama_petani',
                    'petani.no_hp',
                    'desa.nama_desa',
                    'lahan.luas_ha',
                    'lahan.kondisi_lahan'
                )
                ->orderBy('petani.nama_petani')
                ->get();
        }

        return view('penyuluh.petani.index', compact('penyuluhId', 'data'));
    }
}
