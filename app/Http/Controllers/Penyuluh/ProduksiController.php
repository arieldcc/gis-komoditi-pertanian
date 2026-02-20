<?php

namespace App\Http\Controllers\Penyuluh;

use App\Http\Controllers\Controller;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\View\View;

class ProduksiController extends Controller
{
    private function penyuluhId(): ?int
    {
        return DB::table('penyuluh')->where('user_id', auth()->id())->value('id');
    }

    public function index(): View
    {
        $penyuluhId = $this->penyuluhId();

        $lahanKomoditas = collect();
        $periode = DB::table('periode_laporan')->orderByDesc('tahun')->orderByDesc('bulan')->get();
        $kunjungan = collect();
        $produksi = collect();

        if ($penyuluhId) {
            $lahanKomoditas = DB::table('lahan_komoditas')
                ->join('lahan', 'lahan.id', '=', 'lahan_komoditas.lahan_id')
                ->join('petani', 'petani.id', '=', 'lahan.petani_id')
                ->join('komoditas', 'komoditas.id', '=', 'lahan_komoditas.komoditas_id')
                ->join('penugasan_penyuluh', 'penugasan_penyuluh.lahan_id', '=', 'lahan.id')
                ->where('penugasan_penyuluh.penyuluh_id', $penyuluhId)
                ->select('lahan_komoditas.id', 'petani.nama_petani', 'komoditas.nama_komoditas', 'lahan_komoditas.tahun_tanam')
                ->orderBy('petani.nama_petani')
                ->get();

            $kunjungan = DB::table('kunjungan_monitoring')
                ->join('penugasan_penyuluh', 'penugasan_penyuluh.id', '=', 'kunjungan_monitoring.penugasan_id')
                ->join('lahan', 'lahan.id', '=', 'penugasan_penyuluh.lahan_id')
                ->join('petani', 'petani.id', '=', 'lahan.petani_id')
                ->where('penugasan_penyuluh.penyuluh_id', $penyuluhId)
                ->select('kunjungan_monitoring.id', 'petani.nama_petani', 'kunjungan_monitoring.tanggal_kunjungan')
                ->orderByDesc('kunjungan_monitoring.tanggal_kunjungan')
                ->get();

            $produksi = DB::table('produksi_panen')
                ->join('lahan_komoditas', 'lahan_komoditas.id', '=', 'produksi_panen.lahan_komoditas_id')
                ->join('lahan', 'lahan.id', '=', 'lahan_komoditas.lahan_id')
                ->join('petani', 'petani.id', '=', 'lahan.petani_id')
                ->join('komoditas', 'komoditas.id', '=', 'lahan_komoditas.komoditas_id')
                ->join('periode_laporan', 'periode_laporan.id', '=', 'produksi_panen.periode_id')
                ->leftJoin('kunjungan_monitoring', 'kunjungan_monitoring.id', '=', 'produksi_panen.kunjungan_id')
                ->leftJoin('penugasan_penyuluh', 'penugasan_penyuluh.id', '=', 'kunjungan_monitoring.penugasan_id')
                ->where(function ($q) use ($penyuluhId): void {
                    $q->where('penugasan_penyuluh.penyuluh_id', $penyuluhId)
                        ->orWhereNull('produksi_panen.kunjungan_id');
                })
                ->select(
                    'produksi_panen.*',
                    'petani.nama_petani',
                    'komoditas.nama_komoditas',
                    'periode_laporan.bulan',
                    'periode_laporan.tahun'
                )
                ->orderByDesc('produksi_panen.tanggal_panen')
                ->get();
        }

        return view('penyuluh.produksi.index', compact('penyuluhId', 'lahanKomoditas', 'periode', 'kunjungan', 'produksi'));
    }

    public function store(Request $request): RedirectResponse
    {
        $penyuluhId = $this->penyuluhId();
        if (! $penyuluhId) {
            return back()->with('error', 'Profil penyuluh tidak tersedia.');
        }

        $data = $request->validate([
            'lahan_komoditas_id' => ['required', 'exists:lahan_komoditas,id'],
            'periode_id' => ['required', 'exists:periode_laporan,id'],
            'kunjungan_id' => ['nullable', 'exists:kunjungan_monitoring,id'],
            'tanggal_panen' => ['required', 'date'],
            'jumlah_produksi' => ['required', 'numeric', 'min:0'],
            'produktivitas_kg_ha' => ['nullable', 'numeric', 'min:0'],
            'harga_jual' => ['nullable', 'numeric', 'min:0'],
            'catatan' => ['nullable', 'string'],
        ]);

        DB::table('produksi_panen')->insert([
            ...$data,
            'created_at' => now(),
            'updated_at' => now(),
        ]);

        return back()->with('success', 'Data produksi panen berhasil ditambahkan.');
    }

    public function update(Request $request, int $id): RedirectResponse
    {
        $data = $request->validate([
            'tanggal_panen' => ['required', 'date'],
            'jumlah_produksi' => ['required', 'numeric', 'min:0'],
            'produktivitas_kg_ha' => ['nullable', 'numeric', 'min:0'],
            'harga_jual' => ['nullable', 'numeric', 'min:0'],
            'catatan' => ['nullable', 'string'],
        ]);

        DB::table('produksi_panen')->where('id', $id)->update([
            ...$data,
            'updated_at' => now(),
        ]);

        return back()->with('success', 'Data produksi panen berhasil diperbarui.');
    }

    public function destroy(int $id): RedirectResponse
    {
        DB::table('produksi_panen')->where('id', $id)->delete();

        return back()->with('success', 'Data produksi panen berhasil dihapus.');
    }
}
