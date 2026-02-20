@extends('layouts.panel', [
    'pageTitle' => 'Data Penyuluh Kecamatan',
    'pageSubtitle' => 'CRUD penyuluh dan penetapan tugas penyuluhan per petani.',
])

@section('panel_content')
<div class="row g-3">
    <div class="col-lg-5">
        <div class="panel-card p-3">
            <h6 class="fw-semibold">Tambah Penyuluh</h6>
            <div class="small text-muted mb-2">Input penyuluh baru akan otomatis membuat akun login role penyuluh.</div>
            <form method="POST" action="{{ route('kecamatan.penyuluh.store') }}" class="row g-2" enctype="multipart/form-data">
                @csrf
                <div class="col-12"><input class="form-control" name="name" placeholder="Nama Penyuluh" required></div>
                <div class="col-12"><input class="form-control" type="email" name="email" placeholder="Email login penyuluh" required></div>
                <div class="col-12">
                    <select class="form-select" name="balai_id" required>
                        <option value="">Pilih balai</option>
                        @foreach($balai as $b)
                            <option value="{{ $b->id }}">{{ $b->nama_balai }}</option>
                        @endforeach
                    </select>
                </div>
                <div class="col-6"><input class="form-control" type="password" name="password" placeholder="Password" required></div>
                <div class="col-6"><input class="form-control" type="password" name="password_confirmation" placeholder="Konfirmasi Password" required></div>
                <div class="col-6"><input class="form-control" name="nip" placeholder="NIP"></div>
                <div class="col-6"><input class="form-control" name="jabatan" placeholder="Jabatan"></div>
                <div class="col-12"><input class="form-control" name="lokasi_penugasan" placeholder="Lokasi penugasan"></div>
                <div class="col-12"><input class="form-control" name="tugas_tambahan" placeholder="Tugas tambahan"></div>
                <div class="col-12">
                    <input class="form-control" type="file" name="foto_penyuluh" accept="image/*" required>
                    <small class="text-muted">Foto profil penyuluh maksimal 5MB.</small>
                </div>
                <div class="col-12 form-check ms-1"><input class="form-check-input" type="checkbox" name="is_active" value="1" checked> <label class="form-check-label">Aktif</label></div>
                <div class="col-12"><button class="btn btn-success btn-sm">Simpan</button></div>
            </form>
        </div>
    </div>
    <div class="col-lg-7">
        <div class="table-responsive panel-card p-2">
            <table class="table table-sm align-middle mb-0" data-dt-server="true" data-dt-source="kecamatan_penyuluh" data-dt-page-length="10">
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

<div class="row g-3 mt-1">
    <div class="col-lg-5">
        <div class="panel-card p-3">
            <h6 class="fw-semibold">Penugasan Penyuluh ke Petani</h6>
            <div class="small text-muted mb-2">Satu penyuluh dapat ditugaskan ke lebih dari satu petani/lahan.</div>
            <form method="POST" action="{{ route('kecamatan.penyuluh.penugasan.store') }}" class="row g-2">
                @csrf
                <div class="col-12">
                    <select class="form-select" name="penyuluh_id" required data-dynamic-source-url="{{ route('kecamatan.penyuluh.options') }}" data-placeholder="Pilih penyuluh">
                        <option value="">Pilih penyuluh</option>
                        @foreach($penyuluhOptions as $item)
                            <option value="{{ $item->id }}">{{ $item->nama_penyuluh }} ({{ $item->nama_balai }})</option>
                        @endforeach
                    </select>
                </div>
                <div class="col-12">
                    <select class="form-select" name="lahan_id" required data-dynamic-source-url="{{ route('kecamatan.penyuluh.lahan.options') }}" data-placeholder="Pilih petani/lahan">
                        <option value="">Pilih petani/lahan</option>
                        @foreach($lahanOptions as $item)
                            <option value="{{ $item->id }}">{{ $item->nama_petani }} - {{ $item->nama_desa }} ({{ $item->luas_ha }} ha)</option>
                        @endforeach
                    </select>
                </div>
                <div class="col-6"><input class="form-control" type="date" name="tanggal_mulai" required></div>
                <div class="col-6"><input class="form-control" type="date" name="tanggal_selesai"></div>
                <div class="col-12">
                    <select class="form-select" name="status_penugasan" required>
                        <option value="aktif" selected>Aktif</option>
                        <option value="selesai">Selesai</option>
                        <option value="dibatalkan">Dibatalkan</option>
                    </select>
                </div>
                <div class="col-12"><button class="btn btn-success btn-sm">Simpan Penugasan</button></div>
            </form>
        </div>
    </div>
    <div class="col-lg-7">
        <div class="table-responsive panel-card p-2">
            <table class="table table-sm align-middle mb-0" data-dt-server="true" data-dt-source="kecamatan_penugasan" data-dt-page-length="10">
                <thead>
                    <tr>
                        <th data-col="nama_penyuluh">Penyuluh</th>
                        <th data-col="nama_petani">Petani</th>
                        <th data-col="nama_desa">Desa</th>
                        <th data-col="tanggal_mulai">Mulai</th>
                        <th data-col="status_penugasan">Status</th>
                        <th data-col="actions" data-orderable="false" data-searchable="false">Aksi</th>
                    </tr>
                </thead>
                <tbody></tbody>
            </table>
        </div>
    </div>
</div>
@endsection
