@extends('layouts.main')

@section('content')
<section class="hero-gradient py-5">
    <div class="container py-2 py-lg-4">
        <div class="row g-4 align-items-center">
            <div class="col-lg-7">
                <span class="badge rounded-pill text-bg-success-subtle text-success-emphasis px-3 py-2">Kabupaten Banggai Kepulauan</span>
                <h1 class="display-5 fw-bold mt-3 section-title">Sistem Informasi Geografis Komoditas Pertanian</h1>
                <p class="lead text-muted mt-3 mb-4">
                    Platform terintegrasi untuk monitoring lahan, pelaporan penyuluh, verifikasi kecamatan,
                    dan rekap strategis pimpinan dinas secara real-time.
                </p>
                <div class="d-flex flex-wrap gap-2">
                    @auth
                        <a href="{{ route('dashboard') }}" class="btn btn-success btn-lg">Masuk Dashboard</a>
                    @else
                        <a href="{{ route('login') }}" class="btn btn-success btn-lg">Login Sistem</a>
                    @endauth
                    <a href="#fitur" class="btn btn-outline-success btn-lg">Lihat Fitur</a>
                </div>
            </div>
            <div class="col-lg-5">
                <div class="hero-panel rounded-4 p-4 shadow-sm">
                    <h5 class="fw-bold text-success mb-3">Indikator Cepat</h5>
                    <div class="row g-3">
                        <div class="col-6">
                            <div class="stat-box p-3 h-100">
                                <small class="text-muted">Kecamatan Terdata</small>
                                <div class="fs-3 fw-bold text-success" data-counter-target="{{ $statKecamatan ?? 0 }}">0</div>
                            </div>
                        </div>
                        <div class="col-6">
                            <div class="stat-box p-3 h-100">
                                <small class="text-muted">Penyuluh Aktif</small>
                                <div class="fs-3 fw-bold text-success" data-counter-target="{{ $statPenyuluh ?? 0 }}">0</div>
                            </div>
                        </div>
                        <div class="col-6">
                            <div class="stat-box p-3 h-100">
                                <small class="text-muted">Komoditas Utama</small>
                                <div class="fs-3 fw-bold text-success" data-counter-target="{{ $statKomoditas ?? 0 }}">0</div>
                            </div>
                        </div>
                        <div class="col-6">
                            <div class="stat-box p-3 h-100">
                                <small class="text-muted">Akses Laporan</small>
                                <div class="fs-3 fw-bold text-success" data-counter-target="100" data-counter-suffix="%">0%</div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
</section>

<section id="fitur" class="py-5">
    <div class="container">
        <div class="d-flex justify-content-between flex-wrap gap-2 align-items-end mb-4">
            <div>
                <h2 class="section-title mb-1">Fitur Sistem Berdasarkan Level Pengguna</h2>
                <p class="text-muted mb-0">Dirancang sesuai alur kerja dinas pertanian hingga monitoring lapangan.</p>
            </div>
        </div>

        <div id="featureCarousel" class="carousel slide" data-bs-ride="carousel">
            <div class="carousel-indicators">
                <button type="button" data-bs-target="#featureCarousel" data-bs-slide-to="0" class="active" aria-current="true" aria-label="Slide 1"></button>
                <button type="button" data-bs-target="#featureCarousel" data-bs-slide-to="1" aria-label="Slide 2"></button>
                <button type="button" data-bs-target="#featureCarousel" data-bs-slide-to="2" aria-label="Slide 3"></button>
                <button type="button" data-bs-target="#featureCarousel" data-bs-slide-to="3" aria-label="Slide 4"></button>
            </div>
            <div class="carousel-inner rounded-4 overflow-hidden border">
                <div class="carousel-item active">
                    <div class="p-4 p-lg-5 bg-white">
                        <h4 class="text-success fw-bold">Admin Dinas</h4>
                        <p class="mb-0">Akses penuh untuk data master, manajemen user, monitoring global, dan pembentukan laporan resmi.</p>
                    </div>
                </div>
                <div class="carousel-item">
                    <div class="p-4 p-lg-5 bg-white">
                        <h4 class="text-success fw-bold">Admin Kecamatan</h4>
                        <p class="mb-0">Mengelola penyuluh, memvalidasi laporan, dan meneruskan data terverifikasi ke tingkat dinas.</p>
                    </div>
                </div>
                <div class="carousel-item">
                    <div class="p-4 p-lg-5 bg-white">
                        <h4 class="text-success fw-bold">Penyuluh</h4>
                        <p class="mb-0">Input monitoring petani, kendala, kebutuhan, dan produksi dengan dukungan koordinat lokasi.</p>
                    </div>
                </div>
                <div class="carousel-item">
                    <div class="p-4 p-lg-5 bg-white">
                        <h4 class="text-success fw-bold">Pimpinan Dinas</h4>
                        <p class="mb-0">Melihat dashboard strategis dan laporan yang sudah diverifikasi untuk pengambilan kebijakan.</p>
                    </div>
                </div>
            </div>
            <button class="carousel-control-prev" type="button" data-bs-target="#featureCarousel" data-bs-slide="prev">
                <span class="carousel-control-prev-icon"></span>
                <span class="visually-hidden">Previous</span>
            </button>
            <button class="carousel-control-next" type="button" data-bs-target="#featureCarousel" data-bs-slide="next">
                <span class="carousel-control-next-icon"></span>
                <span class="visually-hidden">Next</span>
            </button>
        </div>
    </div>
</section>

<section id="komoditas" class="py-5 bg-white border-top border-bottom">
    <div class="container">
        <h2 class="section-title mb-4">Komoditas Prioritas</h2>
        <div class="row g-3">
            @forelse ($komoditasList as $item)
                <div class="col-md-6 col-xl-3">
                    <div class="commodity-card p-3 h-100">
                        <span class="badge commodity-badge rounded-pill">Komoditas</span>
                        <h5 class="fw-bold mt-3 mb-2">{{ $item->nama_komoditas }}</h5>
                        <p class="text-muted small mb-0">Satuan default: {{ $item->satuan_default ?: '-' }}</p>
                    </div>
                </div>
            @empty
                <div class="col-12">
                    <div class="alert alert-light border mb-0">Belum ada data komoditas aktif.</div>
                </div>
            @endforelse
        </div>

        <div class="panel-card p-3 mt-4">
            <div class="row g-2 align-items-end mb-2">
                <div class="col-lg-5">
                    <label class="form-label mb-1">Filter Titik Berdasarkan Komoditas</label>
                    <select id="landing_komoditas_filter" class="form-select form-select-sm">
                        <option value="">Semua Komoditas</option>
                        @foreach($komoditasList as $item)
                            <option value="{{ $item->id }}">{{ $item->nama_komoditas }}</option>
                        @endforeach
                    </select>
                </div>
                <div class="col-lg-7">
                    <small id="landing_komoditas_filter_count" class="text-muted d-block">Menampilkan seluruh titik komoditas pada peta.</small>
                </div>
            </div>
            <div
                class="sig-map"
                data-map-filter-map="1"
                data-filter-select="landing_komoditas_filter"
                data-filter-field="komoditas_id"
                data-filter-count="landing_komoditas_filter_count"
                data-center-lat="-1.25"
                data-center-lng="123.23"
                data-zoom="9"
                data-styles='@json($mapStyles)'
                data-markers='@json($landingKomoditasMarkers)'
            ></div>
        </div>
    </div>
</section>

<section class="py-5">
    <div class="container">
        <div class="form-wrapper p-4 p-lg-5">
            <div class="row align-items-center g-3">
                <div class="col-lg-8">
                    <h4 class="section-title mb-1">Siap menjalankan sistem?</h4>
                    <p class="text-muted mb-0">Masuk ke aplikasi untuk mulai mengelola data komoditas, user, dan pelaporan lapangan.</p>
                </div>
                <div class="col-lg-4 text-lg-end">
                    <a href="{{ route('login') }}" class="btn btn-success btn-lg px-4">Masuk ke Halaman Login</a>
                </div>
            </div>
        </div>
    </div>
</section>
@endsection
