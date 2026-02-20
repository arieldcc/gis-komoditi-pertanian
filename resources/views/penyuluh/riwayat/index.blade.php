@extends('layouts.panel', [
    'pageTitle' => 'Riwayat Laporan',
    'pageSubtitle' => 'Status historis laporan kunjungan penyuluh.',
])

@section('panel_content')
@if(!$penyuluhId)
    <div class="alert alert-warning">Profil penyuluh belum tersedia.</div>
@else
<div class="table-responsive panel-card p-2">
    <table class="table align-middle mb-0" data-dt-server="true" data-dt-source="penyuluh_riwayat" data-dt-page-length="10">
        <thead>
            <tr>
                <th data-col="tanggal_kunjungan">Tanggal</th>
                <th data-col="nama_petani">Petani</th>
                <th data-col="status_verifikasi">Status</th>
                <th data-col="catatan_verifikasi">Catatan Verifikasi</th>
                <th data-col="diverifikasi_at">Waktu Verifikasi</th>
                <th data-col="actions" data-orderable="false" data-searchable="false">Aksi</th>
            </tr>
        </thead>
        <tbody></tbody>
    </table>
</div>
@endif
@endsection
