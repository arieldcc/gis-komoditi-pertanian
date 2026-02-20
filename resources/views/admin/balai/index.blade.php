@extends('layouts.panel', [
    'pageTitle' => 'Data Balai & Penyuluh',
    'pageSubtitle' => 'CRUD balai penyuluh dan data tenaga penyuluh.',
])

@section('panel_content')
<div class="row g-3">
    <div class="col-lg-6">
        <div class="panel-card p-3 mb-3">
            <h6 class="fw-semibold">Tambah Balai Penyuluh</h6>
            <div class="small text-muted mb-2">Setiap balai otomatis membuat 1 akun admin kecamatan untuk kecamatan yang dipilih.</div>
            <form method="POST" action="{{ route('admin.balai.store') }}" class="row g-2" enctype="multipart/form-data">
                @csrf
                <div class="col-md-6">
                    <select class="form-select" name="kecamatan_id" required>
                        <option value="">Pilih Kecamatan</option>
                        @foreach($kecamatan as $k)
                            <option value="{{ $k->id }}">{{ $k->nama_kecamatan }}</option>
                        @endforeach
                    </select>
                </div>
                <div class="col-md-6"><input class="form-control" name="nama_balai" placeholder="Nama Balai" required></div>
                <div class="col-12"><textarea id="balai_alamat_balai" class="form-control" name="alamat_balai" rows="2" placeholder="Alamat balai"></textarea></div>
                <div class="col-12">
                    <div
                        class="sig-map sig-map-sm"
                        data-map-picker
                        data-lat-input="balai_latitude"
                        data-lng-input="balai_longitude"
                        data-address-input="balai_alamat_balai"
                        data-address-text="balai_map_address"
                        data-click-style="entity:balai"
                        data-center-lat="-1.25"
                        data-center-lng="123.23"
                        data-zoom="9"
                        data-styles='@json($mapStyles)'
                        data-markers='@json($mapMarkers)'
                    ></div>
                </div>
                <div class="col-md-6"><input id="balai_latitude" class="form-control" name="latitude" placeholder="Latitude"></div>
                <div class="col-md-6"><input id="balai_longitude" class="form-control" name="longitude" placeholder="Longitude"></div>
                <div class="col-12"><input id="balai_map_address" class="form-control" placeholder="Alamat titik peta (otomatis saat klik peta)" readonly></div>
                <div class="col-md-8">
                    <input class="form-control" type="file" name="foto_balai" accept="image/*">
                    <small class="text-muted">Foto/gambar balai maksimal 5MB.</small>
                </div>
                <div class="col-md-4 d-flex align-items-center"><div class="form-check"><input class="form-check-input" type="checkbox" name="is_active" value="1" checked><label class="form-check-label">Aktif</label></div></div>
                <div class="col-12"><hr class="my-1"><div class="fw-semibold small text-uppercase text-muted">Profil Akun Admin Kecamatan (Akun Balai)</div></div>
                <div class="col-md-6"><input class="form-control" name="admin_kecamatan_name" placeholder="Nama Admin Kecamatan" required></div>
                <div class="col-md-6"><input class="form-control" type="email" name="admin_kecamatan_email" placeholder="Email Login Admin Kecamatan" required></div>
                <div class="col-md-6"><input class="form-control" type="password" name="admin_kecamatan_password" placeholder="Password Admin Kecamatan" required></div>
                <div class="col-md-6"><input class="form-control" type="password" name="admin_kecamatan_password_confirmation" placeholder="Konfirmasi Password Admin Kecamatan" required></div>
                <div class="col-12"><button class="btn btn-success btn-sm">Simpan Balai</button></div>
            </form>
        </div>

        <div class="table-responsive panel-card p-2">
            <table class="table table-sm align-middle mb-0" data-dt-server="true" data-dt-source="admin_balai" data-dt-page-length="10">
                <thead>
                    <tr>
                        <th data-col="nama_kecamatan">Kecamatan</th>
                        <th data-col="nama_balai">Balai</th>
                        <th data-col="admin_kecamatan_name" data-orderable="false">Admin Kecamatan</th>
                        <th data-col="alamat_balai">Alamat</th>
                        <th data-col="koordinat">Koordinat</th>
                        <th data-col="status">Status</th>
                        <th data-col="actions" data-orderable="false" data-searchable="false">Aksi</th>
                    </tr>
                </thead>
                <tbody></tbody>
            </table>
        </div>
    </div>

    <div class="col-lg-6">
        <div class="panel-card p-3 mb-3">
            <h6 class="fw-semibold">Tambah Penyuluh</h6>
            <div class="small text-muted mb-2">Setiap input penyuluh baru akan otomatis membuat akun login role penyuluh.</div>
            <form method="POST" action="{{ route('admin.balai.penyuluh.store') }}" class="row g-2" enctype="multipart/form-data">
                @csrf
                <div class="col-md-6"><input class="form-control" name="name" placeholder="Nama Penyuluh" required></div>
                <div class="col-md-6"><input class="form-control" type="email" name="email" placeholder="Email login penyuluh" required></div>
                <div class="col-md-6">
                    <select class="form-select" name="balai_id" required>
                        <option value="">Pilih Balai</option>
                        @foreach($balai as $b)
                            <option value="{{ $b->id }}">{{ $b->nama_balai }}</option>
                        @endforeach
                    </select>
                </div>
                <div class="col-md-3"><input class="form-control" type="password" name="password" placeholder="Password" required></div>
                <div class="col-md-3"><input class="form-control" type="password" name="password_confirmation" placeholder="Konfirmasi Password" required></div>
                <div class="col-md-4"><input class="form-control" name="nip" placeholder="NIP"></div>
                <div class="col-md-4"><input class="form-control" name="jabatan" placeholder="Jabatan"></div>
                <div class="col-md-4 d-flex align-items-center"><div class="form-check"><input class="form-check-input" type="checkbox" name="is_active" value="1" checked><label class="form-check-label">Aktif</label></div></div>
                <div class="col-md-6"><input class="form-control" name="lokasi_penugasan" placeholder="Lokasi penugasan"></div>
                <div class="col-md-6"><input class="form-control" name="tugas_tambahan" placeholder="Tugas tambahan"></div>
                <div class="col-12">
                    <input class="form-control" type="file" name="foto_penyuluh" accept="image/*" required>
                    <small class="text-muted">Foto profil penyuluh maksimal 5MB.</small>
                </div>
                <div class="col-12"><button class="btn btn-success btn-sm">Simpan Penyuluh</button></div>
            </form>
        </div>

        <div class="table-responsive panel-card p-2">
            <table class="table table-sm align-middle mb-0" data-dt-server="true" data-dt-source="admin_penyuluh" data-dt-page-length="10">
                <thead>
                    <tr>
                        <th data-col="nama_user">Nama</th>
                        <th data-col="nama_balai">Balai</th>
                        <th data-col="nip">NIP</th>
                        <th data-col="jabatan">Jabatan</th>
                        <th data-col="status">Status</th>
                        <th data-col="actions" data-orderable="false" data-searchable="false">Aksi</th>
                    </tr>
                </thead>
                <tbody></tbody>
            </table>
        </div>
    </div>
</div>
@endsection
