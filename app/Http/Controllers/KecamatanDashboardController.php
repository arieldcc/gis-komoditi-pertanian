<?php

namespace App\Http\Controllers;

use App\Support\DashboardChartSupport;
use Illuminate\Http\JsonResponse;
use Illuminate\Support\Facades\DB;
use Illuminate\View\View;

class KecamatanDashboardController extends Controller
{
    private function managedKecamatanIds(): array
    {
        return DB::table('user_wilayah')
            ->where('user_id', auth()->id())
            ->pluck('kecamatan_id')
            ->map(fn ($id) => (int) $id)
            ->toArray();
    }

    public function index(): View
    {
        return view('dashboards.kecamatan', [
            'dashboardData' => $this->buildDashboardData(),
        ]);
    }

    public function data(): JsonResponse
    {
        return response()->json($this->buildDashboardData());
    }

    private function buildDashboardData(): array
    {
        $kecamatanIds = $this->managedKecamatanIds();

        if (count($kecamatanIds) === 0) {
            return [
                'cards' => [
                    DashboardChartSupport::card('penyuluh_aktif', 'Penyuluh Aktif', 0, ' orang'),
                    DashboardChartSupport::card('laporan_pending', 'Laporan Menunggu/Revisi', 0, ' laporan'),
                    DashboardChartSupport::card('update_hari_ini', 'Update Hari Ini', 0, ' aktivitas'),
                    DashboardChartSupport::card('usulan_perubahan', 'Usulan Perubahan Data', 0, ' usulan'),
                ],
                'pie' => [
                    'title' => 'Status Laporan Penyuluh',
                    'labels' => ['Menunggu', 'Revisi', 'Disetujui', 'Ditolak', 'Draft'],
                    'series' => [0, 0, 0, 0, 0],
                ],
                'bar' => [
                    'title' => 'Produktivitas Penyuluh (Kunjungan)',
                    'categories' => [],
                    'series' => [[
                        'name' => 'Kunjungan',
                        'data' => [],
                    ]],
                ],
                'candlestick' => [
                    'title' => 'Pergerakan Harga Jual (Wilayah Anda)',
                    'series' => [[
                        'name' => 'Harga Jual',
                        'data' => [],
                    ]],
                ],
                'activity' => [],
                'updated_at' => now()->format('d M Y H:i:s'),
            ];
        }

        $penyuluhAktif = DB::table('penyuluh')
            ->join('balai_penyuluh', 'balai_penyuluh.id', '=', 'penyuluh.balai_id')
            ->whereIn('balai_penyuluh.kecamatan_id', $kecamatanIds)
            ->where('penyuluh.is_active', true)
            ->count();

        $menungguVerifikasi = DB::table('kunjungan_monitoring')
            ->join('penugasan_penyuluh', 'penugasan_penyuluh.id', '=', 'kunjungan_monitoring.penugasan_id')
            ->join('lahan', 'lahan.id', '=', 'penugasan_penyuluh.lahan_id')
            ->join('desa', 'desa.id', '=', 'lahan.desa_id')
            ->whereIn('desa.kecamatan_id', $kecamatanIds)
            ->whereIn('kunjungan_monitoring.status_verifikasi', ['draft', 'menunggu', 'revisi'])
            ->count();

        $aktivitasHariIni = DB::table('kunjungan_monitoring')
            ->join('penugasan_penyuluh', 'penugasan_penyuluh.id', '=', 'kunjungan_monitoring.penugasan_id')
            ->join('lahan', 'lahan.id', '=', 'penugasan_penyuluh.lahan_id')
            ->join('desa', 'desa.id', '=', 'lahan.desa_id')
            ->whereIn('desa.kecamatan_id', $kecamatanIds)
            ->whereDate('kunjungan_monitoring.created_at', today())
            ->count();

        $usulanPerubahan = DB::table('usulan_perubahan_data')
            ->join('penugasan_penyuluh', 'penugasan_penyuluh.id', '=', 'usulan_perubahan_data.penugasan_id')
            ->join('lahan', 'lahan.id', '=', 'penugasan_penyuluh.lahan_id')
            ->join('desa', 'desa.id', '=', 'lahan.desa_id')
            ->whereIn('desa.kecamatan_id', $kecamatanIds)
            ->where('usulan_perubahan_data.status', 'menunggu')
            ->count();

        $cards = [
            DashboardChartSupport::card('penyuluh_aktif', 'Penyuluh Aktif', $penyuluhAktif, ' orang'),
            DashboardChartSupport::card('laporan_pending', 'Laporan Menunggu/Revisi', $menungguVerifikasi, ' laporan'),
            DashboardChartSupport::card('update_hari_ini', 'Update Hari Ini', $aktivitasHariIni, ' aktivitas'),
            DashboardChartSupport::card('usulan_perubahan', 'Usulan Perubahan Data', $usulanPerubahan, ' usulan'),
        ];

        $statusRows = DB::table('kunjungan_monitoring')
            ->join('penugasan_penyuluh', 'penugasan_penyuluh.id', '=', 'kunjungan_monitoring.penugasan_id')
            ->join('lahan', 'lahan.id', '=', 'penugasan_penyuluh.lahan_id')
            ->join('desa', 'desa.id', '=', 'lahan.desa_id')
            ->whereIn('desa.kecamatan_id', $kecamatanIds)
            ->groupBy('kunjungan_monitoring.status_verifikasi')
            ->select('kunjungan_monitoring.status_verifikasi', DB::raw('COUNT(*) as total'))
            ->get()
            ->keyBy('status_verifikasi');

        $statusLabels = ['menunggu', 'revisi', 'disetujui', 'ditolak', 'draft'];
        $statusSeries = collect($statusLabels)->map(fn ($status) => (int) ($statusRows[$status]->total ?? 0))->all();

        $penyuluhRows = DB::table('penyuluh')
            ->join('users', 'users.id', '=', 'penyuluh.user_id')
            ->join('penugasan_penyuluh', 'penugasan_penyuluh.penyuluh_id', '=', 'penyuluh.id')
            ->leftJoin('kunjungan_monitoring', 'kunjungan_monitoring.penugasan_id', '=', 'penugasan_penyuluh.id')
            ->join('lahan', 'lahan.id', '=', 'penugasan_penyuluh.lahan_id')
            ->join('desa', 'desa.id', '=', 'lahan.desa_id')
            ->whereIn('desa.kecamatan_id', $kecamatanIds)
            ->groupBy('penyuluh.id', 'users.name')
            ->select('users.name', DB::raw('COUNT(kunjungan_monitoring.id) as total_kunjungan'))
            ->orderByDesc('total_kunjungan')
            ->limit(10)
            ->get();

        $hargaRows = DB::table('produksi_panen')
            ->join('lahan_komoditas', 'lahan_komoditas.id', '=', 'produksi_panen.lahan_komoditas_id')
            ->join('lahan', 'lahan.id', '=', 'lahan_komoditas.lahan_id')
            ->join('desa', 'desa.id', '=', 'lahan.desa_id')
            ->whereIn('desa.kecamatan_id', $kecamatanIds)
            ->whereNotNull('produksi_panen.harga_jual')
            ->whereDate('produksi_panen.tanggal_panen', '>=', now()->subDays(90)->toDateString())
            ->select('produksi_panen.id', 'produksi_panen.tanggal_panen', 'produksi_panen.harga_jual')
            ->orderBy('produksi_panen.tanggal_panen')
            ->orderBy('produksi_panen.id')
            ->get();

        $aktivitas = DB::table('kunjungan_monitoring')
            ->join('penugasan_penyuluh', 'penugasan_penyuluh.id', '=', 'kunjungan_monitoring.penugasan_id')
            ->join('penyuluh', 'penyuluh.id', '=', 'penugasan_penyuluh.penyuluh_id')
            ->join('users as up', 'up.id', '=', 'penyuluh.user_id')
            ->join('lahan', 'lahan.id', '=', 'penugasan_penyuluh.lahan_id')
            ->join('petani', 'petani.id', '=', 'lahan.petani_id')
            ->join('desa', 'desa.id', '=', 'lahan.desa_id')
            ->whereIn('desa.kecamatan_id', $kecamatanIds)
            ->select(
                'kunjungan_monitoring.status_verifikasi',
                'kunjungan_monitoring.tanggal_kunjungan',
                'petani.nama_petani',
                'up.name as nama_penyuluh',
                'desa.nama_desa'
            )
            ->orderByDesc('kunjungan_monitoring.tanggal_kunjungan')
            ->limit(8)
            ->get()
            ->map(fn ($row) => [
                'title' => $row->nama_petani,
                'subtitle' => $row->nama_desa.' • '.$row->nama_penyuluh,
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
                'title' => 'Status Laporan Penyuluh',
                'labels' => ['Menunggu', 'Revisi', 'Disetujui', 'Ditolak', 'Draft'],
                'series' => $statusSeries,
            ],
            'bar' => [
                'title' => 'Produktivitas Penyuluh (Jumlah Kunjungan)',
                'categories' => $penyuluhRows->pluck('name')->all(),
                'series' => [[
                    'name' => 'Kunjungan',
                    'data' => $penyuluhRows->pluck('total_kunjungan')->map(fn ($x) => (int) $x)->all(),
                ]],
            ],
            'candlestick' => [
                'title' => 'Pergerakan Harga Jual (Wilayah Anda)',
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
