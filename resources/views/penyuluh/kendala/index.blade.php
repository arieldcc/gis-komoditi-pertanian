@extends('layouts.panel', [
    'pageTitle' => 'Kendala & Kebutuhan',
    'pageSubtitle' => 'CRUD kendala kunjungan dan kebutuhan petani.',
])

@section('panel_content')
@if(!$penyuluhId)
    <div class="alert alert-warning">Profil penyuluh belum tersedia.</div>
@else
<div class="row g-3 mb-3">
    <div class="col-lg-6">
        <div class="panel-card p-3">
            <h6 class="fw-semibold">Tambah Kendala</h6>
            <form method="POST" action="{{ route('penyuluh.kendala.kendala.store') }}" class="row g-2">
                @csrf
                <div class="col-12"><select class="form-select" name="kunjungan_id" required><option value="">Pilih kunjungan</option>@foreach($kunjungan as $k)<option value="{{ $k->id }}">{{ $k->nama_petani }} - {{ $k->tanggal_kunjungan }}</option>@endforeach</select></div>
                <div class="col-12"><select class="form-select" name="kategori_kendala_id" required><option value="">Kategori</option>@foreach($kategoriKendala as $k)<option value="{{ $k->id }}">{{ $k->nama_kategori }}</option>@endforeach</select></div>
                <div class="col-12"><textarea class="form-control" name="deskripsi_kendala" rows="2" placeholder="Deskripsi" required></textarea></div>
                <div class="col-6"><select class="form-select" name="tingkat_keparahan" required>@foreach(['rendah','sedang','tinggi','kritis'] as $s)<option value="{{ $s }}">{{ ucfirst($s) }}</option>@endforeach</select></div>
                <div class="col-6 d-flex align-items-center"><div class="form-check"><input class="form-check-input" type="checkbox" name="perlu_tindak_lanjut" value="1" checked><label class="form-check-label">Perlu tindak lanjut</label></div></div>
                <div class="col-12"><button class="btn btn-success btn-sm">Simpan Kendala</button></div>
            </form>
        </div>
    </div>

    <div class="col-lg-6">
        <div class="panel-card p-3">
            <h6 class="fw-semibold">Tambah Kebutuhan</h6>
            <form method="POST" action="{{ route('penyuluh.kendala.kebutuhan.store') }}" class="row g-2">
                @csrf
                <div class="col-12"><select class="form-select" name="kunjungan_id" required><option value="">Pilih kunjungan</option>@foreach($kunjungan as $k)<option value="{{ $k->id }}">{{ $k->nama_petani }} - {{ $k->tanggal_kunjungan }}</option>@endforeach</select></div>
                <div class="col-12"><select class="form-select" name="kategori_kebutuhan_id" required><option value="">Kategori</option>@foreach($kategoriKebutuhan as $k)<option value="{{ $k->id }}">{{ $k->nama_kategori }}</option>@endforeach</select></div>
                <div class="col-12"><textarea class="form-control" name="deskripsi_kebutuhan" rows="2" placeholder="Deskripsi" required></textarea></div>
                <div class="col-4"><input class="form-control" name="jumlah" placeholder="Jumlah"></div>
                <div class="col-4"><input class="form-control" name="satuan" placeholder="Satuan"></div>
                <div class="col-4"><select class="form-select" name="prioritas" required>@foreach(['rendah','sedang','tinggi'] as $s)<option value="{{ $s }}">{{ ucfirst($s) }}</option>@endforeach</select></div>
                <div class="col-12"><button class="btn btn-success btn-sm">Simpan Kebutuhan</button></div>
            </form>
        </div>
    </div>
</div>

<div class="row g-3">
    <div class="col-lg-6">
        <div class="table-responsive panel-card p-2">
            <table class="table table-sm mb-0" data-dt-server="true" data-dt-source="penyuluh_kendala" data-dt-page-length="10">
                <thead>
                    <tr>
                        <th data-col="nama_petani">Petani</th>
                        <th data-col="nama_kategori">Kategori Kendala</th>
                        <th data-col="tingkat_keparahan">Keparahan</th>
                        <th data-col="perlu_tindak_lanjut">Tindak Lanjut</th>
                        <th data-col="actions" data-orderable="false" data-searchable="false">Aksi</th>
                    </tr>
                </thead>
                <tbody></tbody>
            </table>
        </div>
    </div>

    <div class="col-lg-6">
        <div class="table-responsive panel-card p-2">
            <table class="table table-sm mb-0" data-dt-server="true" data-dt-source="penyuluh_kebutuhan" data-dt-page-length="10">
                <thead>
                    <tr>
                        <th data-col="nama_petani">Petani</th>
                        <th data-col="nama_kategori">Kategori Kebutuhan</th>
                        <th data-col="jumlah">Jumlah</th>
                        <th data-col="prioritas">Prioritas</th>
                        <th data-col="actions" data-orderable="false" data-searchable="false">Aksi</th>
                    </tr>
                </thead>
                <tbody></tbody>
            </table>
        </div>
    </div>
</div>
@endif
@endsection
