<?php

use App\Http\Controllers\Admin\AdminDashboardController;
use App\Http\Controllers\Admin\BalaiPenyuluhController;
use App\Http\Controllers\Admin\KelompokTaniController as AdminKelompokTaniController;
use App\Http\Controllers\Admin\KomoditasController;
use App\Http\Controllers\Admin\LaporanController as AdminLaporanController;
use App\Http\Controllers\Admin\MapStyleController;
use App\Http\Controllers\Admin\MonitoringController;
use App\Http\Controllers\Admin\UserManagementController;
use App\Http\Controllers\Admin\WilayahController;
use App\Http\Controllers\DashboardController;
use App\Http\Controllers\LandingController;
use App\Http\Controllers\Kecamatan\PetaniLahanController;
use App\Http\Controllers\Kecamatan\KelompokTaniController as KecamatanKelompokTaniController;
use App\Http\Controllers\Kecamatan\PenyuluhController as KecamatanPenyuluhController;
use App\Http\Controllers\Kecamatan\RekapController;
use App\Http\Controllers\Kecamatan\VerifikasiController;
use App\Http\Controllers\KecamatanDashboardController;
use App\Http\Controllers\PanelDataTableController;
use App\Http\Controllers\Penyuluh\KendalaKebutuhanController;
use App\Http\Controllers\Penyuluh\KunjunganController;
use App\Http\Controllers\Penyuluh\PetaniBinaanController;
use App\Http\Controllers\Penyuluh\ProduksiController;
use App\Http\Controllers\Penyuluh\RiwayatController;
use App\Http\Controllers\PenyuluhDashboardController;
use App\Http\Controllers\Pimpinan\AnalitikController;
use App\Http\Controllers\Pimpinan\LaporanController as PimpinanLaporanController;
use App\Http\Controllers\Pimpinan\PetaController;
use App\Http\Controllers\Pimpinan\UnduhController;
use App\Http\Controllers\PimpinanDashboardController;
use App\Http\Controllers\ProfileController;
use Illuminate\Support\Facades\Route;

Route::get('/', [LandingController::class, 'index']);

Route::get('/dashboard', [DashboardController::class, 'index'])
    ->middleware(['auth'])
    ->name('dashboard');

Route::middleware('auth')->group(function () {
    Route::get('/datatable/{source}', [PanelDataTableController::class, 'index'])->name('datatable.index');

    Route::get('/profile', [ProfileController::class, 'edit'])->name('profile.edit');
    Route::patch('/profile', [ProfileController::class, 'update'])->name('profile.update');
    Route::delete('/profile', [ProfileController::class, 'destroy'])->name('profile.destroy');

    Route::prefix('admin')->name('admin.')->middleware('role:admin_dinas')->group(function () {
        Route::get('/dashboard', [AdminDashboardController::class, 'index'])->name('dashboard');
        Route::get('/dashboard/data', [AdminDashboardController::class, 'data'])->name('dashboard.data');
        Route::resource('/users', UserManagementController::class)->except('show');

        Route::get('/wilayah', [WilayahController::class, 'index'])->name('wilayah');
        Route::get('/wilayah/kecamatan-options', [WilayahController::class, 'kecamatanOptions'])->name('wilayah.kecamatan.options');
        Route::post('/wilayah/kecamatan', [WilayahController::class, 'storeKecamatan'])->name('wilayah.kecamatan.store');
        Route::put('/wilayah/kecamatan/{id}', [WilayahController::class, 'updateKecamatan'])->name('wilayah.kecamatan.update');
        Route::delete('/wilayah/kecamatan/{id}', [WilayahController::class, 'destroyKecamatan'])->name('wilayah.kecamatan.destroy');
        Route::post('/wilayah/desa', [WilayahController::class, 'storeDesa'])->name('wilayah.desa.store');
        Route::put('/wilayah/desa/{id}', [WilayahController::class, 'updateDesa'])->name('wilayah.desa.update');
        Route::delete('/wilayah/desa/{id}', [WilayahController::class, 'destroyDesa'])->name('wilayah.desa.destroy');

        Route::get('/komoditas', [KomoditasController::class, 'index'])->name('komoditas');
        Route::post('/komoditas', [KomoditasController::class, 'store'])->name('komoditas.store');
        Route::put('/komoditas/{id}', [KomoditasController::class, 'update'])->name('komoditas.update');
        Route::delete('/komoditas/{id}', [KomoditasController::class, 'destroy'])->name('komoditas.destroy');

        Route::get('/kelompok-tani', [AdminKelompokTaniController::class, 'index'])->name('kelompok_tani');
        Route::get('/kelompok-tani/options', [AdminKelompokTaniController::class, 'options'])->name('kelompok_tani.options');
        Route::post('/kelompok-tani', [AdminKelompokTaniController::class, 'store'])->name('kelompok_tani.store');
        Route::put('/kelompok-tani/{id}', [AdminKelompokTaniController::class, 'update'])->name('kelompok_tani.update');
        Route::delete('/kelompok-tani/{id}', [AdminKelompokTaniController::class, 'destroy'])->name('kelompok_tani.destroy');

        Route::get('/map-style', [MapStyleController::class, 'index'])->name('map_style');
        Route::post('/map-style/entity/{entityKey}', [MapStyleController::class, 'upsertEntity'])->name('map_style.entity.upsert');
        Route::post('/map-style/komoditas/{komoditasId}', [MapStyleController::class, 'upsertKomoditas'])->name('map_style.komoditas.upsert');

        Route::get('/balai', [BalaiPenyuluhController::class, 'index'])->name('balai');
        Route::post('/balai', [BalaiPenyuluhController::class, 'storeBalai'])->name('balai.store');
        Route::put('/balai/{id}', [BalaiPenyuluhController::class, 'updateBalai'])->name('balai.update');
        Route::delete('/balai/{id}', [BalaiPenyuluhController::class, 'destroyBalai'])->name('balai.destroy');
        Route::post('/balai/penyuluh', [BalaiPenyuluhController::class, 'storePenyuluh'])->name('balai.penyuluh.store');
        Route::put('/balai/penyuluh/{id}', [BalaiPenyuluhController::class, 'updatePenyuluh'])->name('balai.penyuluh.update');
        Route::delete('/balai/penyuluh/{id}', [BalaiPenyuluhController::class, 'destroyPenyuluh'])->name('balai.penyuluh.destroy');

        Route::get('/monitoring', [MonitoringController::class, 'index'])->name('monitoring');
        Route::post('/monitoring/{kunjunganId}/verify', [MonitoringController::class, 'verify'])->name('monitoring.verify');

        Route::get('/laporan', [AdminLaporanController::class, 'index'])->name('laporan');
        Route::post('/laporan/periode', [AdminLaporanController::class, 'storePeriode'])->name('laporan.periode.store');
        Route::patch('/laporan/periode/{id}', [AdminLaporanController::class, 'updatePeriode'])->name('laporan.periode.update');
        Route::post('/laporan/create', [AdminLaporanController::class, 'storeLaporan'])->name('laporan.store');
        Route::post('/laporan/{laporanId}/generate-detail', [AdminLaporanController::class, 'generateDetail'])->name('laporan.generate_detail');
    });

    Route::prefix('kecamatan')->name('kecamatan.')->middleware('role:admin_kecamatan')->group(function () {
        Route::get('/dashboard', [KecamatanDashboardController::class, 'index'])->name('dashboard');
        Route::get('/dashboard/data', [KecamatanDashboardController::class, 'data'])->name('dashboard.data');

        Route::get('/penyuluh', [KecamatanPenyuluhController::class, 'index'])->name('penyuluh');
        Route::get('/penyuluh/options', [KecamatanPenyuluhController::class, 'options'])->name('penyuluh.options');
        Route::get('/penyuluh/lahan-options', [KecamatanPenyuluhController::class, 'lahanOptions'])->name('penyuluh.lahan.options');
        Route::post('/penyuluh', [KecamatanPenyuluhController::class, 'store'])->name('penyuluh.store');
        Route::put('/penyuluh/{id}', [KecamatanPenyuluhController::class, 'update'])->name('penyuluh.update');
        Route::delete('/penyuluh/{id}', [KecamatanPenyuluhController::class, 'destroy'])->name('penyuluh.destroy');
        Route::post('/penyuluh/penugasan', [KecamatanPenyuluhController::class, 'storePenugasan'])->name('penyuluh.penugasan.store');
        Route::put('/penyuluh/penugasan/{id}', [KecamatanPenyuluhController::class, 'updatePenugasan'])->name('penyuluh.penugasan.update');
        Route::delete('/penyuluh/penugasan/{id}', [KecamatanPenyuluhController::class, 'destroyPenugasan'])->name('penyuluh.penugasan.destroy');

        Route::get('/kelompok-tani', [KecamatanKelompokTaniController::class, 'index'])->name('kelompok_tani');
        Route::get('/kelompok-tani/options', [KecamatanKelompokTaniController::class, 'options'])->name('kelompok_tani.options');
        Route::post('/kelompok-tani', [KecamatanKelompokTaniController::class, 'store'])->name('kelompok_tani.store');
        Route::put('/kelompok-tani/{id}', [KecamatanKelompokTaniController::class, 'update'])->name('kelompok_tani.update');
        Route::delete('/kelompok-tani/{id}', [KecamatanKelompokTaniController::class, 'destroy'])->name('kelompok_tani.destroy');

        Route::get('/petani-lahan', [PetaniLahanController::class, 'index'])->name('petani_lahan');
        Route::post('/petani-lahan/petani', [PetaniLahanController::class, 'storePetani'])->name('petani_lahan.petani.store');
        Route::put('/petani-lahan/petani/{id}', [PetaniLahanController::class, 'updatePetani'])->name('petani_lahan.petani.update');
        Route::delete('/petani-lahan/petani/{id}', [PetaniLahanController::class, 'destroyPetani'])->name('petani_lahan.petani.destroy');
        Route::post('/petani-lahan/lahan', [PetaniLahanController::class, 'storeLahan'])->name('petani_lahan.lahan.store');
        Route::put('/petani-lahan/lahan/{id}', [PetaniLahanController::class, 'updateLahan'])->name('petani_lahan.lahan.update');
        Route::delete('/petani-lahan/lahan/{id}', [PetaniLahanController::class, 'destroyLahan'])->name('petani_lahan.lahan.destroy');
        Route::post('/petani-lahan/lahan-komoditas', [PetaniLahanController::class, 'storeLahanKomoditas'])->name('petani_lahan.lahan_komoditas.store');
        Route::put('/petani-lahan/lahan-komoditas/{id}', [PetaniLahanController::class, 'updateLahanKomoditas'])->name('petani_lahan.lahan_komoditas.update');
        Route::delete('/petani-lahan/lahan-komoditas/{id}', [PetaniLahanController::class, 'destroyLahanKomoditas'])->name('petani_lahan.lahan_komoditas.destroy');

        Route::get('/verifikasi', [VerifikasiController::class, 'index'])->name('verifikasi');
        Route::post('/verifikasi/{kunjunganId}', [VerifikasiController::class, 'update'])->name('verifikasi.update');
        Route::post('/verifikasi/usulan/{id}', [VerifikasiController::class, 'updateUsulan'])->name('verifikasi.usulan.update');

        Route::get('/rekap', [RekapController::class, 'index'])->name('rekap');
    });

    Route::prefix('penyuluh')->name('penyuluh.')->middleware('role:penyuluh')->group(function () {
        Route::get('/dashboard', [PenyuluhDashboardController::class, 'index'])->name('dashboard');
        Route::get('/dashboard/data', [PenyuluhDashboardController::class, 'data'])->name('dashboard.data');

        Route::get('/petani', [PetaniBinaanController::class, 'index'])->name('petani');

        Route::get('/kunjungan', [KunjunganController::class, 'index'])->name('kunjungan');
        Route::get('/kunjungan/penugasan/{id}/detail', [KunjunganController::class, 'detail'])->name('kunjungan.detail');
        Route::post('/kunjungan', [KunjunganController::class, 'store'])->name('kunjungan.store');
        Route::post('/kunjungan/perbaikan', [KunjunganController::class, 'storePerbaikan'])->name('kunjungan.perbaikan.store');
        Route::put('/kunjungan/{id}', [KunjunganController::class, 'update'])->name('kunjungan.update');
        Route::delete('/kunjungan/{id}', [KunjunganController::class, 'destroy'])->name('kunjungan.destroy');

        Route::get('/produksi', [ProduksiController::class, 'index'])->name('produksi');
        Route::post('/produksi', [ProduksiController::class, 'store'])->name('produksi.store');
        Route::put('/produksi/{id}', [ProduksiController::class, 'update'])->name('produksi.update');
        Route::delete('/produksi/{id}', [ProduksiController::class, 'destroy'])->name('produksi.destroy');

        Route::get('/kendala', [KendalaKebutuhanController::class, 'index'])->name('kendala');
        Route::post('/kendala/kendala', [KendalaKebutuhanController::class, 'storeKendala'])->name('kendala.kendala.store');
        Route::put('/kendala/kendala/{id}', [KendalaKebutuhanController::class, 'updateKendala'])->name('kendala.kendala.update');
        Route::delete('/kendala/kendala/{id}', [KendalaKebutuhanController::class, 'destroyKendala'])->name('kendala.kendala.destroy');
        Route::post('/kendala/kebutuhan', [KendalaKebutuhanController::class, 'storeKebutuhan'])->name('kendala.kebutuhan.store');
        Route::put('/kendala/kebutuhan/{id}', [KendalaKebutuhanController::class, 'updateKebutuhan'])->name('kendala.kebutuhan.update');
        Route::delete('/kendala/kebutuhan/{id}', [KendalaKebutuhanController::class, 'destroyKebutuhan'])->name('kendala.kebutuhan.destroy');

        Route::get('/riwayat', [RiwayatController::class, 'index'])->name('riwayat');
    });

    Route::prefix('pimpinan')->name('pimpinan.')->middleware('role:pimpinan_dinas')->group(function () {
        Route::get('/dashboard', [PimpinanDashboardController::class, 'index'])->name('dashboard');
        Route::get('/dashboard/data', [PimpinanDashboardController::class, 'data'])->name('dashboard.data');
        Route::get('/peta', [PetaController::class, 'index'])->name('peta');
        Route::get('/laporan', [PimpinanLaporanController::class, 'index'])->name('laporan');
        Route::get('/analitik', [AnalitikController::class, 'index'])->name('analitik');
        Route::get('/unduh', [UnduhController::class, 'index'])->name('unduh');
        Route::get('/unduh/produksi-csv', [UnduhController::class, 'produksiCsv'])->name('unduh.produksi_csv');
        Route::get('/unduh/laporan-csv', [UnduhController::class, 'laporanCsv'])->name('unduh.laporan_csv');
        Route::get('/unduh/laporan-pdf', [UnduhController::class, 'laporanPdf'])->name('unduh.laporan_pdf');
    });
});

require __DIR__.'/auth.php';
