@extends('layouts.panel', [
    'pageTitle' => 'Master Wilayah',
    'pageSubtitle' => 'CRUD kecamatan dan desa.',
])

@section('panel_content')
<div class="row g-3">
    <div class="col-lg-5">
        <div class="panel-card p-3 mb-3">
            <h6 class="fw-semibold">Tambah Kecamatan</h6>
            <form method="POST" action="{{ route('admin.wilayah.kecamatan.store') }}" class="row g-2">
                @csrf
                <div class="col-4"><input class="form-control" placeholder="Kode Kecamatan (otomatis)" readonly></div>
                <div class="col-8"><input class="form-control" name="nama_kecamatan" placeholder="Nama" required></div>
                <div class="col-12">
                    <div
                        class="sig-map sig-map-sm"
                        data-map-picker
                        data-lat-input="kecamatan_centroid_lat"
                        data-lng-input="kecamatan_centroid_lng"
                        data-address-input="kecamatan_map_address"
                        data-address-text="kecamatan_map_address"
                        data-click-style="entity:kecamatan"
                        data-center-lat="-1.25"
                        data-center-lng="123.23"
                        data-zoom="9"
                        data-styles='@json($mapStyles)'
                        data-markers='@json($kecamatanMarkers)'
                    ></div>
                </div>
                <div class="col-6"><input id="kecamatan_centroid_lat" class="form-control" name="centroid_lat" placeholder="Latitude"></div>
                <div class="col-6"><input id="kecamatan_centroid_lng" class="form-control" name="centroid_lng" placeholder="Longitude"></div>
                <div class="col-12"><input id="kecamatan_map_address" class="form-control" name="alamat" placeholder="Alamat titik peta (otomatis saat klik peta, bisa diisi manual jika gagal)"></div>
                <div class="col-12 form-check ms-1"><input class="form-check-input" type="checkbox" name="is_active" value="1" checked> <label class="form-check-label">Aktif</label></div>
                <div class="col-12"><button class="btn btn-success btn-sm">Simpan Kecamatan</button></div>
            </form>
        </div>

        <div class="table-responsive panel-card p-2">
            <table class="table table-sm align-middle mb-0" data-dt-server="true" data-dt-source="admin_kecamatan" data-dt-page-length="10">
                <thead>
                    <tr>
                        <th data-col="kode_kecamatan">Kode</th>
                        <th data-col="nama_kecamatan">Nama</th>
                        <th data-col="centroid_lat">Latitude</th>
                        <th data-col="centroid_lng">Longitude</th>
                        <th data-col="is_active">Status</th>
                        <th data-col="actions" data-orderable="false" data-searchable="false">Aksi</th>
                    </tr>
                </thead>
                <tbody></tbody>
            </table>
        </div>
    </div>

    <div class="col-lg-7">
        <div class="panel-card p-3 mb-3">
            <h6 class="fw-semibold">Tambah Desa</h6>
            <form method="POST" action="{{ route('admin.wilayah.desa.store') }}" class="row g-2">
                @csrf
                <div class="col-md-4">
                    <select
                        class="form-select"
                        name="kecamatan_id"
                        required
                        data-dynamic-source-url="{{ route('admin.wilayah.kecamatan.options') }}"
                        data-placeholder="Pilih kecamatan"
                    >
                        <option value="">Pilih kecamatan</option>
                        @foreach($kecamatan as $k)
                            <option value="{{ $k->id }}">{{ $k->nama_kecamatan }}</option>
                        @endforeach
                    </select>
                </div>
                <div class="col-md-3"><input class="form-control" placeholder="Kode Desa (otomatis)" readonly></div>
                <div class="col-md-5"><input class="form-control" name="nama_desa" placeholder="Nama Desa" required></div>
                <div class="col-12">
                    <div
                        class="sig-map sig-map-sm"
                        data-map-picker
                        data-lat-input="desa_centroid_lat"
                        data-lng-input="desa_centroid_lng"
                        data-address-input="desa_map_address"
                        data-address-text="desa_map_address"
                        data-click-style="entity:desa"
                        data-center-lat="-1.25"
                        data-center-lng="123.23"
                        data-zoom="9"
                        data-styles='@json($mapStyles)'
                        data-markers='@json($desaMarkers)'
                    ></div>
                </div>
                <div class="col-md-6"><input id="desa_centroid_lat" class="form-control" name="centroid_lat" placeholder="Latitude"></div>
                <div class="col-md-6"><input id="desa_centroid_lng" class="form-control" name="centroid_lng" placeholder="Longitude"></div>
                <div class="col-12"><input id="desa_map_address" class="form-control" name="alamat" placeholder="Alamat titik peta (otomatis saat klik peta, bisa diisi manual jika gagal)"></div>
                <div class="col-12 form-check ms-1"><input class="form-check-input" type="checkbox" name="is_active" value="1" checked> <label class="form-check-label">Aktif</label></div>
                <div class="col-12"><button class="btn btn-success btn-sm">Simpan Desa</button></div>
            </form>
        </div>

        <div class="table-responsive panel-card p-2">
            <table class="table table-sm align-middle mb-0" data-dt-server="true" data-dt-source="admin_desa" data-dt-page-length="10">
                <thead>
                    <tr>
                        <th data-col="nama_kecamatan">Kecamatan</th>
                        <th data-col="kode_desa">Kode</th>
                        <th data-col="nama_desa">Nama Desa</th>
                        <th data-col="centroid_lat">Latitude</th>
                        <th data-col="centroid_lng">Longitude</th>
                        <th data-col="is_active">Status</th>
                        <th data-col="actions" data-orderable="false" data-searchable="false">Aksi</th>
                    </tr>
                </thead>
                <tbody></tbody>
            </table>
        </div>
    </div>
</div>
@endsection
