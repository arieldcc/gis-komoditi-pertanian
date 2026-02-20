@extends('layouts.panel', [
    'pageTitle' => 'Laporan Pimpinan',
    'pageSubtitle' => 'Manajemen periode dan dokumen laporan pimpinan.',
])

@section('panel_content')
<div class="row g-3">
    <div class="col-lg-5">
        <div class="panel-card p-3 mb-3">
            <h6 class="fw-semibold">Tambah Periode</h6>
            <form method="POST" action="{{ route('admin.laporan.periode.store') }}" class="row g-2">
                @csrf
                <div class="col-6"><input class="form-control" type="number" name="bulan" placeholder="Bulan" min="1" max="12" required></div>
                <div class="col-6"><input class="form-control" type="number" name="tahun" placeholder="Tahun" min="2000" max="2100" required></div>
                <div class="col-6"><input class="form-control" type="date" name="tanggal_mulai" required></div>
                <div class="col-6"><input class="form-control" type="date" name="tanggal_selesai" required></div>
                <div class="col-12">
                    <select class="form-select" name="status_periode" required>
                        @foreach(['terbuka','ditutup','arsip'] as $s)
                            <option value="{{ $s }}">{{ ucfirst($s) }}</option>
                        @endforeach
                    </select>
                </div>
                <div class="col-12"><button class="btn btn-success btn-sm">Simpan Periode</button></div>
            </form>
        </div>

        <div class="table-responsive panel-card p-2">
            <table class="table table-sm align-middle mb-0" data-dt-server="true" data-dt-source="admin_laporan_periode" data-dt-page-length="8">
                <thead>
                    <tr>
                        <th data-col="periode">Periode</th>
                        <th data-col="tanggal_mulai">Mulai</th>
                        <th data-col="tanggal_selesai">Selesai</th>
                        <th data-col="status_periode">Status</th>
                        <th data-col="actions" data-orderable="false" data-searchable="false">Aksi</th>
                    </tr>
                </thead>
                <tbody></tbody>
            </table>
        </div>
    </div>

    <div class="col-lg-7">
        <div class="panel-card p-3 mb-3">
            <h6 class="fw-semibold">Buat Laporan Pimpinan</h6>
            <form method="POST" action="{{ route('admin.laporan.store') }}" class="row g-2">
                @csrf
                <div class="col-md-4">
                    <select class="form-select" name="periode_id" required>
                        <option value="">Pilih Periode</option>
                        @foreach($periode as $p)
                            <option value="{{ $p->id }}">{{ sprintf('%02d', $p->bulan) }}/{{ $p->tahun }}</option>
                        @endforeach
                    </select>
                </div>
                <div class="col-md-4"><input class="form-control" name="jenis_laporan" placeholder="Jenis Laporan" required></div>
                <div class="col-md-4"><input class="form-control" name="file_url" placeholder="Path/URL Dokumen" required></div>
                <div class="col-12"><button class="btn btn-success btn-sm">Simpan Laporan</button></div>
            </form>
        </div>

        <div class="table-responsive panel-card p-2 mb-3">
            <table class="table table-sm align-middle mb-0" data-dt-server="true" data-dt-source="admin_laporan_list" data-dt-page-length="8">
                <thead>
                    <tr>
                        <th data-col="generated_at">Generated At</th>
                        <th data-col="jenis_laporan">Jenis</th>
                        <th data-col="periode">Periode</th>
                        <th data-col="generated_by">Oleh</th>
                        <th data-col="file_url">Dokumen</th>
                        <th data-col="actions" data-orderable="false" data-searchable="false">Aksi</th>
                    </tr>
                </thead>
                <tbody></tbody>
            </table>
        </div>

        <div class="table-responsive panel-card p-2">
            <table class="table table-sm align-middle mb-0" data-dt-server="true" data-dt-source="admin_laporan_detail" data-dt-page-length="10">
                <thead>
                    <tr>
                        <th data-col="periode">Periode</th>
                        <th data-col="nama_kecamatan">Kecamatan</th>
                        <th data-col="total_produksi">Produksi</th>
                        <th data-col="total_luas">Luas</th>
                        <th data-col="total_petani">Petani</th>
                        <th data-col="total_lahan">Lahan</th>
                        <th data-col="actions" data-orderable="false" data-searchable="false">Aksi</th>
                    </tr>
                </thead>
                <tbody></tbody>
            </table>
        </div>
    </div>
</div>
@endsection
