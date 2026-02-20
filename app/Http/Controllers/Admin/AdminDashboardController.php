<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\User;
use App\Support\DashboardChartSupport;
use Illuminate\Http\JsonResponse;
use Illuminate\Support\Facades\DB;
use Illuminate\View\View;

class AdminDashboardController extends Controller
{
    public function index(): View
    {
        return view('dashboards.admin', [
            'dashboardData' => $this->buildDashboardData(),
        ]);
    }

    public function data(): JsonResponse
    {
        return response()->json($this->buildDashboardData());
    }

    private function buildDashboardData(): array
    {
        $totalUsers = User::count();
        $totalKecamatan = (int) DB::table('kecamatan')->count();
        $totalPenyuluh = User::where('role', User::ROLE_PENYULUH)->count();
        $laporanMenunggu = (int) DB::table('kunjungan_monitoring')
            ->whereIn('status_verifikasi', ['menunggu', 'revisi'])
            ->count();
        $laporanHariIni = (int) DB::table('kunjungan_monitoring')
            ->whereDate('tanggal_kunjungan', today())
            ->count();

        $cards = [
            DashboardChartSupport::card('total_user', 'Total User Sistem', $totalUsers, ' akun'),
            DashboardChartSupport::card('total_kecamatan', 'Total Kecamatan', $totalKecamatan, ' kecamatan'),
            DashboardChartSupport::card('total_penyuluh', 'Total Penyuluh', $totalPenyuluh, ' orang'),
            DashboardChartSupport::card('laporan_menunggu', 'Laporan Menunggu/Revisi', $laporanMenunggu, ' laporan'),
            DashboardChartSupport::card('laporan_hari_ini', 'Laporan Masuk Hari Ini', $laporanHariIni, ' laporan'),
        ];

        $roleRows = User::query()
            ->select('role', DB::raw('COUNT(*) as total'))
            ->groupBy('role')
            ->get()
            ->keyBy('role');

        $pieLabels = ['Admin Dinas', 'Admin Kecamatan', 'Penyuluh', 'Pimpinan Dinas'];
        $pieSeries = [
            (int) ($roleRows[User::ROLE_ADMIN_DINAS]->total ?? 0),
            (int) ($roleRows[User::ROLE_ADMIN_KECAMATAN]->total ?? 0),
            (int) ($roleRows[User::ROLE_PENYULUH]->total ?? 0),
            (int) ($roleRows[User::ROLE_PIMPINAN_DINAS]->total ?? 0),
        ];

        $laporanKecamatanRows = DB::table('kecamatan')
            ->leftJoin('desa', 'desa.kecamatan_id', '=', 'kecamatan.id')
            ->leftJoin('lahan', 'lahan.desa_id', '=', 'desa.id')
            ->leftJoin('penugasan_penyuluh', 'penugasan_penyuluh.lahan_id', '=', 'lahan.id')
            ->leftJoin('kunjungan_monitoring', 'kunjungan_monitoring.penugasan_id', '=', 'penugasan_penyuluh.id')
            ->groupBy('kecamatan.id', 'kecamatan.nama_kecamatan')
            ->select(
                'kecamatan.nama_kecamatan',
                DB::raw('COUNT(kunjungan_monitoring.id) as total_laporan')
            )
            ->orderByDesc('total_laporan')
            ->limit(10)
            ->get();

        $hargaRows = DB::table('produksi_panen')
            ->select('id', 'tanggal_panen', 'harga_jual')
            ->whereNotNull('harga_jual')
            ->whereDate('tanggal_panen', '>=', now()->subDays(90)->toDateString())
            ->orderBy('tanggal_panen')
            ->orderBy('id')
            ->get();

        $aktivitas = DB::table('kunjungan_monitoring')
            ->join('penugasan_penyuluh', 'penugasan_penyuluh.id', '=', 'kunjungan_monitoring.penugasan_id')
            ->join('penyuluh', 'penyuluh.id', '=', 'penugasan_penyuluh.penyuluh_id')
            ->join('users as u', 'u.id', '=', 'penyuluh.user_id')
            ->join('lahan', 'lahan.id', '=', 'penugasan_penyuluh.lahan_id')
            ->join('petani', 'petani.id', '=', 'lahan.petani_id')
            ->join('desa', 'desa.id', '=', 'lahan.desa_id')
            ->join('kecamatan', 'kecamatan.id', '=', 'desa.kecamatan_id')
            ->select(
                'kunjungan_monitoring.id',
                'kunjungan_monitoring.status_verifikasi',
                'kunjungan_monitoring.tanggal_kunjungan',
                'petani.nama_petani',
                'u.name as nama_penyuluh',
                'kecamatan.nama_kecamatan'
            )
            ->orderByDesc('kunjungan_monitoring.tanggal_kunjungan')
            ->limit(8)
            ->get()
            ->map(fn ($row) => [
                'title' => $row->nama_petani,
                'subtitle' => $row->nama_kecamatan.' • '.$row->nama_penyuluh,
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
                'title' => 'Distribusi Akun per Role',
                'labels' => $pieLabels,
                'series' => $pieSeries,
            ],
            'bar' => [
                'title' => 'Top Kecamatan Berdasarkan Laporan Penyuluh',
                'categories' => $laporanKecamatanRows->pluck('nama_kecamatan')->all(),
                'series' => [[
                    'name' => 'Jumlah Laporan',
                    'data' => $laporanKecamatanRows->pluck('total_laporan')->map(fn ($x) => (int) $x)->all(),
                ]],
            ],
            'candlestick' => [
                'title' => 'Pergerakan Harga Jual Komoditas (90 Hari)',
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
