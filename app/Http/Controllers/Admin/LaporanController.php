<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\Rule;
use Illuminate\View\View;

class LaporanController extends Controller
{
    public function index(): View
    {
        $periode = DB::table('periode_laporan')->orderByDesc('tahun')->orderByDesc('bulan')->get();

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

        $laporanDetail = DB::table('laporan_pimpinan_kecamatan')
            ->join('laporan_pimpinan', 'laporan_pimpinan.id', '=', 'laporan_pimpinan_kecamatan.laporan_id')
            ->join('kecamatan', 'kecamatan.id', '=', 'laporan_pimpinan_kecamatan.kecamatan_id')
            ->join('periode_laporan', 'periode_laporan.id', '=', 'laporan_pimpinan.periode_id')
            ->select(
                'laporan_pimpinan_kecamatan.*',
                'kecamatan.nama_kecamatan',
                'periode_laporan.bulan',
                'periode_laporan.tahun'
            )
            ->orderByDesc('laporan_pimpinan_kecamatan.id')
            ->limit(200)
            ->get();

        return view('admin.laporan.index', compact('periode', 'laporan', 'laporanDetail'));
    }

    public function storePeriode(Request $request): RedirectResponse
    {
        $data = $request->validate([
            'bulan' => ['required', 'integer', 'between:1,12'],
            'tahun' => ['required', 'integer', 'between:2000,2100'],
            'tanggal_mulai' => ['required', 'date'],
            'tanggal_selesai' => ['required', 'date', 'after_or_equal:tanggal_mulai'],
            'status_periode' => ['required', Rule::in(['terbuka', 'ditutup', 'arsip'])],
        ]);

        $exists = DB::table('periode_laporan')
            ->where('bulan', $data['bulan'])
            ->where('tahun', $data['tahun'])
            ->exists();

        if ($exists) {
            return back()->with('error', 'Periode bulan dan tahun sudah ada.');
        }

        DB::table('periode_laporan')->insert([
            ...$data,
            'created_at' => now(),
            'updated_at' => now(),
        ]);

        return back()->with('success', 'Periode laporan berhasil ditambahkan.');
    }

    public function updatePeriode(Request $request, int $id): RedirectResponse
    {
        $data = $request->validate([
            'status_periode' => ['required', Rule::in(['terbuka', 'ditutup', 'arsip'])],
        ]);

        DB::table('periode_laporan')->where('id', $id)->update([
            'status_periode' => $data['status_periode'],
            'updated_at' => now(),
        ]);

        return back()->with('success', 'Status periode berhasil diperbarui.');
    }

    public function storeLaporan(Request $request): RedirectResponse
    {
        $data = $request->validate([
            'periode_id' => ['required', 'exists:periode_laporan,id'],
            'jenis_laporan' => ['required', 'string', 'max:50'],
            'file_url' => ['required', 'string'],
        ]);

        DB::table('laporan_pimpinan')->insert([
            ...$data,
            'generated_by_user_id' => auth()->id(),
            'generated_at' => now(),
            'created_at' => now(),
            'updated_at' => now(),
        ]);

        return back()->with('success', 'Laporan pimpinan berhasil dibuat.');
    }

    public function generateDetail(int $laporanId): RedirectResponse
    {
        $laporan = DB::table('laporan_pimpinan')->where('id', $laporanId)->first();

        if (! $laporan) {
            return back()->with('error', 'Data laporan tidak ditemukan.');
        }

        $summary = DB::table('produksi_panen')
            ->join('lahan_komoditas', 'lahan_komoditas.id', '=', 'produksi_panen.lahan_komoditas_id')
            ->join('lahan', 'lahan.id', '=', 'lahan_komoditas.lahan_id')
            ->join('desa', 'desa.id', '=', 'lahan.desa_id')
            ->join('kecamatan', 'kecamatan.id', '=', 'desa.kecamatan_id')
            ->leftJoin('petani', 'petani.id', '=', 'lahan.petani_id')
            ->where('produksi_panen.periode_id', $laporan->periode_id)
            ->groupBy('kecamatan.id')
            ->selectRaw('kecamatan.id as kecamatan_id')
            ->selectRaw('COALESCE(SUM(produksi_panen.jumlah_produksi),0) as total_produksi')
            ->selectRaw('COALESCE(SUM(lahan.luas_ha),0) as total_luas')
            ->selectRaw('COUNT(DISTINCT petani.id) as total_petani')
            ->selectRaw('COUNT(DISTINCT lahan.id) as total_lahan')
            ->get();

        foreach ($summary as $item) {
            DB::table('laporan_pimpinan_kecamatan')->updateOrInsert(
                [
                    'laporan_id' => $laporanId,
                    'kecamatan_id' => $item->kecamatan_id,
                ],
                [
                    'total_produksi' => $item->total_produksi,
                    'total_luas' => $item->total_luas,
                    'total_petani' => $item->total_petani,
                    'total_lahan' => $item->total_lahan,
                    'updated_at' => now(),
                    'created_at' => now(),
                ]
            );
        }

        return back()->with('success', 'Detail laporan per kecamatan berhasil digenerate.');
    }
}
