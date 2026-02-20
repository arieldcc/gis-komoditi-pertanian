@extends('layouts.panel', [
    'pageTitle' => 'Style Ikon Peta',
    'pageSubtitle' => 'Atur ikon marker untuk kecamatan, desa, balai, dan komoditas.',
])

@section('panel_content')
<div class="panel-card p-3 mb-3">
    <h6 class="fw-semibold mb-3">Style Entitas</h6>
    <div class="row g-3">
        @foreach($entityStyles as $entityKey => $style)
            <div class="col-lg-6">
                <form method="POST" action="{{ route('admin.map_style.entity.upsert', $entityKey) }}" class="border rounded p-3 h-100">
                    @csrf
                    <div class="d-flex align-items-center justify-content-between mb-2">
                        <strong>{{ $style['label'] }}</strong>
                        <span class="sig-map-marker-preview" style="--sig-size: {{ $style['size'] }}px; --sig-icon-color: {{ $style['icon_color'] }}; --sig-bg-color: {{ $style['bg_color'] }};">
                            {{ $style['icon_symbol'] }}
                        </span>
                    </div>
                    <input type="hidden" name="label" value="{{ $style['label'] }}">
                    <div class="row g-2">
                        <div class="col-4">
                            <label class="form-label form-label-sm">Simbol</label>
                            <input class="form-control form-control-sm" name="icon_symbol" maxlength="12" value="{{ $style['icon_symbol'] }}" required>
                        </div>
                        <div class="col-4">
                            <label class="form-label form-label-sm">Warna Ikon</label>
                            <input class="form-control form-control-sm form-control-color w-100" type="color" name="icon_color" value="{{ $style['icon_color'] }}" required>
                        </div>
                        <div class="col-4">
                            <label class="form-label form-label-sm">Warna Latar</label>
                            <input class="form-control form-control-sm form-control-color w-100" type="color" name="bg_color" value="{{ $style['bg_color'] }}" required>
                        </div>
                        <div class="col-6">
                            <label class="form-label form-label-sm">Ukuran (px)</label>
                            <input class="form-control form-control-sm" type="number" min="20" max="48" name="size" value="{{ $style['size'] }}" required>
                        </div>
                        <div class="col-6 d-flex align-items-end">
                            <button class="btn btn-success btn-sm w-100">Simpan Style</button>
                        </div>
                    </div>
                </form>
            </div>
        @endforeach
    </div>
</div>

<div class="table-responsive panel-card p-2">
    <table class="table align-middle mb-0" data-dt-server="true" data-dt-source="admin_map_style_komoditas" data-dt-page-length="10">
        <thead>
            <tr>
                <th data-col="nama_komoditas">Komoditas</th>
                <th data-col="icon_symbol">Simbol</th>
                <th data-col="icon_color">Warna Ikon</th>
                <th data-col="bg_color">Warna Latar</th>
                <th data-col="size">Ukuran</th>
                <th data-col="actions" data-orderable="false" data-searchable="false">Aksi</th>
            </tr>
        </thead>
        <tbody></tbody>
    </table>
</div>
@endsection
