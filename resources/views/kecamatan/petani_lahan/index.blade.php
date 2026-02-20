@extends('layouts.panel', [
    'pageTitle' => 'Data Petani & Lahan',
    'pageSubtitle' => 'CRUD petani, lahan, dan komoditas lahan.',
])

@section('panel_content')
<div class="row g-3">
    <div class="col-lg-4">
        <div class="panel-card p-3 mb-3">
            <h6 class="fw-semibold">Tambah Petani</h6>
            <form method="POST" action="{{ route('kecamatan.petani_lahan.petani.store') }}" class="row g-2" enctype="multipart/form-data">
                @csrf
                <div class="col-12"><select class="form-select" name="desa_id" required><option value="">Pilih desa</option>@foreach($desa as $d)<option value="{{ $d->id }}">{{ $d->nama_kecamatan }} - {{ $d->nama_desa }}</option>@endforeach</select></div>
                <div class="col-12"><input class="form-control" name="nama_petani" placeholder="Nama petani" required></div>
                <div class="col-12"><input class="form-control" name="no_hp" placeholder="No HP"></div>
                <div class="col-12">
                    <select class="form-select" name="kelompok_tani" data-dynamic-source-url="{{ route('kecamatan.kelompok_tani.options') }}" data-placeholder="Pilih kelompok tani">
                        <option value="">Pilih kelompok tani</option>
                        @foreach($kelompokTani as $k)
                            <option value="{{ $k->nama_kelompok }}">{{ $k->nama_kelompok }}</option>
                        @endforeach
                    </select>
                </div>
                <div class="col-12">
                    <input class="form-control" type="file" name="foto_petani" accept="image/*" required>
                    <small class="text-muted">Foto profil petani maksimal 5MB.</small>
                </div>
                <div class="col-12"><textarea class="form-control" name="alamat_domisili" rows="2" placeholder="Alamat"></textarea></div>
                <div class="col-12 form-check ms-1"><input class="form-check-input" type="checkbox" name="is_active" value="1" checked> <label class="form-check-label">Aktif</label></div>
                <div class="col-12"><button class="btn btn-success btn-sm">Simpan Petani</button></div>
            </form>
        </div>

        <div class="table-responsive panel-card p-2">
            <table class="table table-sm mb-0" data-dt-server="true" data-dt-source="kecamatan_petani" data-dt-page-length="10">
                <thead>
                    <tr>
                        <th data-col="nama_petani">Petani</th>
                        <th data-col="nama_desa">Desa</th>
                        <th data-col="nama_kecamatan">Kecamatan</th>
                        <th data-col="kelompok_tani">Kelompok Tani</th>
                        <th data-col="no_hp">No HP</th>
                        <th data-col="status">Status</th>
                        <th data-col="actions" data-orderable="false" data-searchable="false">Aksi</th>
                    </tr>
                </thead>
                <tbody></tbody>
            </table>
        </div>
    </div>

    <div class="col-lg-4">
        <div class="panel-card p-3 mb-3">
            <h6 class="fw-semibold">Tambah Lahan</h6>
            <form method="POST" action="{{ route('kecamatan.petani_lahan.lahan.store') }}" class="row g-2" enctype="multipart/form-data">
                @csrf
                <div class="col-12"><select class="form-select" name="petani_id" required><option value="">Pilih petani</option>@foreach($petani as $p)<option value="{{ $p->id }}">{{ $p->nama_petani }}</option>@endforeach</select></div>
                <div class="col-12"><select class="form-select" name="desa_id" required><option value="">Pilih desa</option>@foreach($desa as $d)<option value="{{ $d->id }}">{{ $d->nama_desa }}</option>@endforeach</select></div>
                <div class="col-6"><input class="form-control" name="luas_ha" placeholder="Luas (ha)"></div>
                <div class="col-6"><select class="form-select" name="kondisi_lahan"><option value="">Kondisi</option>@foreach(['baik','sedang','rusak','kritis'] as $k)<option value="{{ $k }}">{{ ucfirst($k) }}</option>@endforeach</select></div>
                <div class="col-12">
                    <div
                        class="sig-map sig-map-sm"
                        data-map-picker
                        data-lat-input="lahan_latitude"
                        data-lng-input="lahan_longitude"
                        data-address-input="lahan_alamat_lahan"
                        data-address-text="lahan_map_address"
                        data-click-style="entity:komoditas_default"
                        data-center-lat="-1.25"
                        data-center-lng="123.23"
                        data-zoom="10"
                        data-styles='@json($mapStyles)'
                        data-markers='@json($lahanMarkers)'
                    ></div>
                </div>
                <div class="col-6"><input id="lahan_latitude" class="form-control" name="latitude" placeholder="Latitude"></div>
                <div class="col-6"><input id="lahan_longitude" class="form-control" name="longitude" placeholder="Longitude"></div>
                <div class="col-12"><input id="lahan_map_address" class="form-control" placeholder="Alamat titik peta (otomatis saat klik peta)" readonly></div>
                <div class="col-12">
                    <input class="form-control" type="file" name="foto_lahan" accept="image/*" required>
                    <small class="text-muted">Foto lahan maksimal 5MB.</small>
                </div>
                <div class="col-12"><textarea id="lahan_alamat_lahan" class="form-control" name="alamat_lahan" rows="2" placeholder="Alamat lahan"></textarea></div>
                <div class="col-12 form-check ms-1"><input class="form-check-input" type="checkbox" name="is_active" value="1" checked> <label class="form-check-label">Aktif</label></div>
                <div class="col-12"><button class="btn btn-success btn-sm">Simpan Lahan</button></div>
            </form>
        </div>

        <div class="table-responsive panel-card p-2">
            <table class="table table-sm mb-0" data-dt-server="true" data-dt-source="kecamatan_lahan" data-dt-page-length="10">
                <thead>
                    <tr>
                        <th data-col="nama_petani">Petani</th>
                        <th data-col="nama_desa">Desa</th>
                        <th data-col="luas_ha">Luas (ha)</th>
                        <th data-col="kondisi_lahan">Kondisi</th>
                        <th data-col="koordinat">Koordinat</th>
                        <th data-col="status">Status</th>
                        <th data-col="actions" data-orderable="false" data-searchable="false">Aksi</th>
                    </tr>
                </thead>
                <tbody></tbody>
            </table>
        </div>
    </div>

    <div class="col-lg-4">
        <div class="panel-card p-3 mb-3">
            <h6 class="fw-semibold">Tambah Lahan Komoditas</h6>
            <form method="POST" action="{{ route('kecamatan.petani_lahan.lahan_komoditas.store') }}" class="row g-2">
                @csrf
                <div class="col-12"><select class="form-select" name="lahan_id" required><option value="">Pilih lahan</option>@foreach($lahan as $l)<option value="{{ $l->id }}">{{ $l->nama_petani }} - {{ $l->nama_desa }}</option>@endforeach</select></div>
                <div class="col-12"><select class="form-select" name="komoditas_id" required><option value="">Pilih komoditas</option>@foreach($komoditas as $k)<option value="{{ $k->id }}">{{ $k->nama_komoditas }}</option>@endforeach</select></div>
                <div class="col-6"><input class="form-control" type="number" name="tahun_tanam" placeholder="Tahun"></div>
                <div class="col-6"><input class="form-control" name="luas_tanam_ha" placeholder="Luas tanam"></div>
                <div class="col-12">
                    <div
                        class="sig-map sig-map-sm"
                        data-map-picker
                        data-lat-input="komoditas_latitude"
                        data-lng-input="komoditas_longitude"
                        data-address-input="komoditas_alamat_titik"
                        data-address-text="komoditas_map_address"
                        data-click-style="entity:komoditas_default"
                        data-center-lat="-1.25"
                        data-center-lng="123.23"
                        data-zoom="10"
                        data-styles='@json($mapStyles)'
                        data-markers='@json($komoditasMarkers)'
                    ></div>
                </div>
                <div class="col-6"><input id="komoditas_latitude" class="form-control" name="latitude" placeholder="Latitude titik komoditas" required></div>
                <div class="col-6"><input id="komoditas_longitude" class="form-control" name="longitude" placeholder="Longitude titik komoditas" required></div>
                <div class="col-12"><input id="komoditas_map_address" class="form-control" placeholder="Alamat titik komoditas (otomatis saat klik peta)" readonly></div>
                <div class="col-12"><textarea id="komoditas_alamat_titik" class="form-control" name="alamat_titik" rows="2" placeholder="Alamat titik komoditas (bisa diisi manual)"></textarea></div>
                <div class="col-12"><select class="form-select" name="status_tanam" required>@foreach(['rencana','tanam','panen','bera','gagal'] as $s)<option value="{{ $s }}">{{ ucfirst($s) }}</option>@endforeach</select></div>
                <div class="col-12"><textarea class="form-control" name="catatan" rows="2" placeholder="Catatan"></textarea></div>
                <div class="col-12"><button class="btn btn-success btn-sm">Simpan</button></div>
            </form>
        </div>

        <div class="panel-card p-3 mb-3">
            <h6 class="fw-semibold">Peta Sebaran Komoditas</h6>
            <div class="row g-2">
                <div class="col-12">
                    <label class="form-label mb-1">Filter Komoditas</label>
                    <select id="kecamatan_komoditas_filter" class="form-select form-select-sm">
                        <option value="">Semua Komoditas</option>
                        @foreach($komoditas as $k)
                            <option value="{{ $k->id }}">{{ $k->nama_komoditas }}</option>
                        @endforeach
                    </select>
                </div>
                <div class="col-12">
                    <small id="kecamatan_komoditas_filter_count" class="text-muted d-block mb-2">Menampilkan seluruh titik komoditas.</small>
                    <div
                        class="sig-map sig-map-sm"
                        data-map-filter-map="1"
                        data-filter-select="kecamatan_komoditas_filter"
                        data-filter-field="komoditas_id"
                        data-filter-count="kecamatan_komoditas_filter_count"
                        data-center-lat="-1.25"
                        data-center-lng="123.23"
                        data-zoom="10"
                        data-styles='@json($mapStyles)'
                        data-markers='@json($komoditasMarkers)'
                    ></div>
                </div>
            </div>
        </div>

        <div class="table-responsive panel-card p-2">
            <table class="table table-sm mb-0" data-dt-server="true" data-dt-source="kecamatan_lahan_komoditas" data-dt-page-length="10">
                <thead>
                    <tr>
                        <th data-col="nama_petani">Petani</th>
                        <th data-col="nama_komoditas">Komoditas</th>
                        <th data-col="tahun_tanam">Tahun</th>
                        <th data-col="luas_tanam_ha">Luas Tanam</th>
                        <th data-col="koordinat">Koordinat Titik</th>
                        <th data-col="status_tanam">Status</th>
                        <th data-col="actions" data-orderable="false" data-searchable="false">Aksi</th>
                    </tr>
                </thead>
                <tbody></tbody>
            </table>
        </div>
    </div>
</div>
@endsection
