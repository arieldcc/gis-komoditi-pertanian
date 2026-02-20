@extends('layouts.panel', [
    'pageTitle' => 'Rekap Kecamatan',
    'pageSubtitle' => 'Ringkasan petani, lahan, dan komoditas wilayah kerja.',
])

@section('panel_content')
<div class="row g-3">
    <div class="col-lg-7">
        <div class="table-responsive panel-card p-2">
            <table class="table align-middle mb-0" data-dt-server="true" data-dt-source="kecamatan_rekap_summary" data-dt-page-length="10">
                <thead>
                    <tr>
                        <th data-col="nama_kecamatan">Kecamatan</th>
                        <th data-col="total_petani">Total Petani</th>
                        <th data-col="total_lahan">Total Lahan</th>
                        <th data-col="total_luas">Total Luas (ha)</th>
                        <th data-col="actions" data-orderable="false" data-searchable="false">Aksi</th>
                    </tr>
                </thead>
                <tbody></tbody>
            </table>
        </div>
    </div>
    <div class="col-lg-5">
        <div class="table-responsive panel-card p-2">
            <table class="table table-sm align-middle mb-0" data-dt-server="true" data-dt-source="kecamatan_rekap_komoditas" data-dt-page-length="10">
                <thead>
                    <tr>
                        <th data-col="nama_komoditas">Komoditas</th>
                        <th data-col="total_lahan_komoditas">Jumlah Lahan</th>
                        <th data-col="total_luas_tanam">Luas Tanam</th>
                        <th data-col="actions" data-orderable="false" data-searchable="false">Aksi</th>
                    </tr>
                </thead>
                <tbody></tbody>
            </table>
        </div>
    </div>
</div>
@endsection
