<?php

namespace App\Http\Controllers;

use App\Support\MapStyleSupport;
use Illuminate\Support\Facades\DB;
use Illuminate\View\View;

class LandingController extends Controller
{
    public function index(): View
    {
        MapStyleSupport::ensureDefaultRows();
        $mapStyles = MapStyleSupport::styleMap();

        $komoditasList = DB::table('komoditas')
            ->where('is_active', true)
            ->orderBy('nama_komoditas')
            ->get(['id', 'nama_komoditas', 'satuan_default']);

        $landingKomoditasMarkers = DB::table('lahan_komoditas')
            ->join('lahan', 'lahan.id', '=', 'lahan_komoditas.lahan_id')
            ->join('komoditas', 'komoditas.id', '=', 'lahan_komoditas.komoditas_id')
            ->join('petani', 'petani.id', '=', 'lahan.petani_id')
            ->join('desa', 'desa.id', '=', 'lahan.desa_id')
            ->join('kecamatan', 'kecamatan.id', '=', 'desa.kecamatan_id')
            ->whereNotNull('lahan_komoditas.latitude')
            ->whereNotNull('lahan_komoditas.longitude')
            ->select(
                'lahan_komoditas.latitude',
                'lahan_komoditas.longitude',
                'lahan_komoditas.alamat_titik',
                'lahan.foto_lahan_url',
                'komoditas.id as komoditas_id',
                'komoditas.nama_komoditas',
                'petani.nama_petani',
                'desa.nama_desa',
                'kecamatan.nama_kecamatan'
            )
            ->orderBy('komoditas.nama_komoditas')
            ->get()
            ->map(function ($item) use ($mapStyles): array {
                $styleCode = 'komoditas:'.$item->komoditas_id;

                return [
                    'lat' => (float) $item->latitude,
                    'lng' => (float) $item->longitude,
                    'title' => 'Komoditas '.$item->nama_komoditas,
                    'description' => trim(($item->alamat_titik ? $item->alamat_titik.' | ' : '').'Petani: '.$item->nama_petani),
                    'komoditas_id' => (string) $item->komoditas_id,
                    'style_code' => $styleCode,
                    'style' => MapStyleSupport::iconForStyleCode($styleCode, $mapStyles),
                    'image_url' => $item->foto_lahan_url,
                    'fields' => [
                        ['label' => 'Komoditas', 'value' => $item->nama_komoditas],
                        ['label' => 'Petani', 'value' => $item->nama_petani],
                        ['label' => 'Desa', 'value' => $item->nama_desa],
                        ['label' => 'Kecamatan', 'value' => $item->nama_kecamatan],
                    ],
                ];
            })
            ->toArray();

        $statKecamatan = DB::table('kecamatan')->where('is_active', true)->count();
        $statPenyuluh = DB::table('penyuluh')->where('is_active', true)->count();
        $statKomoditas = $komoditasList->count();

        return view('welcome', compact(
            'mapStyles',
            'komoditasList',
            'landingKomoditasMarkers',
            'statKecamatan',
            'statPenyuluh',
            'statKomoditas'
        ));
    }
}
