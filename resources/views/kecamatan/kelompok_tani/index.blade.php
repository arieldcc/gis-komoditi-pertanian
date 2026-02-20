@extends('layouts.panel', [
    'pageTitle' => 'Master Kelompok Tani',
    'pageSubtitle' => 'CRUD data master kelompok tani untuk input petani.',
])

@section('panel_content')
<div class="panel-card p-3 mb-3">
    <h6 class="fw-semibold">Tambah Kelompok Tani</h6>
    <form method="POST" action="{{ route('kecamatan.kelompok_tani.store') }}" class="row g-2">
        @csrf
        <div class="col-md-3"><input class="form-control" placeholder="Kode Kelompok (otomatis)" readonly></div>
        <div class="col-md-5"><input class="form-control" name="nama_kelompok" placeholder="Nama Kelompok Tani" required></div>
        <div class="col-md-2"><textarea class="form-control" name="deskripsi" rows="1" placeholder="Deskripsi"></textarea></div>
        <div class="col-md-2 d-flex align-items-center"><div class="form-check"><input class="form-check-input" type="checkbox" name="is_active" value="1" checked><label class="form-check-label">Aktif</label></div></div>
        <div class="col-12"><button class="btn btn-success btn-sm">Simpan</button></div>
    </form>
</div>

<div class="table-responsive panel-card p-2">
    <table class="table align-middle mb-0" data-dt-server="true" data-dt-source="kecamatan_kelompok_tani" data-dt-page-length="10">
        <thead>
            <tr>
                <th data-col="kode_kelompok">Kode</th>
                <th data-col="nama_kelompok">Nama Kelompok</th>
                <th data-col="deskripsi">Deskripsi</th>
                <th data-col="status">Status</th>
                <th data-col="actions" data-orderable="false" data-searchable="false">Aksi</th>
            </tr>
        </thead>
        <tbody></tbody>
    </table>
</div>
@endsection
