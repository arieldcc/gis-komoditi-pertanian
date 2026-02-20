@extends('layouts.panel', [
    'pageTitle' => 'Verifikasi Laporan',
    'pageSubtitle' => 'Verifikasi laporan kunjungan dari penyuluh wilayah Anda.',
])

@section('panel_content')
<div class="panel-card p-3 mb-3">
    <div class="d-flex justify-content-between align-items-center">
        <div>
            <h6 class="fw-semibold mb-1">Notifikasi Verifikasi Data</h6>
            <small class="text-muted">Usulan perubahan data dari penyuluh menunggu tindak lanjut admin kecamatan.</small>
        </div>
        <span class="badge text-bg-warning fs-6">{{ $pendingUsulanCount ?? 0 }} usulan menunggu</span>
    </div>
</div>

<div class="panel-card p-2 mb-3">
    <h6 class="fw-semibold px-2 pt-2">Laporan Kunjungan Penyuluh</h6>
    <div class="table-responsive">
        <table class="table table-sm align-middle mb-0" data-dt-server="true" data-dt-source="kecamatan_verifikasi" data-dt-page-length="10" data-dt-auto-refresh="12000">
            <thead>
                <tr>
                    <th data-col="tanggal_kunjungan">Tanggal</th>
                    <th data-col="nama_kecamatan">Kecamatan</th>
                    <th data-col="nama_penyuluh">Penyuluh</th>
                    <th data-col="nama_petani">Petani</th>
                    <th data-col="status_verifikasi">Status</th>
                    <th data-col="actions" data-orderable="false" data-searchable="false">Aksi</th>
                </tr>
            </thead>
            <tbody></tbody>
        </table>
    </div>
</div>

<div class="panel-card p-2">
    <h6 class="fw-semibold px-2 pt-2">Usulan Perubahan Data Lapangan</h6>
    <div class="table-responsive">
        <table class="table table-sm align-middle mb-0" data-dt-server="true" data-dt-source="kecamatan_usulan_perubahan" data-dt-page-length="10" data-dt-auto-refresh="12000">
            <thead>
                <tr>
                    <th data-col="waktu_usulan">Waktu</th>
                    <th data-col="nama_pengaju">Penyuluh</th>
                    <th data-col="nama_petani">Petani</th>
                    <th data-col="target">Target</th>
                    <th data-col="field_name">Field</th>
                    <th data-col="nilai_usulan">Nilai Usulan</th>
                    <th data-col="status">Status</th>
                    <th data-col="actions" data-orderable="false" data-searchable="false">Aksi</th>
                </tr>
            </thead>
            <tbody></tbody>
        </table>
    </div>
</div>
@endsection
