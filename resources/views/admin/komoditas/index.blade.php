@extends('layouts.panel', [
    'pageTitle' => 'Master Komoditas',
    'pageSubtitle' => 'CRUD data komoditas pertanian.',
])

@section('panel_content')
<div class="panel-card p-3 mb-3">
    <h6 class="fw-semibold">Tambah Komoditas</h6>
    <form method="POST" action="{{ route('admin.komoditas.store') }}" class="row g-2">
        @csrf
        <div class="col-md-3"><input class="form-control" placeholder="Kode Komoditas (otomatis)" readonly></div>
        <div class="col-md-4"><input class="form-control" name="nama_komoditas" placeholder="Nama Komoditas" required></div>
        <div class="col-md-3"><input class="form-control" name="satuan_default" placeholder="Satuan" value="kg" required></div>
        <div class="col-md-2 d-flex align-items-center"><div class="form-check"><input class="form-check-input" type="checkbox" name="is_active" value="1" checked><label class="form-check-label">Aktif</label></div></div>
        <div class="col-12"><button class="btn btn-success btn-sm">Simpan</button></div>
    </form>
</div>

<div class="table-responsive panel-card p-2">
    <table class="table align-middle mb-0" data-dt-server="true" data-dt-source="admin_komoditas" data-dt-page-length="10">
        <thead>
            <tr>
                <th data-col="kode_komoditas">Kode</th>
                <th data-col="nama_komoditas">Nama</th>
                <th data-col="satuan_default">Satuan</th>
                <th data-col="is_active">Status</th>
                <th data-col="actions" data-orderable="false" data-searchable="false">Aksi</th>
            </tr>
        </thead>
        <tbody></tbody>
    </table>
</div>
@endsection
