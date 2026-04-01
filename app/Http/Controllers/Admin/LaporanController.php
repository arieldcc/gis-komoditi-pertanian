<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use Barryvdh\DomPDF\Facade\Pdf;
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
        ]);

        $laporanId = DB::table('laporan_pimpinan')->insertGetId([
            ...$data,
            'file_url' => '',
            'generated_by_user_id' => auth()->id(),
            'generated_at' => now(),
            'created_at' => now(),
            'updated_at' => now(),
        ]);

        DB::table('laporan_pimpinan')->where('id', $laporanId)->update([
            'file_url' => route('laporan.preview_pdf', ['laporanId' => $laporanId], true),
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

        $produksiSummary = DB::table('produksi_panen')
            ->join('lahan_komoditas', 'lahan_komoditas.id', '=', 'produksi_panen.lahan_komoditas_id')
            ->join('lahan', 'lahan.id', '=', 'lahan_komoditas.lahan_id')
            ->join('desa', 'desa.id', '=', 'lahan.desa_id')
            ->where('produksi_panen.periode_id', $laporan->periode_id)
            ->groupBy('desa.kecamatan_id')
            ->selectRaw('desa.kecamatan_id')
            ->selectRaw('COALESCE(SUM(produksi_panen.jumlah_produksi),0) as total_produksi');

        $periodAssets = DB::table('produksi_panen')
            ->join('lahan_komoditas', 'lahan_komoditas.id', '=', 'produksi_panen.lahan_komoditas_id')
            ->join('lahan', 'lahan.id', '=', 'lahan_komoditas.lahan_id')
            ->join('desa', 'desa.id', '=', 'lahan.desa_id')
            ->leftJoin('petani', 'petani.id', '=', 'lahan.petani_id')
            ->where('produksi_panen.periode_id', $laporan->periode_id)
            ->distinct()
            ->selectRaw('desa.kecamatan_id, lahan.id as lahan_id, petani.id as petani_id, COALESCE(lahan.luas_ha, 0) as luas_ha');

        $assetSummary = DB::query()
            ->fromSub($periodAssets, 'period_assets')
            ->groupBy('period_assets.kecamatan_id')
            ->selectRaw('period_assets.kecamatan_id')
            ->selectRaw('COALESCE(SUM(period_assets.luas_ha),0) as total_luas')
            ->selectRaw('COUNT(DISTINCT period_assets.petani_id) as total_petani')
            ->selectRaw('COUNT(DISTINCT period_assets.lahan_id) as total_lahan');

        $summary = DB::query()
            ->fromSub($produksiSummary, 'produksi_summary')
            ->leftJoinSub($assetSummary, 'asset_summary', function ($join): void {
                $join->on('asset_summary.kecamatan_id', '=', 'produksi_summary.kecamatan_id');
            })
            ->selectRaw('produksi_summary.kecamatan_id')
            ->selectRaw('produksi_summary.total_produksi')
            ->selectRaw('COALESCE(asset_summary.total_luas,0) as total_luas')
            ->selectRaw('COALESCE(asset_summary.total_petani,0) as total_petani')
            ->selectRaw('COALESCE(asset_summary.total_lahan,0) as total_lahan')
            ->get();

        $kecamatanIds = $summary->pluck('kecamatan_id')->map(fn ($id) => (int) $id)->all();

        $staleDetail = DB::table('laporan_pimpinan_kecamatan')->where('laporan_id', $laporanId);
        if ($kecamatanIds !== []) {
            $staleDetail->whereNotIn('kecamatan_id', $kecamatanIds);
        }
        $staleDetail->delete();

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

    public function previewPdf(int $laporanId)
    {
        $payload = $this->buildPdfPayload($laporanId);

        if (! $payload) {
            abort(404, 'Data laporan tidak ditemukan.');
        }

        $pdf = Pdf::loadView('pimpinan.unduh.laporan_pdf', $payload)
            ->setPaper('a4', 'portrait');

        return $pdf->stream($this->pdfFilename($payload['laporan']));
    }

    private function buildPdfPayload(int $laporanId): ?array
    {
        $laporan = DB::table('laporan_pimpinan')
            ->join('periode_laporan', 'periode_laporan.id', '=', 'laporan_pimpinan.periode_id')
            ->join('users', 'users.id', '=', 'laporan_pimpinan.generated_by_user_id')
            ->where('laporan_pimpinan.id', $laporanId)
            ->select(
                'laporan_pimpinan.id',
                'laporan_pimpinan.jenis_laporan',
                'laporan_pimpinan.generated_at',
                'periode_laporan.bulan',
                'periode_laporan.tahun',
                'periode_laporan.tanggal_mulai',
                'periode_laporan.tanggal_selesai',
                'users.name as generated_by'
            )
            ->first();

        if (! $laporan) {
            return null;
        }

        $summary = DB::table('laporan_pimpinan_kecamatan')
            ->join('kecamatan', 'kecamatan.id', '=', 'laporan_pimpinan_kecamatan.kecamatan_id')
            ->where('laporan_pimpinan_kecamatan.laporan_id', $laporanId)
            ->select(
                'kecamatan.nama_kecamatan',
                'laporan_pimpinan_kecamatan.total_produksi',
                'laporan_pimpinan_kecamatan.total_luas',
                'laporan_pimpinan_kecamatan.total_petani',
                'laporan_pimpinan_kecamatan.total_lahan'
            )
            ->orderByDesc('laporan_pimpinan_kecamatan.total_produksi')
            ->get();

        $laporanPenyuluh = DB::table('kunjungan_monitoring')
            ->join('penugasan_penyuluh', 'penugasan_penyuluh.id', '=', 'kunjungan_monitoring.penugasan_id')
            ->join('penyuluh', 'penyuluh.id', '=', 'penugasan_penyuluh.penyuluh_id')
            ->join('users', 'users.id', '=', 'penyuluh.user_id')
            ->join('lahan', 'lahan.id', '=', 'penugasan_penyuluh.lahan_id')
            ->join('petani', 'petani.id', '=', 'lahan.petani_id')
            ->join('desa', 'desa.id', '=', 'lahan.desa_id')
            ->join('kecamatan', 'kecamatan.id', '=', 'desa.kecamatan_id')
            ->leftJoin('verifikasi_log', function ($join): void {
                $join->on('verifikasi_log.kunjungan_id', '=', 'kunjungan_monitoring.id')
                    ->whereRaw('verifikasi_log.id = (SELECT MAX(v2.id) FROM verifikasi_log v2 WHERE v2.kunjungan_id = kunjungan_monitoring.id)');
            })
            ->where('kunjungan_monitoring.status_verifikasi', 'disetujui')
            ->whereBetween('kunjungan_monitoring.tanggal_kunjungan', [
                $laporan->tanggal_mulai.' 00:00:00',
                $laporan->tanggal_selesai.' 23:59:59',
            ])
            ->select(
                'kunjungan_monitoring.tanggal_kunjungan',
                'kecamatan.nama_kecamatan',
                'users.name as nama_penyuluh',
                'petani.nama_petani',
                'kunjungan_monitoring.kondisi_tanaman',
                'kunjungan_monitoring.catatan_umum',
                'kunjungan_monitoring.rekomendasi',
                'verifikasi_log.catatan_verifikasi'
            )
            ->orderByDesc('kunjungan_monitoring.tanggal_kunjungan')
            ->limit(300)
            ->get();

        $totals = [
            'produksi' => (float) $summary->sum('total_produksi'),
            'luas' => (float) $summary->sum('total_luas'),
            'petani' => (int) $summary->sum('total_petani'),
            'lahan' => (int) $summary->sum('total_lahan'),
            'kunjungan_disetujui' => (int) $laporanPenyuluh->count(),
        ];

        return [
            'laporan' => $laporan,
            'summary' => $summary,
            'laporanPenyuluh' => $laporanPenyuluh,
            'totals' => $totals,
        ];
    }

    private function pdfFilename(object $laporan): string
    {
        return 'laporan-pimpinan-'.sprintf('%02d-%d', (int) $laporan->bulan, (int) $laporan->tahun).'.pdf';
    }
}
