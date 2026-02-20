@extends('layouts.panel', [
    'pageTitle' => 'Analitik Tren',
    'pageSubtitle' => 'Analisa produksi komoditas dan tren periode.',
])

@section('panel_content')
<div class="row g-3 mb-3">
    <div class="col-md-3"><div class="panel-card p-3"><small class="text-muted">Total Produksi</small><div class="h4 mb-0">{{ number_format($ringkasan['total_produksi'],2) }}</div></div></div>
    <div class="col-md-3"><div class="panel-card p-3"><small class="text-muted">Rata Harga</small><div class="h4 mb-0">{{ number_format($ringkasan['rata_harga'],2) }}</div></div></div>
    <div class="col-md-3"><div class="panel-card p-3"><small class="text-muted">Komoditas Aktif</small><div class="h4 mb-0">{{ $ringkasan['komoditas_aktif'] }}</div></div></div>
    <div class="col-md-3"><div class="panel-card p-3"><small class="text-muted">Laporan Terbit</small><div class="h4 mb-0">{{ $ringkasan['laporan_terbit'] }}</div></div></div>
</div>

<div class="row g-3">
    <div class="col-lg-6">
        <div class="table-responsive panel-card p-2">
            <table class="table table-sm align-middle mb-0" data-dt-server="true" data-dt-source="pimpinan_analitik_komoditas" data-dt-page-length="10">
                <thead>
                    <tr>
                        <th data-col="nama_komoditas">Komoditas</th>
                        <th data-col="total_produksi">Total Produksi</th>
                        <th data-col="rata_harga">Rata Harga</th>
                        <th data-col="actions" data-orderable="false" data-searchable="false">Aksi</th>
                    </tr>
                </thead>
                <tbody></tbody>
            </table>
        </div>
    </div>
    <div class="col-lg-6">
        <div class="table-responsive panel-card p-2">
            <table class="table table-sm align-middle mb-0" data-dt-server="true" data-dt-source="pimpinan_analitik_periode" data-dt-page-length="10">
                <thead>
                    <tr>
                        <th data-col="periode">Periode</th>
                        <th data-col="total_produksi">Total Produksi</th>
                        <th data-col="actions" data-orderable="false" data-searchable="false">Aksi</th>
                    </tr>
                </thead>
                <tbody></tbody>
            </table>
        </div>
    </div>
</div>
@endsection
