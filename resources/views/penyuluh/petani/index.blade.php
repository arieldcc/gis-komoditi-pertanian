@extends('layouts.panel', [
    'pageTitle' => 'Petani Binaan',
    'pageSubtitle' => 'Daftar petani yang ditugaskan ke penyuluh login.',
])

@section('panel_content')
@if(!$penyuluhId)
    <div class="alert alert-warning">Profil penyuluh untuk akun ini belum tersedia. Hubungi admin.</div>
@else
    <div class="table-responsive panel-card p-2">
        <table class="table align-middle mb-0" data-dt-server="true" data-dt-source="penyuluh_petani_binaan" data-dt-page-length="10">
            <thead>
                <tr>
                    <th data-col="nama_petani">Petani</th>
                    <th data-col="nama_desa">Desa</th>
                    <th data-col="no_hp">Kontak</th>
                    <th data-col="luas_ha">Luas Lahan</th>
                    <th data-col="kondisi_lahan">Kondisi</th>
                    <th data-col="komoditas">Komoditas</th>
                    <th data-col="actions" data-orderable="false" data-searchable="false">Aksi</th>
                </tr>
            </thead>
            <tbody></tbody>
        </table>
    </div>
@endif
@endsection
