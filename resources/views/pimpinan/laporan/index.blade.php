@extends('layouts.panel', [
    'pageTitle' => 'Laporan Terverifikasi',
    'pageSubtitle' => 'Akses laporan pimpinan berdasarkan periode.',
])

@section('panel_content')
<div class="row g-3">
    <div class="col-lg-6">
        <div class="table-responsive panel-card p-2">
            <table class="table table-sm align-middle mb-0" data-dt-server="true" data-dt-source="pimpinan_laporan_list" data-dt-page-length="10">
                <thead>
                    <tr>
                        <th data-col="generated_at">Generated At</th>
                        <th data-col="jenis_laporan">Jenis</th>
                        <th data-col="periode">Periode</th>
                        <th data-col="generated_by">Oleh</th>
                        <th data-col="file_url">Dokumen</th>
                        <th data-col="actions" data-orderable="false" data-searchable="false">Aksi</th>
                    </tr>
                </thead>
                <tbody></tbody>
            </table>
        </div>
    </div>

    <div class="col-lg-6">
        <div class="table-responsive panel-card p-2">
            <table class="table table-sm align-middle mb-0" data-dt-server="true" data-dt-source="pimpinan_laporan_detail" data-dt-page-length="10">
                <thead>
                    <tr>
                        <th data-col="periode">Periode</th>
                        <th data-col="nama_kecamatan">Kecamatan</th>
                        <th data-col="total_produksi">Total Produksi</th>
                        <th data-col="total_luas">Total Luas</th>
                        <th data-col="total_petani">Petani</th>
                        <th data-col="total_lahan">Lahan</th>
                        <th data-col="actions" data-orderable="false" data-searchable="false">Aksi</th>
                    </tr>
                </thead>
                <tbody></tbody>
            </table>
        </div>
    </div>
</div>
@endsection
