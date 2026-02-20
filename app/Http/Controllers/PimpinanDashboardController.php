<?php

namespace App\Http\Controllers;

use App\Support\DashboardChartSupport;
use Illuminate\Http\JsonResponse;
use Illuminate\Support\Facades\DB;
use Illuminate\View\View;

class PimpinanDashboardController extends Controller
{
    public function index(): View
    {
        return view('dashboards.pimpinan', [
            'dashboardData' => $this->buildDashboardData(),
        ]);
    }

    public function data(): JsonResponse
    {
        return response()->json($this->buildDashboardData());
    }

    private function buildDashboardData(): array
    {
        $kecamatanTerlapor = DB::table('laporan_pimpinan_kecamatan')
            ->distinct('kecamatan_id')
            ->count('kecamatan_id');

        $laporanTerverifikasi = DB::table('kunjungan_monitoring')
            ->where('status_verifikasi', 'disetujui')
            ->count();

        $komoditasDominan = DB::table('produksi_panen')
            ->join('lahan_komoditas', 'lahan_komoditas.id', '=', 'produksi_panen.lahan_komoditas_id')
            ->join('komoditas', 'komoditas.id', '=', 'lahan_komoditas.komoditas_id')
            ->groupBy('komoditas.id', 'komoditas.nama_komoditas')
            ->select('komoditas.nama_komoditas', DB::raw('SUM(produksi_panen.jumlah_produksi) as total'))
            ->orderByDesc('total')
            ->first()?->nama_komoditas;

        $totalProduksi = (float) DB::table('produksi_panen')->sum('jumlah_produksi');

        $cards = [
            DashboardChartSupport::card('kecamatan_terlapor', 'Kecamatan Terlapor', $kecamatanTerlapor, ' kecamatan'),
            DashboardChartSupport::card('laporan_terverifikasi', 'Laporan Terverifikasi', $laporanTerverifikasi, ' laporan'),
            DashboardChartSupport::card('total_produksi', 'Total Produksi', round($totalProduksi, 2), ' kg'),
            DashboardChartSupport::card('komoditas_dominan', 'Komoditas Dominan', $komoditasDominan ?: '-'),
        ];

        $statusRows = DB::table('kunjungan_monitoring')
            ->groupBy('status_verifikasi')
            ->select('status_verifikasi', DB::raw('COUNT(*) as total'))
            ->get()
            ->keyBy('status_verifikasi');

        $statusLabels = ['menunggu', 'revisi', 'disetujui', 'ditolak', 'draft'];
        $statusSeries = collect($statusLabels)->map(fn ($status) => (int) ($statusRows[$status]->total ?? 0))->all();

        $produksiKomoditasRows = DB::table('produksi_panen')
            ->join('lahan_komoditas', 'lahan_komoditas.id', '=', 'produksi_panen.lahan_komoditas_id')
            ->join('komoditas', 'komoditas.id', '=', 'lahan_komoditas.komoditas_id')
            ->groupBy('komoditas.id', 'komoditas.nama_komoditas')
            ->select('komoditas.nama_komoditas', DB::raw('COALESCE(SUM(produksi_panen.jumlah_produksi),0) as total_produksi'))
            ->orderByDesc('total_produksi')
            ->limit(10)
            ->get();

        $hargaRows = DB::table('produksi_panen')
            ->select('id', 'tanggal_panen', 'harga_jual')
            ->whereNotNull('harga_jual')
            ->whereDate('tanggal_panen', '>=', now()->subDays(120)->toDateString())
            ->orderBy('tanggal_panen')
            ->orderBy('id')
            ->get();

        $aktivitas = DB::table('kunjungan_monitoring')
            ->join('penugasan_penyuluh', 'penugasan_penyuluh.id', '=', 'kunjungan_monitoring.penugasan_id')
            ->join('lahan', 'lahan.id', '=', 'penugasan_penyuluh.lahan_id')
            ->join('petani', 'petani.id', '=', 'lahan.petani_id')
            ->join('desa', 'desa.id', '=', 'lahan.desa_id')
            ->join('kecamatan', 'kecamatan.id', '=', 'desa.kecamatan_id')
            ->where('kunjungan_monitoring.status_verifikasi', 'disetujui')
            ->select(
                'petani.nama_petani',
                'kecamatan.nama_kecamatan',
                'kunjungan_monitoring.status_verifikasi',
                'kunjungan_monitoring.tanggal_kunjungan'
            )
            ->orderByDesc('kunjungan_monitoring.tanggal_kunjungan')
            ->limit(8)
            ->get()
            ->map(fn ($row) => [
                'title' => $row->nama_petani,
                'subtitle' => $row->nama_kecamatan,
                'status' => $row->status_verifikasi,
                'time' => $row->tanggal_kunjungan
                    ? date('d M Y H:i', strtotime((string) $row->tanggal_kunjungan))
                    : '-',
            ])
            ->values()
            ->all();

        return [
            'cards' => $cards,
            'pie' => [
                'title' => 'Distribusi Status Laporan',
                'labels' => ['Menunggu', 'Revisi', 'Disetujui', 'Ditolak', 'Draft'],
                'series' => $statusSeries,
            ],
            'bar' => [
                'title' => 'Produksi per Komoditas',
                'categories' => $produksiKomoditasRows->pluck('nama_komoditas')->all(),
                'series' => [[
                    'name' => 'Total Produksi (kg)',
                    'data' => $produksiKomoditasRows->pluck('total_produksi')->map(fn ($x) => round((float) $x, 2))->all(),
                ]],
            ],
            'candlestick' => [
                'title' => 'Pergerakan Harga Jual (120 Hari)',
                'series' => [[
                    'name' => 'Harga Jual',
                    'data' => DashboardChartSupport::candlestickFromRows($hargaRows),
                ]],
            ],
            'activity' => $aktivitas,
            'updated_at' => now()->format('d M Y H:i:s'),
        ];
    }
}
