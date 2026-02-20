@extends('layouts.panel', [
    'pageTitle' => 'Peta Komoditas',
    'pageSubtitle' => 'Visualisasi titik wilayah dan balai penyuluh.',
])

@section('panel_content')
<div class="row g-3">
    <div class="col-12">
        <div class="panel-card p-3">
            <div class="d-flex justify-content-between align-items-center mb-2">
                <h6 class="fw-semibold mb-0">Peta Banggai, Sulawesi Tengah</h6>
                <small class="text-muted">Klik titik marker untuk melihat detail</small>
            </div>
            <div
                class="sig-map"
                data-map-picker
                data-center-lat="-1.25"
                data-center-lng="123.23"
                data-zoom="9"
                data-styles='@json($mapStyles)'
                data-markers='@json($mapMarkers)'
                data-fit-markers="true"
            ></div>
        </div>
    </div>
    <div class="col-lg-7">
        <div class="table-responsive panel-card p-2">
            <table class="table align-middle mb-0" data-dt-server="true" data-dt-source="pimpinan_peta_kecamatan" data-dt-page-length="10">
                <thead>
                    <tr>
                        <th data-col="nama_kecamatan">Kecamatan</th>
                        <th data-col="centroid_lat">Lat</th>
                        <th data-col="centroid_lng">Lng</th>
                        <th data-col="total_lahan">Lahan</th>
                        <th data-col="total_komoditas">Komoditas</th>
                        <th data-col="actions" data-orderable="false" data-searchable="false">Aksi</th>
                    </tr>
                </thead>
                <tbody></tbody>
            </table>
        </div>
    </div>
    <div class="col-lg-5">
        <div class="table-responsive panel-card p-2">
            <table class="table table-sm align-middle mb-0" data-dt-server="true" data-dt-source="pimpinan_peta_balai" data-dt-page-length="10">
                <thead>
                    <tr>
                        <th data-col="nama_balai">Balai</th>
                        <th data-col="nama_kecamatan">Kecamatan</th>
                        <th data-col="koordinat">Koordinat</th>
                        <th data-col="actions" data-orderable="false" data-searchable="false">Aksi</th>
                    </tr>
                </thead>
                <tbody></tbody>
            </table>
        </div>
    </div>
</div>
@endsection
