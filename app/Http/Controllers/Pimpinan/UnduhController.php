<?php

namespace App\Http\Controllers\Pimpinan;

use App\Http\Controllers\Controller;
use Barryvdh\DomPDF\Facade\Pdf;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Symfony\Component\HttpFoundation\StreamedResponse;
use Illuminate\View\View;

class UnduhController extends Controller
{
    public function index(): View
    {
        $laporan = DB::table('laporan_pimpinan')
            ->join('periode_laporan', 'periode_laporan.id', '=', 'laporan_pimpinan.periode_id')
            ->select('laporan_pimpinan.id', 'laporan_pimpinan.jenis_laporan', 'laporan_pimpinan.file_url', 'periode_laporan.bulan', 'periode_laporan.tahun')
            ->orderByDesc('laporan_pimpinan.generated_at')
            ->get();

        return view('pimpinan.unduh.index', compact('laporan'));
    }

    public function produksiCsv(): StreamedResponse
    {
        $rows = DB::table('produksi_panen')
            ->join('lahan_komoditas', 'lahan_komoditas.id', '=', 'produksi_panen.lahan_komoditas_id')
            ->join('komoditas', 'komoditas.id', '=', 'lahan_komoditas.komoditas_id')
            ->join('periode_laporan', 'periode_laporan.id', '=', 'produksi_panen.periode_id')
            ->select(
                'produksi_panen.tanggal_panen',
                'komoditas.nama_komoditas',
                'produksi_panen.jumlah_produksi',
                'produksi_panen.produktivitas_kg_ha',
                'produksi_panen.harga_jual',
                'periode_laporan.bulan',
                'periode_laporan.tahun'
            )
            ->orderByDesc('produksi_panen.tanggal_panen')
            ->get();

        return response()->streamDownload(function () use ($rows): void {
            $handle = fopen('php://output', 'w');
            fputcsv($handle, ['Tanggal Panen', 'Komoditas', 'Jumlah Produksi', 'Produktivitas', 'Harga Jual', 'Bulan', 'Tahun']);
            foreach ($rows as $row) {
                fputcsv($handle, [
                    $row->tanggal_panen,
                    $row->nama_komoditas,
                    $row->jumlah_produksi,
                    $row->produktivitas_kg_ha,
                    $row->harga_jual,
                    $row->bulan,
                    $row->tahun,
                ]);
            }
            fclose($handle);
        }, 'produksi-komoditas.csv', ['Content-Type' => 'text/csv']);
    }

    public function laporanCsv(Request $request): StreamedResponse
    {
        $laporanId = (int) $request->integer('laporan_id');

        $rows = DB::table('laporan_pimpinan_kecamatan')
            ->join('kecamatan', 'kecamatan.id', '=', 'laporan_pimpinan_kecamatan.kecamatan_id')
            ->where('laporan_pimpinan_kecamatan.laporan_id', $laporanId)
            ->select(
                'kecamatan.nama_kecamatan',
                'laporan_pimpinan_kecamatan.total_produksi',
                'laporan_pimpinan_kecamatan.total_luas',
                'laporan_pimpinan_kecamatan.total_petani',
                'laporan_pimpinan_kecamatan.total_lahan'
            )
            ->orderBy('kecamatan.nama_kecamatan')
            ->get();

        return response()->streamDownload(function () use ($rows): void {
            $handle = fopen('php://output', 'w');
            fputcsv($handle, ['Kecamatan', 'Total Produksi', 'Total Luas', 'Total Petani', 'Total Lahan']);
            foreach ($rows as $row) {
                fputcsv($handle, [
                    $row->nama_kecamatan,
                    $row->total_produksi,
                    $row->total_luas,
                    $row->total_petani,
                    $row->total_lahan,
                ]);
            }
            fclose($handle);
        }, 'laporan-kecamatan.csv', ['Content-Type' => 'text/csv']);
    }

    public function laporanPdf(Request $request)
    {
        $laporanId = (int) $request->integer('laporan_id');
        if ($laporanId < 1) {
            return back()->with('error', 'Pilih laporan terlebih dahulu untuk diunduh ke PDF.');
        }

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
            return back()->with('error', 'Data laporan tidak ditemukan.');
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

        $pdf = Pdf::loadView('pimpinan.unduh.laporan_pdf', [
            'laporan' => $laporan,
            'summary' => $summary,
            'laporanPenyuluh' => $laporanPenyuluh,
            'totals' => $totals,
        ])->setPaper('a4', 'portrait');

        $filename = 'laporan-pimpinan-'.sprintf('%02d-%d', (int) $laporan->bulan, (int) $laporan->tahun).'.pdf';

        return $pdf->download($filename);
    }
}
