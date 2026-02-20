@extends('layouts.panel', [
    'pageTitle' => 'Monitoring Laporan',
    'pageSubtitle' => 'Verifikasi status kunjungan monitoring dari lapangan.',
])

@section('panel_content')
<div class="table-responsive panel-card p-2">
    <table class="table table-sm align-middle mb-0" data-dt-server="true" data-dt-source="admin_monitoring" data-dt-page-length="10">
        <thead>
            <tr>
                <th data-col="tanggal_kunjungan">Tanggal</th>
                <th data-col="nama_penyuluh">Penyuluh</th>
                <th data-col="nama_petani">Petani</th>
                <th data-col="status_verifikasi">Status</th>
                <th data-col="actions" data-orderable="false" data-searchable="false">Aksi</th>
            </tr>
        </thead>
        <tbody></tbody>
    </table>
</div>
@endsection
