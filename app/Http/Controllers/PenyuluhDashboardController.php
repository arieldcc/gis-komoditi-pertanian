<?php

namespace App\Http\Controllers;

use App\Support\DashboardChartSupport;
use Illuminate\Http\JsonResponse;
use Illuminate\Support\Facades\DB;
use Illuminate\View\View;

class PenyuluhDashboardController extends Controller
{
    private function penyuluhId(): ?int
    {
        $id = DB::table('penyuluh')->where('user_id', auth()->id())->value('id');

        return $id ? (int) $id : null;
    }

    public function index(): View
    {
        return view('dashboards.penyuluh', [
            'dashboardData' => $this->buildDashboardData(),
        ]);
    }

    public function data(): JsonResponse
    {
        return response()->json($this->buildDashboardData());
    }

    private function buildDashboardData(): array
    {
        $penyuluhId = $this->penyuluhId();

        if (! $penyuluhId) {
            return [
                'cards' => [
                    DashboardChartSupport::card('petani_binaan', 'Petani Binaan Aktif', 0, ' petani'),
                    DashboardChartSupport::card('kunjungan_minggu', 'Kunjungan Minggu Ini', 0, ' kunjungan'),
                    DashboardChartSupport::card('laporan_menunggu', 'Laporan Menunggu Respon', 0, ' laporan'),
                    DashboardChartSupport::card('laporan_revisi', 'Laporan Revisi', 0, ' laporan'),
                ],
                'pie' => [
                    'title' => 'Status Laporan Kunjungan Anda',
                    'labels' => ['Menunggu', 'Revisi', 'Disetujui', 'Ditolak', 'Draft'],
                    'series' => [0, 0, 0, 0, 0],
                ],
                'bar' => [
                    'title' => 'Tren Kunjungan 8 Minggu Terakhir',
                    'categories' => [],
                    'series' => [[
                        'name' => 'Kunjungan',
                        'data' => [],
                    ]],
                ],
                'candlestick' => [
                    'title' => 'Pergerakan Harga Jual Komoditas Binaan',
                    'series' => [[
                        'name' => 'Harga Jual',
                        'data' => [],
                    ]],
                ],
                'activity' => [],
                'updated_at' => now()->format('d M Y H:i:s'),
            ];
        }

        $petaniBinaan = (int) DB::table('penugasan_penyuluh')
            ->join('lahan', 'lahan.id', '=', 'penugasan_penyuluh.lahan_id')
            ->where('penugasan_penyuluh.penyuluh_id', $penyuluhId)
            ->where('penugasan_penyuluh.status_penugasan', 'aktif')
            ->selectRaw('COUNT(DISTINCT lahan.petani_id) as total')
            ->value('total');

        $kunjunganMinggu = DB::table('kunjungan_monitoring')
            ->join('penugasan_penyuluh', 'penugasan_penyuluh.id', '=', 'kunjungan_monitoring.penugasan_id')
            ->where('penugasan_penyuluh.penyuluh_id', $penyuluhId)
            ->whereBetween('kunjungan_monitoring.tanggal_kunjungan', [now()->startOfWeek(), now()->endOfWeek()])
            ->count();

        $laporanMenunggu = DB::table('kunjungan_monitoring')
            ->join('penugasan_penyuluh', 'penugasan_penyuluh.id', '=', 'kunjungan_monitoring.penugasan_id')
            ->where('penugasan_penyuluh.penyuluh_id', $penyuluhId)
            ->where('kunjungan_monitoring.status_verifikasi', 'menunggu')
            ->count();

        $laporanRevisi = DB::table('kunjungan_monitoring')
            ->join('penugasan_penyuluh', 'penugasan_penyuluh.id', '=', 'kunjungan_monitoring.penugasan_id')
            ->where('penugasan_penyuluh.penyuluh_id', $penyuluhId)
            ->where('kunjungan_monitoring.status_verifikasi', 'revisi')
            ->count();

        $cards = [
            DashboardChartSupport::card('petani_binaan', 'Petani Binaan Aktif', $petaniBinaan, ' petani'),
            DashboardChartSupport::card('kunjungan_minggu', 'Kunjungan Minggu Ini', $kunjunganMinggu, ' kunjungan'),
            DashboardChartSupport::card('laporan_menunggu', 'Laporan Menunggu Respon', $laporanMenunggu, ' laporan'),
            DashboardChartSupport::card('laporan_revisi', 'Laporan Revisi', $laporanRevisi, ' laporan'),
        ];

        $statusRows = DB::table('kunjungan_monitoring')
            ->join('penugasan_penyuluh', 'penugasan_penyuluh.id', '=', 'kunjungan_monitoring.penugasan_id')
            ->where('penugasan_penyuluh.penyuluh_id', $penyuluhId)
            ->groupBy('kunjungan_monitoring.status_verifikasi')
            ->select('kunjungan_monitoring.status_verifikasi', DB::raw('COUNT(*) as total'))
            ->get()
            ->keyBy('status_verifikasi');

        $statusLabels = ['menunggu', 'revisi', 'disetujui', 'ditolak', 'draft'];
        $statusSeries = collect($statusLabels)->map(fn ($status) => (int) ($statusRows[$status]->total ?? 0))->all();

        $trenKunjungan = DB::table('kunjungan_monitoring')
            ->join('penugasan_penyuluh', 'penugasan_penyuluh.id', '=', 'kunjungan_monitoring.penugasan_id')
            ->where('penugasan_penyuluh.penyuluh_id', $penyuluhId)
            ->whereDate('kunjungan_monitoring.tanggal_kunjungan', '>=', now()->subWeeks(8)->toDateString())
            ->groupBy(DB::raw('YEARWEEK(kunjungan_monitoring.tanggal_kunjungan, 1)'))
            ->selectRaw('YEARWEEK(kunjungan_monitoring.tanggal_kunjungan, 1) as minggu_ke')
            ->selectRaw('MIN(DATE(kunjungan_monitoring.tanggal_kunjungan)) as awal_minggu')
            ->selectRaw('COUNT(*) as total_kunjungan')
            ->orderBy('minggu_ke')
            ->get();

        $hargaRows = DB::table('produksi_panen')
            ->join('kunjungan_monitoring', 'kunjungan_monitoring.id', '=', 'produksi_panen.kunjungan_id')
            ->join('penugasan_penyuluh', 'penugasan_penyuluh.id', '=', 'kunjungan_monitoring.penugasan_id')
            ->where('penugasan_penyuluh.penyuluh_id', $penyuluhId)
            ->whereNotNull('produksi_panen.harga_jual')
            ->whereDate('produksi_panen.tanggal_panen', '>=', now()->subDays(90)->toDateString())
            ->select('produksi_panen.id', 'produksi_panen.tanggal_panen', 'produksi_panen.harga_jual')
            ->orderBy('produksi_panen.tanggal_panen')
            ->orderBy('produksi_panen.id')
            ->get();

        $aktivitas = DB::table('kunjungan_monitoring')
            ->join('penugasan_penyuluh', 'penugasan_penyuluh.id', '=', 'kunjungan_monitoring.penugasan_id')
            ->join('lahan', 'lahan.id', '=', 'penugasan_penyuluh.lahan_id')
            ->join('petani', 'petani.id', '=', 'lahan.petani_id')
            ->join('desa', 'desa.id', '=', 'lahan.desa_id')
            ->where('penugasan_penyuluh.penyuluh_id', $penyuluhId)
            ->select(
                'petani.nama_petani',
                'desa.nama_desa',
                'kunjungan_monitoring.status_verifikasi',
                'kunjungan_monitoring.tanggal_kunjungan'
            )
            ->orderByDesc('kunjungan_monitoring.tanggal_kunjungan')
            ->limit(8)
            ->get()
            ->map(fn ($row) => [
                'title' => $row->nama_petani,
                'subtitle' => $row->nama_desa,
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
                'title' => 'Status Laporan Kunjungan Anda',
                'labels' => ['Menunggu', 'Revisi', 'Disetujui', 'Ditolak', 'Draft'],
                'series' => $statusSeries,
            ],
            'bar' => [
                'title' => 'Tren Kunjungan 8 Minggu Terakhir',
                'categories' => $trenKunjungan->map(fn ($row) => date('d M', strtotime((string) $row->awal_minggu)))->all(),
                'series' => [[
                    'name' => 'Kunjungan',
                    'data' => $trenKunjungan->pluck('total_kunjungan')->map(fn ($x) => (int) $x)->all(),
                ]],
            ],
            'candlestick' => [
                'title' => 'Pergerakan Harga Jual Komoditas Binaan',
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
