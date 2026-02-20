@extends('layouts.panel', [
    'pageTitle' => 'Unduh Dokumen',
    'pageSubtitle' => 'Ekspor data produksi dan laporan kecamatan.',
])

@section('panel_content')
<div class="row g-3 mb-3">
    <div class="col-md-4">
        <div class="panel-card p-3">
            <h6 class="fw-semibold">Unduh Produksi CSV</h6>
            <p class="text-muted small">Ekspor seluruh data produksi panen ke file CSV.</p>
            <a href="{{ route('pimpinan.unduh.produksi_csv') }}" class="btn btn-success btn-sm">Unduh Produksi</a>
        </div>
    </div>
    <div class="col-md-8">
        <div class="panel-card p-3">
            <h6 class="fw-semibold">Unduh Detail Laporan Kecamatan</h6>
            <form method="GET" action="{{ route('pimpinan.unduh.laporan_csv') }}" class="row g-2 align-items-end">
                <div class="col-md-8">
                    <label class="form-label">Pilih Laporan</label>
                    <select class="form-select" name="laporan_id" required>
                        <option value="">Pilih laporan</option>
                        @foreach($laporan as $l)
                            <option value="{{ $l->id }}">{{ sprintf('%02d',$l->bulan) }}/{{ $l->tahun }} - {{ $l->jenis_laporan }}</option>
                        @endforeach
                    </select>
                </div>
                <div class="col-md-4">
                    <div class="d-flex gap-2">
                        <button class="btn btn-success" formaction="{{ route('pimpinan.unduh.laporan_csv') }}" formmethod="GET">Unduh CSV</button>
                        <button class="btn btn-danger" formaction="{{ route('pimpinan.unduh.laporan_pdf') }}" formmethod="GET">Unduh PDF</button>
                    </div>
                </div>
            </form>
        </div>
    </div>
</div>

<div class="table-responsive panel-card p-2">
    <table class="table table-sm align-middle mb-0" data-dt-server="true" data-dt-source="pimpinan_unduh_laporan" data-dt-page-length="10">
        <thead>
            <tr>
                <th data-col="periode">Periode</th>
                <th data-col="jenis_laporan">Jenis Laporan</th>
                <th data-col="file_url">Dokumen</th>
                <th data-col="actions" data-orderable="false" data-searchable="false">Aksi</th>
            </tr>
        </thead>
        <tbody></tbody>
    </table>
</div>
@endsection
