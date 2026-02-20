<?php

return [
    'admin_dinas' => [
        ['label' => 'Dashboard', 'route' => 'admin.dashboard'],
        ['label' => 'Manajemen User', 'route' => 'admin.users.index'],
        ['label' => 'Master Wilayah', 'route' => 'admin.wilayah'],
        ['label' => 'Master Komoditas', 'route' => 'admin.komoditas'],
        ['label' => 'Master Kelompok Tani', 'route' => 'admin.kelompok_tani'],
        ['label' => 'Style Ikon Peta', 'route' => 'admin.map_style'],
        ['label' => 'Data Balai & Penyuluh', 'route' => 'admin.balai'],
        ['label' => 'Monitoring Laporan', 'route' => 'admin.monitoring'],
        ['label' => 'Laporan Pimpinan', 'route' => 'admin.laporan'],
    ],
    'admin_kecamatan' => [
        ['label' => 'Dashboard', 'route' => 'kecamatan.dashboard'],
        ['label' => 'Data Penyuluh', 'route' => 'kecamatan.penyuluh'],
        ['label' => 'Master Kelompok Tani', 'route' => 'kecamatan.kelompok_tani'],
        ['label' => 'Data Petani & Lahan', 'route' => 'kecamatan.petani_lahan'],
        ['label' => 'Verifikasi Laporan', 'route' => 'kecamatan.verifikasi'],
        ['label' => 'Rekap Kecamatan', 'route' => 'kecamatan.rekap'],
    ],
    'penyuluh' => [
        ['label' => 'Dashboard', 'route' => 'penyuluh.dashboard'],
        ['label' => 'Kunjungan Terpadu', 'route' => 'penyuluh.kunjungan'],
        ['label' => 'Riwayat Laporan', 'route' => 'penyuluh.riwayat'],
    ],
    'pimpinan_dinas' => [
        ['label' => 'Dashboard', 'route' => 'pimpinan.dashboard'],
        ['label' => 'Peta Komoditas', 'route' => 'pimpinan.peta'],
        ['label' => 'Laporan Terverifikasi', 'route' => 'pimpinan.laporan'],
        ['label' => 'Analitik Tren', 'route' => 'pimpinan.analitik'],
        ['label' => 'Unduh Dokumen', 'route' => 'pimpinan.unduh'],
    ],
];
