@extends('layouts.panel', [
    'pageTitle' => 'Dashboard Admin Kecamatan',
    'pageSubtitle' => 'Monitoring penyuluh dan verifikasi laporan tingkat kecamatan.',
])

@section('panel_content')
@php
    $cards = $dashboardData['cards'] ?? [];
    $activity = $dashboardData['activity'] ?? [];
@endphp
<div class="row g-3" data-dashboard-endpoint="{{ route('kecamatan.dashboard.data') }}" data-dashboard-refresh-ms="10000">
    @foreach($cards as $card)
        @php
            $rawValue = $card['value'] ?? 0;
            $value = is_numeric($rawValue) ? number_format((float) $rawValue, 0, ',', '.') : $rawValue;
        @endphp
        <div class="col-sm-6 col-xl-3">
            <div class="panel-card p-3 h-100 dashboard-kpi-card" data-card-key="{{ $card['key'] ?? '' }}">
                <small class="text-muted">{{ $card['title'] ?? '-' }}</small>
                <div class="h4 mb-0 mt-2"><span data-card-value>{{ $value }}</span><span data-card-suffix>{{ $card['suffix'] ?? '' }}</span></div>
            </div>
        </div>
    @endforeach

    <div class="col-lg-4">
        <div class="panel-card p-3 h-100">
            <h6 class="fw-semibold mb-2">Status Verifikasi Laporan</h6>
            <div class="dashboard-chart-box" data-chart="pie"></div>
        </div>
    </div>

    <div class="col-lg-8">
        <div class="panel-card p-3 h-100">
            <h6 class="fw-semibold mb-2">Produktivitas Kunjungan per Penyuluh</h6>
            <div class="dashboard-chart-box" data-chart="bar"></div>
        </div>
    </div>

    <div class="col-lg-8">
        <div class="panel-card p-3 h-100">
            <h6 class="fw-semibold mb-2">Candlestick Harga Jual Wilayah Kecamatan</h6>
            <div class="dashboard-chart-box dashboard-chart-candlestick" data-chart="candlestick"></div>
        </div>
    </div>

    <div class="col-lg-4">
        <div class="panel-card p-3 h-100 d-flex flex-column">
            <h6 class="fw-semibold mb-3">Laporan Penyuluh Masuk</h6>
            <div class="dashboard-activity-list flex-grow-1" data-dashboard-activity>
                @foreach($activity as $item)
                    <div class="dashboard-activity-item">
                        <div class="fw-semibold">{{ $item['title'] ?? '-' }}</div>
                        <small class="text-muted d-block">{{ $item['subtitle'] ?? '-' }}</small>
                        <div class="d-flex justify-content-between align-items-center mt-1">
                            <span class="badge rounded-pill text-bg-secondary">{{ strtoupper((string) ($item['status'] ?? '-')) }}</span>
                            <small class="text-muted">{{ $item['time'] ?? '-' }}</small>
                        </div>
                    </div>
                @endforeach
            </div>
            <small class="text-muted mt-2" data-dashboard-updated-at>Terakhir update: {{ $dashboardData['updated_at'] ?? '-' }}</small>
        </div>
    </div>

    <div class="col-12">
        <div class="panel-card p-4">
            <h6 class="fw-semibold mb-2">Fokus Tugas</h6>
            <p class="text-muted mb-0">Validasi laporan penyuluh secara cepat, respon usulan perbaikan data, dan pastikan data komoditas wilayah selalu terkini.</p>
        </div>
    </div>
</div>
@endsection
