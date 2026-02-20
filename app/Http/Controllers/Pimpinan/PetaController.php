<?php

namespace App\Http\Controllers\Pimpinan;

use App\Http\Controllers\Controller;
use App\Support\MapStyleSupport;
use Illuminate\Support\Facades\DB;
use Illuminate\View\View;

class PetaController extends Controller
{
    public function index(): View
    {
        MapStyleSupport::ensureDefaultRows();
        $mapStyles = MapStyleSupport::styleMap();

        $kecamatan = DB::table('kecamatan')
            ->leftJoin('desa', 'desa.kecamatan_id', '=', 'kecamatan.id')
            ->leftJoin('lahan', 'lahan.desa_id', '=', 'desa.id')
            ->leftJoin('lahan_komoditas', 'lahan_komoditas.lahan_id', '=', 'lahan.id')
            ->groupBy('kecamatan.id', 'kecamatan.nama_kecamatan', 'kecamatan.centroid_lat', 'kecamatan.centroid_lng')
            ->selectRaw('kecamatan.nama_kecamatan, kecamatan.centroid_lat, kecamatan.centroid_lng')
            ->selectRaw('COUNT(DISTINCT lahan.id) as total_lahan')
            ->selectRaw('COUNT(DISTINCT lahan_komoditas.komoditas_id) as total_komoditas')
            ->orderBy('kecamatan.nama_kecamatan')
            ->get();

        $kecamatanMarkers = $kecamatan
            ->filter(fn ($item) => $item->centroid_lat !== null && $item->centroid_lng !== null)
            ->map(fn ($item) => [
                'lat' => (float) $item->centroid_lat,
                'lng' => (float) $item->centroid_lng,
                'title' => 'Kecamatan '.$item->nama_kecamatan,
                'description' => 'Lahan: '.$item->total_lahan.' | Komoditas: '.$item->total_komoditas,
                'style_code' => 'entity:kecamatan',
                'style' => MapStyleSupport::iconForStyleCode('entity:kecamatan', $mapStyles),
                'fields' => [
                    ['label' => 'Kecamatan', 'value' => $item->nama_kecamatan],
                    ['label' => 'Total Lahan', 'value' => $item->total_lahan],
                    ['label' => 'Jenis Komoditas', 'value' => $item->total_komoditas],
                ],
            ])
            ->values()
            ->toArray();

        $titikBalai = DB::table('balai_penyuluh')
            ->join('kecamatan', 'kecamatan.id', '=', 'balai_penyuluh.kecamatan_id')
            ->select('balai_penyuluh.nama_balai', 'balai_penyuluh.alamat_balai', 'balai_penyuluh.foto_balai_url', 'balai_penyuluh.latitude', 'balai_penyuluh.longitude', 'kecamatan.nama_kecamatan')
            ->orderBy('kecamatan.nama_kecamatan')
            ->get();

        $balaiMarkers = $titikBalai
            ->filter(fn ($item) => $item->latitude !== null && $item->longitude !== null)
            ->map(fn ($item) => [
                'lat' => (float) $item->latitude,
                'lng' => (float) $item->longitude,
                'title' => $item->nama_balai,
                'description' => 'Balai penyuluh | Kec. '.$item->nama_kecamatan,
                'style_code' => 'entity:balai',
                'style' => MapStyleSupport::iconForStyleCode('entity:balai', $mapStyles),
                'image_url' => $item->foto_balai_url,
                'fields' => [
                    ['label' => 'Balai', 'value' => $item->nama_balai],
                    ['label' => 'Kecamatan', 'value' => $item->nama_kecamatan],
                    ['label' => 'Alamat', 'value' => $item->alamat_balai],
                ],
            ])
            ->values()
            ->toArray();

        $desaMarkers = DB::table('desa')
            ->join('kecamatan', 'kecamatan.id', '=', 'desa.kecamatan_id')
            ->whereNotNull('desa.centroid_lat')
            ->whereNotNull('desa.centroid_lng')
            ->select('desa.nama_desa', 'desa.centroid_lat', 'desa.centroid_lng', 'kecamatan.nama_kecamatan')
            ->orderBy('kecamatan.nama_kecamatan')
            ->get()
            ->map(fn ($item) => [
                'lat' => (float) $item->centroid_lat,
                'lng' => (float) $item->centroid_lng,
                'title' => 'Desa '.$item->nama_desa,
                'description' => 'Kec. '.$item->nama_kecamatan,
                'style_code' => 'entity:desa',
                'style' => MapStyleSupport::iconForStyleCode('entity:desa', $mapStyles),
                'fields' => [
                    ['label' => 'Desa', 'value' => $item->nama_desa],
                    ['label' => 'Kecamatan', 'value' => $item->nama_kecamatan],
                ],
            ])
            ->toArray();

        $komoditasMarkers = DB::table('lahan_komoditas')
            ->join('lahan', 'lahan.id', '=', 'lahan_komoditas.lahan_id')
            ->join('komoditas', 'komoditas.id', '=', 'lahan_komoditas.komoditas_id')
            ->join('petani', 'petani.id', '=', 'lahan.petani_id')
            ->whereNotNull('lahan_komoditas.latitude')
            ->whereNotNull('lahan_komoditas.longitude')
            ->select(
                'lahan_komoditas.latitude',
                'lahan_komoditas.longitude',
                'lahan_komoditas.alamat_titik',
                'lahan.foto_lahan_url',
                'komoditas.id as komoditas_id',
                'komoditas.nama_komoditas',
                'petani.nama_petani'
            )
            ->get()
            ->map(fn ($item) => [
                'lat' => (float) $item->latitude,
                'lng' => (float) $item->longitude,
                'title' => 'Komoditas '.$item->nama_komoditas,
                'description' => trim(($item->alamat_titik ? $item->alamat_titik.' | ' : '').'Petani: '.$item->nama_petani),
                'style_code' => 'komoditas:'.$item->komoditas_id,
                'style' => MapStyleSupport::iconForStyleCode('komoditas:'.$item->komoditas_id, $mapStyles),
                'image_url' => $item->foto_lahan_url,
                'fields' => [
                    ['label' => 'Komoditas', 'value' => $item->nama_komoditas],
                    ['label' => 'Petani', 'value' => $item->nama_petani],
                    ['label' => 'Alamat Titik', 'value' => $item->alamat_titik],
                ],
            ])
            ->toArray();

        $mapMarkers = array_merge($kecamatanMarkers, $desaMarkers, $balaiMarkers, $komoditasMarkers);

        return view('pimpinan.peta.index', compact('kecamatan', 'titikBalai', 'mapStyles', 'mapMarkers'));
    }
}
