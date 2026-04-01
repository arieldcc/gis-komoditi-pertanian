@extends('layouts.panel', [
    'pageTitle' => 'Penghapusan Menyeluruh',
    'pageSubtitle' => 'Fitur khusus Admin Dinas untuk menghapus data master beserta seluruh data turunannya secara aman.',
])

@section('panel_content')
<div class="panel-card mb-4">
    <div class="panel-card-body">
        <div class="alert alert-danger mb-0">
            <h5 class="alert-heading mb-2">Zona Berbahaya Admin Dinas</h5>
            <p class="mb-2">Fitur ini digunakan untuk <strong>menghapus data master beserta seluruh data turunannya</strong> secara berurutan sampai data induk benar-benar hilang dari sistem.</p>
            <p class="mb-0">Eksekusi dilakukan dari tombol <strong>Hapus Total</strong> pada tabel modul terkait. Sistem akan meminta Anda mengetik <strong>kunci konfirmasi</strong> secara persis sebelum proses dijalankan.</p>
        </div>
    </div>
</div>

<div class="row g-3">
    @foreach($supportedEntities as $entity)
        <div class="col-12 col-xl-6">
            <div class="panel-card h-100">
                <div class="panel-card-body">
                    <div class="d-flex justify-content-between align-items-start gap-3 mb-2">
                        <div>
                            <h5 class="mb-1">{{ $entity['label'] }}</h5>
                            <div class="text-muted small">Akses dari menu {{ $entity['module_label'] }}</div>
                        </div>
                        <a href="{{ route($entity['route']) }}" class="btn btn-outline-danger btn-sm">Buka Modul</a>
                    </div>
                    <p class="mb-0">{{ $entity['description'] }}</p>
                </div>
            </div>
        </div>
    @endforeach
</div>

<div class="panel-card mt-4">
    <div class="panel-card-body">
        <h5 class="mb-2">Langkah Penggunaan</h5>
        <ol class="mb-0 ps-3">
            <li>Buka modul data master yang ingin dihapus.</li>
            <li>Cari baris data target, lalu klik tombol <strong>Hapus Total</strong>.</li>
            <li>Baca dampak penghapusan yang ditampilkan sistem.</li>
            <li>Ketik kunci konfirmasi persis seperti yang ditampilkan.</li>
            <li>Jika kunci sesuai, sistem akan menghapus data anak terlebih dahulu lalu data induknya.</li>
        </ol>
    </div>
</div>
@endsection
