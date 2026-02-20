@extends('layouts.panel', [
    'pageTitle' => 'Input Produksi',
    'pageSubtitle' => 'CRUD data produksi panen berdasarkan lahan komoditas.',
])

@section('panel_content')
@if(!$penyuluhId)
    <div class="alert alert-warning">Profil penyuluh belum tersedia.</div>
@else
<div class="panel-card p-3 mb-3">
    <h6 class="fw-semibold">Tambah Produksi Panen</h6>
    <form method="POST" action="{{ route('penyuluh.produksi.store') }}" class="row g-2">
        @csrf
        <div class="col-md-3"><select class="form-select" name="lahan_komoditas_id" required><option value="">Lahan Komoditas</option>@foreach($lahanKomoditas as $lk)<option value="{{ $lk->id }}">{{ $lk->nama_petani }} - {{ $lk->nama_komoditas }}</option>@endforeach</select></div>
        <div class="col-md-2"><select class="form-select" name="periode_id" required><option value="">Periode</option>@foreach($periode as $p)<option value="{{ $p->id }}">{{ sprintf('%02d',$p->bulan) }}/{{ $p->tahun }}</option>@endforeach</select></div>
        <div class="col-md-3"><select class="form-select" name="kunjungan_id"><option value="">Opsional Kunjungan</option>@foreach($kunjungan as $k)<option value="{{ $k->id }}">{{ $k->nama_petani }} - {{ $k->tanggal_kunjungan }}</option>@endforeach</select></div>
        <div class="col-md-2"><input class="form-control" type="date" name="tanggal_panen" required></div>
        <div class="col-md-2"><input class="form-control" name="jumlah_produksi" placeholder="Produksi" required></div>
        <div class="col-md-3"><input class="form-control" name="produktivitas_kg_ha" placeholder="Produktivitas"></div>
        <div class="col-md-3"><input class="form-control" name="harga_jual" placeholder="Harga jual"></div>
        <div class="col-md-6"><textarea class="form-control" name="catatan" rows="2" placeholder="Catatan"></textarea></div>
        <div class="col-12"><button class="btn btn-success btn-sm">Simpan Produksi</button></div>
    </form>
</div>

<div class="table-responsive panel-card p-2">
    <table class="table table-sm align-middle mb-0" data-dt-server="true" data-dt-source="penyuluh_produksi" data-dt-page-length="10">
        <thead>
            <tr>
                <th data-col="tanggal_panen">Tanggal</th>
                <th data-col="nama_petani">Petani</th>
                <th data-col="nama_komoditas">Komoditas</th>
                <th data-col="jumlah_produksi">Produksi</th>
                <th data-col="periode">Periode</th>
                <th data-col="actions" data-orderable="false" data-searchable="false">Aksi</th>
            </tr>
        </thead>
        <tbody></tbody>
    </table>
</div>
@endif
@endsection
