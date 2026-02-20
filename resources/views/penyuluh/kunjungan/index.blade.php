@extends('layouts.panel', [
    'pageTitle' => 'Kunjungan Terpadu Penyuluh',
    'pageSubtitle' => 'Satu form kunjungan untuk input monitoring, produksi, kendala, kebutuhan, dan usulan koreksi data.',
])

@section('panel_content')
@if(!$penyuluhId)
    <div class="alert alert-warning">Profil penyuluh belum tersedia.</div>
@else
@if($errors->any())
    <div class="alert alert-danger">
        Validasi gagal. Field bermasalah ditandai warna merah. Data input tetap dipertahankan agar tidak perlu input ulang.
    </div>
@endif
<div class="panel-card p-3 mb-3">
    <h6 class="fw-semibold mb-3">Tambah Kunjungan Terpadu</h6>

    <form
        method="POST"
        action="{{ route('penyuluh.kunjungan.store') }}"
        enctype="multipart/form-data"
        class="row g-3"
        id="kunjungan-terpadu-form"
        data-detail-url-template="{{ route('penyuluh.kunjungan.detail', ['id' => '__ID__']) }}"
        data-old-produksi-komoditas="{{ old('produksi_lahan_komoditas_id') }}"
        data-old-koreksi-komoditas="{{ old('koreksi_komoditas_id') }}"
        data-has-old-corrections="{{ old('koreksi_lahan_latitude') || old('koreksi_lahan_longitude') || old('koreksi_lahan_luas_ha') || old('koreksi_lahan_kondisi_lahan') || old('koreksi_lahan_alamat_lahan') || old('koreksi_komoditas_latitude') || old('koreksi_komoditas_longitude') || old('koreksi_komoditas_luas_tanam_ha') || old('koreksi_komoditas_status_tanam') || old('koreksi_komoditas_alamat_titik') ? '1' : '0' }}"
    >
        @csrf

        <div class="col-lg-4">
            <label class="form-label">Penugasan Petani</label>
            <select class="form-select @error('penugasan_id') is-invalid @enderror" name="penugasan_id" id="penugasan_id" required>
                <option value="">Pilih petani penugasan</option>
                @foreach($penugasan as $p)
                    <option value="{{ $p->id }}" @selected((string) old('penugasan_id') === (string) $p->id)>{{ $p->nama_petani }} - {{ $p->nama_desa }}</option>
                @endforeach
            </select>
            @error('penugasan_id')<div class="invalid-feedback">{{ $message }}</div>@enderror
        </div>

        <div class="col-lg-4">
            <label class="form-label">Tanggal Kunjungan</label>
            <input class="form-control @error('tanggal_kunjungan') is-invalid @enderror" type="datetime-local" name="tanggal_kunjungan" value="{{ old('tanggal_kunjungan') }}" required>
            @error('tanggal_kunjungan')<div class="invalid-feedback">{{ $message }}</div>@enderror
        </div>

        <div class="col-lg-4">
            <label class="form-label">Status Laporan</label>
            <select class="form-select @error('status_verifikasi') is-invalid @enderror" name="status_verifikasi" required>
                @foreach(['draft','menunggu','revisi'] as $s)
                    <option value="{{ $s }}" @selected(old('status_verifikasi', 'menunggu') === $s)>{{ ucfirst($s) }}</option>
                @endforeach
            </select>
            @error('status_verifikasi')<div class="invalid-feedback">{{ $message }}</div>@enderror
        </div>

        <div class="col-12">
            <div class="border rounded p-3 bg-light-subtle">
                <div class="d-flex justify-content-between align-items-center mb-2">
                    <h6 class="mb-0">Data Target Kunjungan (Otomatis)</h6>
                    <small class="text-muted">Data diambil otomatis dari petani/lahan/komoditas terpilih</small>
                </div>

                <div class="row g-3">
                    <div class="col-xl-4">
                        <div class="small text-muted mb-1">Profil Petani</div>
                        <div id="detail-petani" class="small">
                            <div class="text-muted">Pilih penugasan untuk menampilkan data petani.</div>
                        </div>
                    </div>
                    <div class="col-xl-4">
                        <div class="small text-muted mb-1">Profil Lahan</div>
                        <div id="detail-lahan" class="small">
                            <div class="text-muted">Pilih penugasan untuk menampilkan data lahan.</div>
                        </div>
                    </div>
                    <div class="col-xl-4">
                        <div class="small text-muted mb-1">Komoditas pada Lahan</div>
                        <div id="detail-komoditas" class="small">
                            <div class="text-muted">Belum ada data komoditas.</div>
                        </div>
                    </div>

                    <div class="col-12">
                        <div id="kunjungan-target-map" class="sig-map sig-map-sm"></div>
                    </div>
                </div>
            </div>
        </div>

        <div class="col-md-4">
            <label class="form-label">Kondisi Tanaman</label>
            <textarea class="form-control @error('kondisi_tanaman') is-invalid @enderror" name="kondisi_tanaman" rows="3" placeholder="Ringkasan kondisi tanaman saat kunjungan">{{ old('kondisi_tanaman') }}</textarea>
            @error('kondisi_tanaman')<div class="invalid-feedback">{{ $message }}</div>@enderror
        </div>
        <div class="col-md-4">
            <label class="form-label">Catatan Umum</label>
            <textarea class="form-control @error('catatan_umum') is-invalid @enderror" name="catatan_umum" rows="3" placeholder="Catatan lapangan umum">{{ old('catatan_umum') }}</textarea>
            @error('catatan_umum')<div class="invalid-feedback">{{ $message }}</div>@enderror
        </div>
        <div class="col-md-4">
            <label class="form-label">Rekomendasi Penyuluh</label>
            <textarea class="form-control @error('rekomendasi') is-invalid @enderror" name="rekomendasi" rows="3" placeholder="Saran tindak lanjut">{{ old('rekomendasi') }}</textarea>
            @error('rekomendasi')<div class="invalid-feedback">{{ $message }}</div>@enderror
        </div>

        <div class="col-md-6">
            <label class="form-label">Foto Kunjungan (Wajib, max 5MB)</label>
            <input class="form-control @error('foto_kunjungan') is-invalid @enderror" type="file" name="foto_kunjungan" accept=".jpg,.jpeg,.png,.webp" required>
            @error('foto_kunjungan')<div class="invalid-feedback">{{ $message }}</div>@enderror
        </div>

        <div class="col-12">
            <div class="border rounded p-3">
                <h6 class="mb-2">Input Produksi (Opsional)</h6>
                <div class="row g-2">
                    <div class="col-md-4">
                        <label class="form-label">Komoditas Lahan</label>
                        <select class="form-select @error('produksi_lahan_komoditas_id') is-invalid @enderror" name="produksi_lahan_komoditas_id" id="produksi_lahan_komoditas_id">
                            <option value="">Pilih komoditas</option>
                        </select>
                        @error('produksi_lahan_komoditas_id')<div class="invalid-feedback">{{ $message }}</div>@enderror
                    </div>
                    <div class="col-md-4">
                        <label class="form-label">Periode Laporan</label>
                        <select class="form-select @error('produksi_periode_id') is-invalid @enderror" name="produksi_periode_id">
                            <option value="">Otomatis periode terbuka</option>
                            @foreach($periodeLaporan as $periode)
                                <option value="{{ $periode->id }}" @selected((string) old('produksi_periode_id') === (string) $periode->id)>{{ sprintf('%02d/%d', $periode->bulan, $periode->tahun) }} ({{ ucfirst($periode->status_periode) }})</option>
                            @endforeach
                        </select>
                        @error('produksi_periode_id')<div class="invalid-feedback">{{ $message }}</div>@enderror
                    </div>
                    <div class="col-md-4">
                        <label class="form-label">Tanggal Panen</label>
                        <input class="form-control @error('produksi_tanggal_panen') is-invalid @enderror" type="date" name="produksi_tanggal_panen" value="{{ old('produksi_tanggal_panen') }}">
                        @error('produksi_tanggal_panen')<div class="invalid-feedback">{{ $message }}</div>@enderror
                    </div>
                    <div class="col-md-3">
                        <label class="form-label">Jumlah Produksi</label>
                        <input class="form-control @error('produksi_jumlah_produksi') is-invalid @enderror" type="number" step="0.01" min="0" name="produksi_jumlah_produksi" value="{{ old('produksi_jumlah_produksi') }}" placeholder="contoh: 1200">
                        @error('produksi_jumlah_produksi')<div class="invalid-feedback">{{ $message }}</div>@enderror
                    </div>
                    <div class="col-md-3">
                        <label class="form-label">Produktivitas (kg/ha)</label>
                        <input class="form-control @error('produksi_produktivitas_kg_ha') is-invalid @enderror" type="number" step="0.01" min="0" name="produksi_produktivitas_kg_ha" value="{{ old('produksi_produktivitas_kg_ha') }}">
                        @error('produksi_produktivitas_kg_ha')<div class="invalid-feedback">{{ $message }}</div>@enderror
                    </div>
                    <div class="col-md-3">
                        <label class="form-label">Harga Jual</label>
                        <input class="form-control @error('produksi_harga_jual') is-invalid @enderror" type="number" step="0.01" min="0" name="produksi_harga_jual" value="{{ old('produksi_harga_jual') }}">
                        @error('produksi_harga_jual')<div class="invalid-feedback">{{ $message }}</div>@enderror
                    </div>
                    <div class="col-md-3">
                        <label class="form-label">Catatan Produksi</label>
                        <input class="form-control @error('produksi_catatan') is-invalid @enderror" name="produksi_catatan" value="{{ old('produksi_catatan') }}">
                        @error('produksi_catatan')<div class="invalid-feedback">{{ $message }}</div>@enderror
                    </div>
                </div>
            </div>
        </div>

        <div class="col-12">
            <div class="border rounded p-3">
                <h6 class="mb-2">Kendala & Kebutuhan (Opsional)</h6>
                <div class="row g-2">
                    <div class="col-md-3">
                        <label class="form-label">Kategori Kendala</label>
                        <select class="form-select @error('kendala_kategori_id') is-invalid @enderror" name="kendala_kategori_id">
                            <option value="">Pilih kategori</option>
                            @foreach($kategoriKendala as $kategori)
                                <option value="{{ $kategori->id }}" @selected((string) old('kendala_kategori_id') === (string) $kategori->id)>{{ $kategori->nama_kategori }}</option>
                            @endforeach
                        </select>
                        @error('kendala_kategori_id')<div class="invalid-feedback">{{ $message }}</div>@enderror
                    </div>
                    <div class="col-md-3">
                        <label class="form-label">Tingkat Keparahan</label>
                        <select class="form-select @error('kendala_tingkat_keparahan') is-invalid @enderror" name="kendala_tingkat_keparahan">
                            <option value="">Pilih</option>
                            @foreach(['rendah','sedang','tinggi','kritis'] as $level)
                                <option value="{{ $level }}" @selected(old('kendala_tingkat_keparahan') === $level)>{{ ucfirst($level) }}</option>
                            @endforeach
                        </select>
                        @error('kendala_tingkat_keparahan')<div class="invalid-feedback">{{ $message }}</div>@enderror
                    </div>
                    <div class="col-md-3">
                        <label class="form-label">Perlu Tindak Lanjut</label>
                        <select class="form-select @error('kendala_perlu_tindak_lanjut') is-invalid @enderror" name="kendala_perlu_tindak_lanjut">
                            <option value="1" @selected((string) old('kendala_perlu_tindak_lanjut', '1') === '1')>Ya</option>
                            <option value="0" @selected((string) old('kendala_perlu_tindak_lanjut') === '0')>Tidak</option>
                        </select>
                        @error('kendala_perlu_tindak_lanjut')<div class="invalid-feedback">{{ $message }}</div>@enderror
                    </div>
                    <div class="col-md-3">
                        <label class="form-label">Deskripsi Kendala</label>
                        <input class="form-control @error('kendala_deskripsi') is-invalid @enderror" name="kendala_deskripsi" value="{{ old('kendala_deskripsi') }}" placeholder="contoh: serangan hama meningkat">
                        @error('kendala_deskripsi')<div class="invalid-feedback">{{ $message }}</div>@enderror
                    </div>

                    <div class="col-md-6">
                        <label class="form-label">Foto Kendala (max 5MB)</label>
                        <input class="form-control @error('foto_kendala') is-invalid @enderror" type="file" name="foto_kendala" accept=".jpg,.jpeg,.png,.webp">
                        <small class="text-muted">Wajib jika Anda mengisi data kendala.</small>
                        @error('foto_kendala')<div class="invalid-feedback">{{ $message }}</div>@enderror
                    </div>

                    <div class="col-md-3">
                        <label class="form-label">Kategori Kebutuhan</label>
                        <select class="form-select @error('kebutuhan_kategori_id') is-invalid @enderror" name="kebutuhan_kategori_id">
                            <option value="">Pilih kategori</option>
                            @foreach($kategoriKebutuhan as $kategori)
                                <option value="{{ $kategori->id }}" @selected((string) old('kebutuhan_kategori_id') === (string) $kategori->id)>{{ $kategori->nama_kategori }}</option>
                            @endforeach
                        </select>
                        @error('kebutuhan_kategori_id')<div class="invalid-feedback">{{ $message }}</div>@enderror
                    </div>
                    <div class="col-md-3">
                        <label class="form-label">Prioritas Kebutuhan</label>
                        <select class="form-select @error('kebutuhan_prioritas') is-invalid @enderror" name="kebutuhan_prioritas">
                            <option value="">Pilih</option>
                            @foreach(['rendah','sedang','tinggi'] as $prioritas)
                                <option value="{{ $prioritas }}" @selected(old('kebutuhan_prioritas') === $prioritas)>{{ ucfirst($prioritas) }}</option>
                            @endforeach
                        </select>
                        @error('kebutuhan_prioritas')<div class="invalid-feedback">{{ $message }}</div>@enderror
                    </div>
                    <div class="col-md-2">
                        <label class="form-label">Jumlah</label>
                        <input class="form-control @error('kebutuhan_jumlah') is-invalid @enderror" type="number" step="0.01" min="0" name="kebutuhan_jumlah" value="{{ old('kebutuhan_jumlah') }}">
                        @error('kebutuhan_jumlah')<div class="invalid-feedback">{{ $message }}</div>@enderror
                    </div>
                    <div class="col-md-2">
                        <label class="form-label">Satuan</label>
                        <input class="form-control @error('kebutuhan_satuan') is-invalid @enderror" name="kebutuhan_satuan" value="{{ old('kebutuhan_satuan') }}" placeholder="kg/liter/unit">
                        @error('kebutuhan_satuan')<div class="invalid-feedback">{{ $message }}</div>@enderror
                    </div>
                    <div class="col-md-2">
                        <label class="form-label">Deskripsi Kebutuhan</label>
                        <input class="form-control @error('kebutuhan_deskripsi') is-invalid @enderror" name="kebutuhan_deskripsi" value="{{ old('kebutuhan_deskripsi') }}" placeholder="contoh: pupuk NPK">
                        @error('kebutuhan_deskripsi')<div class="invalid-feedback">{{ $message }}</div>@enderror
                    </div>
                </div>
            </div>
        </div>

        <div class="col-12 d-flex justify-content-end">
            <button class="btn btn-success" type="submit">Simpan Kunjungan Terpadu</button>
        </div>
    </form>
</div>

<div class="panel-card p-3 mb-3">
    <h6 class="fw-semibold mb-3">Pengajuan Perbaikan Data Lapangan</h6>
    <small class="text-muted d-block mb-3">Gunakan form ini jika hanya ingin memperbaiki data lapangan tanpa membuat laporan kunjungan.</small>

    <form method="POST" action="{{ route('penyuluh.kunjungan.perbaikan.store') }}" class="row g-3">
        @csrf

        <div class="col-lg-4">
            <label class="form-label">Penugasan Petani</label>
            <select class="form-select @error('penugasan_id') is-invalid @enderror" name="penugasan_id" id="penugasan_id_perbaikan" required>
                <option value="">Pilih petani penugasan</option>
                @foreach($penugasan as $p)
                    <option value="{{ $p->id }}" @selected((string) old('penugasan_id') === (string) $p->id)>{{ $p->nama_petani }} - {{ $p->nama_desa }}</option>
                @endforeach
            </select>
            @error('penugasan_id')<div class="invalid-feedback">{{ $message }}</div>@enderror
        </div>

        <div class="col-lg-4">
            <label class="form-label">Waktu Pengajuan</label>
            <input class="form-control @error('waktu_pengajuan') is-invalid @enderror" type="datetime-local" name="waktu_pengajuan" value="{{ old('waktu_pengajuan', now()->format('Y-m-d\\TH:i')) }}" required>
            @error('waktu_pengajuan')<div class="invalid-feedback">{{ $message }}</div>@enderror
        </div>

        <div class="col-12">
            <div class="border rounded p-3 bg-warning-subtle">
                <div class="row g-2">
                    <div class="col-12"><strong class="small">Koreksi Data Lahan</strong></div>
                    <div class="col-md-2">
                        <label class="form-label">Latitude Lahan</label>
                        <input class="form-control @error('koreksi_lahan_latitude') is-invalid @enderror" type="number" step="0.0000001" name="koreksi_lahan_latitude" id="koreksi_lahan_latitude" value="{{ old('koreksi_lahan_latitude') }}">
                        @error('koreksi_lahan_latitude')<div class="invalid-feedback">{{ $message }}</div>@enderror
                    </div>
                    <div class="col-md-2">
                        <label class="form-label">Longitude Lahan</label>
                        <input class="form-control @error('koreksi_lahan_longitude') is-invalid @enderror" type="number" step="0.0000001" name="koreksi_lahan_longitude" id="koreksi_lahan_longitude" value="{{ old('koreksi_lahan_longitude') }}">
                        @error('koreksi_lahan_longitude')<div class="invalid-feedback">{{ $message }}</div>@enderror
                    </div>
                    <div class="col-md-2">
                        <label class="form-label">Luas Lahan (ha)</label>
                        <input class="form-control @error('koreksi_lahan_luas_ha') is-invalid @enderror" type="number" step="0.01" min="0" name="koreksi_lahan_luas_ha" id="koreksi_lahan_luas_ha" value="{{ old('koreksi_lahan_luas_ha') }}">
                        @error('koreksi_lahan_luas_ha')<div class="invalid-feedback">{{ $message }}</div>@enderror
                    </div>
                    <div class="col-md-2">
                        <label class="form-label">Kondisi Lahan</label>
                        <select class="form-select @error('koreksi_lahan_kondisi_lahan') is-invalid @enderror" name="koreksi_lahan_kondisi_lahan" id="koreksi_lahan_kondisi_lahan">
                            <option value="">Pilih</option>
                            @foreach(['baik','sedang','rusak','kritis'] as $kondisi)
                                <option value="{{ $kondisi }}" @selected(old('koreksi_lahan_kondisi_lahan') === $kondisi)>{{ ucfirst($kondisi) }}</option>
                            @endforeach
                        </select>
                        @error('koreksi_lahan_kondisi_lahan')<div class="invalid-feedback">{{ $message }}</div>@enderror
                    </div>
                    <div class="col-md-4">
                        <label class="form-label">Alamat Lahan</label>
                        <input class="form-control @error('koreksi_lahan_alamat_lahan') is-invalid @enderror" name="koreksi_lahan_alamat_lahan" id="koreksi_lahan_alamat_lahan" value="{{ old('koreksi_lahan_alamat_lahan') }}">
                        @error('koreksi_lahan_alamat_lahan')<div class="invalid-feedback">{{ $message }}</div>@enderror
                    </div>

                    <div class="col-12 mt-2"><strong class="small">Koreksi Titik Komoditas</strong></div>
                    <div class="col-md-3">
                        <label class="form-label">Komoditas</label>
                        <select class="form-select @error('koreksi_komoditas_id') is-invalid @enderror" name="koreksi_komoditas_id" id="koreksi_komoditas_id">
                            <option value="">Pilih komoditas</option>
                        </select>
                        @error('koreksi_komoditas_id')<div class="invalid-feedback">{{ $message }}</div>@enderror
                    </div>
                    <div class="col-md-2">
                        <label class="form-label">Latitude Komoditas</label>
                        <input class="form-control @error('koreksi_komoditas_latitude') is-invalid @enderror" type="number" step="0.0000001" name="koreksi_komoditas_latitude" id="koreksi_komoditas_latitude" value="{{ old('koreksi_komoditas_latitude') }}">
                        @error('koreksi_komoditas_latitude')<div class="invalid-feedback">{{ $message }}</div>@enderror
                    </div>
                    <div class="col-md-2">
                        <label class="form-label">Longitude Komoditas</label>
                        <input class="form-control @error('koreksi_komoditas_longitude') is-invalid @enderror" type="number" step="0.0000001" name="koreksi_komoditas_longitude" id="koreksi_komoditas_longitude" value="{{ old('koreksi_komoditas_longitude') }}">
                        @error('koreksi_komoditas_longitude')<div class="invalid-feedback">{{ $message }}</div>@enderror
                    </div>
                    <div class="col-md-2">
                        <label class="form-label">Luas Tanam</label>
                        <input class="form-control @error('koreksi_komoditas_luas_tanam_ha') is-invalid @enderror" type="number" step="0.01" min="0" name="koreksi_komoditas_luas_tanam_ha" id="koreksi_komoditas_luas_tanam_ha" value="{{ old('koreksi_komoditas_luas_tanam_ha') }}">
                        @error('koreksi_komoditas_luas_tanam_ha')<div class="invalid-feedback">{{ $message }}</div>@enderror
                    </div>
                    <div class="col-md-3">
                        <label class="form-label">Status Tanam</label>
                        <select class="form-select @error('koreksi_komoditas_status_tanam') is-invalid @enderror" name="koreksi_komoditas_status_tanam" id="koreksi_komoditas_status_tanam">
                            <option value="">Pilih</option>
                            @foreach(['rencana','tanam','panen','bera','gagal'] as $status)
                                <option value="{{ $status }}" @selected(old('koreksi_komoditas_status_tanam') === $status)>{{ ucfirst($status) }}</option>
                            @endforeach
                        </select>
                        @error('koreksi_komoditas_status_tanam')<div class="invalid-feedback">{{ $message }}</div>@enderror
                    </div>
                    <div class="col-12">
                        <label class="form-label">Alamat Titik Komoditas</label>
                        <input class="form-control @error('koreksi_komoditas_alamat_titik') is-invalid @enderror" name="koreksi_komoditas_alamat_titik" id="koreksi_komoditas_alamat_titik" value="{{ old('koreksi_komoditas_alamat_titik') }}">
                        @error('koreksi_komoditas_alamat_titik')<div class="invalid-feedback">{{ $message }}</div>@enderror
                    </div>
                    <div class="col-12">
                        <label class="form-label">Alasan Perubahan</label>
                        <textarea class="form-control @error('catatan_koreksi') is-invalid @enderror" name="catatan_koreksi" rows="2" placeholder="Jelaskan alasan perubahan data lapangan">{{ old('catatan_koreksi') }}</textarea>
                        @error('catatan_koreksi')<div class="invalid-feedback">{{ $message }}</div>@enderror
                    </div>
                </div>
            </div>
        </div>

        <div class="col-12 d-flex justify-content-end">
            <button class="btn btn-warning" type="submit">Kirim Pengajuan Perbaikan Data</button>
        </div>
    </form>
</div>

<div class="table-responsive panel-card p-2">
    <table class="table table-sm align-middle mb-0" data-dt-server="true" data-dt-source="penyuluh_kunjungan" data-dt-page-length="10">
        <thead>
            <tr>
                <th data-col="tanggal_kunjungan">Tanggal</th>
                <th data-col="nama_petani">Petani</th>
                <th data-col="status_verifikasi">Status</th>
                <th data-col="koordinat">Koordinat</th>
                <th data-col="actions" data-orderable="false" data-searchable="false">Aksi</th>
            </tr>
        </thead>
        <tbody></tbody>
    </table>
</div>

<script>
document.addEventListener('DOMContentLoaded', () => {
    const form = document.getElementById('kunjungan-terpadu-form');
    if (!form) {
        return;
    }

    const penugasanSelect = document.getElementById('penugasan_id');
    const penugasanPerbaikanSelect = document.getElementById('penugasan_id_perbaikan');
    const detailUrlTemplate = form.dataset.detailUrlTemplate || '';

    const oldProduksiKomoditasId = String(form.dataset.oldProduksiKomoditas || '');
    const oldKoreksiKomoditasId = String(form.dataset.oldKoreksiKomoditas || '');
    const hasOldCorrections = form.dataset.hasOldCorrections === '1';
    let initialDetailLoaded = false;

    const detailPetani = document.getElementById('detail-petani');
    const detailLahan = document.getElementById('detail-lahan');
    const detailKomoditas = document.getElementById('detail-komoditas');

    const produksiKomoditasSelect = document.getElementById('produksi_lahan_komoditas_id');
    const koreksiKomoditasSelect = document.getElementById('koreksi_komoditas_id');

    const koreksiLahanFields = {
        latitude: document.getElementById('koreksi_lahan_latitude'),
        longitude: document.getElementById('koreksi_lahan_longitude'),
        luas_ha: document.getElementById('koreksi_lahan_luas_ha'),
        kondisi_lahan: document.getElementById('koreksi_lahan_kondisi_lahan'),
        alamat_lahan: document.getElementById('koreksi_lahan_alamat_lahan'),
    };

    const koreksiKomoditasFields = {
        latitude: document.getElementById('koreksi_komoditas_latitude'),
        longitude: document.getElementById('koreksi_komoditas_longitude'),
        luas_tanam_ha: document.getElementById('koreksi_komoditas_luas_tanam_ha'),
        status_tanam: document.getElementById('koreksi_komoditas_status_tanam'),
        alamat_titik: document.getElementById('koreksi_komoditas_alamat_titik'),
    };

    let currentKomoditas = [];

    const mapElement = document.getElementById('kunjungan-target-map');
    const canUseMap = Boolean(window.L && mapElement);
    const map = canUseMap ? window.L.map(mapElement).setView([-1.35, 123.25], 9) : null;
    let mapMarkers = [];

    if (map) {
        window.L.tileLayer('https://{s}.tile.openstreetmap.org/{z}/{x}/{y}.png', {
            maxZoom: 19,
            attribution: '&copy; OpenStreetMap contributors',
        }).addTo(map);
        setTimeout(() => map.invalidateSize(), 150);
    }

    function markerIcon(symbol, iconColor, bgColor) {
        return window.L.divIcon({
            className: 'sig-map-div-icon',
            html: `<div class="sig-map-marker" style="--sig-size:28px;--sig-icon-color:${iconColor};--sig-bg-color:${bgColor};"><span>${symbol}</span></div>`,
            iconSize: [28, 28],
            iconAnchor: [14, 28],
            popupAnchor: [0, -26],
        });
    }

    function resetMap() {
        if (!map) {
            return;
        }

        mapMarkers.forEach((marker) => map.removeLayer(marker));
        mapMarkers = [];
    }

    function updateMap(data) {
        if (!map) {
            return;
        }

        resetMap();

        const bounds = [];

        if (data?.lahan?.latitude && data?.lahan?.longitude) {
            const lat = Number.parseFloat(data.lahan.latitude);
            const lng = Number.parseFloat(data.lahan.longitude);
            if (Number.isFinite(lat) && Number.isFinite(lng)) {
                const marker = window.L.marker([lat, lng], {
                    icon: markerIcon('L', '#14532d', '#dcfce7'),
                }).addTo(map);

                marker.bindPopup(`<strong>Lahan ${escapeHtml(data.petani?.nama_petani || '')}</strong><br>${escapeHtml(data.lahan.alamat_lahan || '-')}`);
                mapMarkers.push(marker);
                bounds.push([lat, lng]);
            }
        }

        (data?.komoditas || []).forEach((item) => {
            const lat = Number.parseFloat(item.latitude);
            const lng = Number.parseFloat(item.longitude);
            if (!Number.isFinite(lat) || !Number.isFinite(lng)) {
                return;
            }

            const symbol = String(item.nama_komoditas || 'K').slice(0, 1).toUpperCase();
            const marker = window.L.marker([lat, lng], {
                icon: markerIcon(symbol, '#9a3412', '#ffedd5'),
            }).addTo(map);

            marker.bindPopup(`<strong>Komoditas ${escapeHtml(item.nama_komoditas || '')}</strong><br>${escapeHtml(item.alamat_titik || '-')}`);
            mapMarkers.push(marker);
            bounds.push([lat, lng]);
        });

        if (bounds.length > 0) {
            map.fitBounds(bounds, { padding: [20, 20] });
        } else {
            map.setView([-1.35, 123.25], 9);
        }
    }

    function fillSelect(select, options, placeholder, preferred = '') {
        if (!select) {
            return;
        }

        const previous = String(select.value || '');
        select.innerHTML = '';

        const defaultOption = document.createElement('option');
        defaultOption.value = '';
        defaultOption.textContent = placeholder;
        select.appendChild(defaultOption);

        options.forEach((item) => {
            const option = document.createElement('option');
            option.value = String(item.id);
            option.textContent = `${item.nama_komoditas} (${item.tahun_tanam || '-'})`;
            select.appendChild(option);
        });

        const candidate = String(preferred || previous || '');
        if (candidate && options.some((item) => String(item.id) === candidate)) {
            select.value = candidate;
        }
    }

    function setFieldIfEmpty(field, value) {
        if (!field) {
            return;
        }

        if (String(field.value || '').trim() !== '') {
            return;
        }

        field.value = value ?? '';
    }

    function renderDetail(data) {
        if (!data) {
            return;
        }

        const petani = data.petani || {};
        const lahan = data.lahan || {};
        const komoditas = Array.isArray(data.komoditas) ? data.komoditas : [];
        currentKomoditas = komoditas;

        detailPetani.innerHTML = `
            <div><strong>${escapeHtml(petani.nama_petani || '-')}</strong></div>
            <div>No HP: ${escapeHtml(petani.no_hp || '-')}</div>
            <div>Kelompok: ${escapeHtml(petani.kelompok_tani || '-')}</div>
            <div>Alamat: ${escapeHtml(petani.alamat_domisili || '-')}</div>
            <div>Desa/Kecamatan: ${escapeHtml(petani.nama_desa || '-')} / ${escapeHtml(petani.nama_kecamatan || '-')}</div>
        `;

        detailLahan.innerHTML = `
            <div>Alamat: ${escapeHtml(lahan.alamat_lahan || '-')}</div>
            <div>Luas: ${escapeHtml(lahan.luas_ha || '-')} ha</div>
            <div>Kondisi: ${escapeHtml(lahan.kondisi_lahan || '-')}</div>
            <div>Koordinat: ${escapeHtml(lahan.latitude || '-')} , ${escapeHtml(lahan.longitude || '-')}</div>
        `;

        if (komoditas.length === 0) {
            detailKomoditas.innerHTML = '<div class="text-muted">Belum ada data komoditas pada lahan ini.</div>';
        } else {
            detailKomoditas.innerHTML = komoditas.map((item) => `
                <div class="mb-1 border rounded px-2 py-1">
                    <strong>${escapeHtml(item.nama_komoditas || '-')}</strong>
                    <div class="text-muted">Luas: ${escapeHtml(item.luas_tanam_ha || '-')} ha | Status: ${escapeHtml(item.status_tanam || '-')}</div>
                    <div class="text-muted">Titik: ${escapeHtml(item.latitude || '-')} , ${escapeHtml(item.longitude || '-')}</div>
                </div>
            `).join('');
        }

        fillSelect(
            produksiKomoditasSelect,
            komoditas,
            'Pilih komoditas',
            !initialDetailLoaded ? oldProduksiKomoditasId : ''
        );
        fillSelect(
            koreksiKomoditasSelect,
            komoditas,
            'Pilih komoditas',
            !initialDetailLoaded ? oldKoreksiKomoditasId : ''
        );

        setFieldIfEmpty(koreksiLahanFields.latitude, lahan.latitude);
        setFieldIfEmpty(koreksiLahanFields.longitude, lahan.longitude);
        setFieldIfEmpty(koreksiLahanFields.luas_ha, lahan.luas_ha);
        setFieldIfEmpty(koreksiLahanFields.kondisi_lahan, lahan.kondisi_lahan);
        setFieldIfEmpty(koreksiLahanFields.alamat_lahan, lahan.alamat_lahan);

        if (komoditas.length > 0) {
            if (!koreksiKomoditasSelect.value) {
                koreksiKomoditasSelect.value = String(komoditas[0].id);
            }
            syncKomoditasCorrectionFields(!initialDetailLoaded && !hasOldCorrections);
        } else {
            koreksiKomoditasSelect.value = '';
            Object.values(koreksiKomoditasFields).forEach((field) => {
                if (field) {
                    field.value = '';
                }
            });
        }

        updateMap(data);
        initialDetailLoaded = true;
    }

    function syncKomoditasCorrectionFields(force = false) {
        const selectedId = String(koreksiKomoditasSelect.value || '');
        const current = currentKomoditas.find((item) => String(item.id) === selectedId);

        if (!current) {
            return;
        }

        if (force || String(koreksiKomoditasFields.latitude.value || '').trim() === '') {
            koreksiKomoditasFields.latitude.value = current.latitude ?? '';
        }
        if (force || String(koreksiKomoditasFields.longitude.value || '').trim() === '') {
            koreksiKomoditasFields.longitude.value = current.longitude ?? '';
        }
        if (force || String(koreksiKomoditasFields.luas_tanam_ha.value || '').trim() === '') {
            koreksiKomoditasFields.luas_tanam_ha.value = current.luas_tanam_ha ?? '';
        }
        if (force || String(koreksiKomoditasFields.status_tanam.value || '').trim() === '') {
            koreksiKomoditasFields.status_tanam.value = current.status_tanam ?? '';
        }
        if (force || String(koreksiKomoditasFields.alamat_titik.value || '').trim() === '') {
            koreksiKomoditasFields.alamat_titik.value = current.alamat_titik ?? '';
        }
    }

    async function loadPenugasanDetailById(penugasanId) {
        const safePenugasanId = String(penugasanId || '');
        if (!safePenugasanId || !detailUrlTemplate) {
            return;
        }

        const url = detailUrlTemplate.replace('__ID__', safePenugasanId);

        try {
            const response = await fetch(url, {
                method: 'GET',
                headers: {
                    Accept: 'application/json',
                    'X-Requested-With': 'XMLHttpRequest',
                },
            });

            if (!response.ok) {
                return;
            }

            const result = await response.json();
            renderDetail(result);
        } catch (_error) {
            // ignore fetch error; user can retry by changing selection
        }
    }

    function syncPenugasanSelects(source) {
        const sourceValue = String(source?.value || '');
        if (penugasanSelect && source !== penugasanSelect && String(penugasanSelect.value || '') !== sourceValue) {
            penugasanSelect.value = sourceValue;
        }
        if (penugasanPerbaikanSelect && source !== penugasanPerbaikanSelect && String(penugasanPerbaikanSelect.value || '') !== sourceValue) {
            penugasanPerbaikanSelect.value = sourceValue;
        }
    }

    async function loadPenugasanDetail() {
        const penugasanId = String(penugasanSelect?.value || penugasanPerbaikanSelect?.value || '');
        if (!penugasanId || !detailUrlTemplate) {
            return;
        }
        await loadPenugasanDetailById(penugasanId);
    }

    if (penugasanSelect) {
        penugasanSelect.addEventListener('change', async () => {
            syncPenugasanSelects(penugasanSelect);
            await loadPenugasanDetailById(penugasanSelect.value);
        });
    }

    if (penugasanPerbaikanSelect) {
        penugasanPerbaikanSelect.addEventListener('change', async () => {
            syncPenugasanSelects(penugasanPerbaikanSelect);
            await loadPenugasanDetailById(penugasanPerbaikanSelect.value);
        });
    }

    koreksiKomoditasSelect.addEventListener('change', () => syncKomoditasCorrectionFields(true));

    if (penugasanSelect && String(penugasanSelect.value || '').trim() !== '') {
        loadPenugasanDetail();
    } else if (penugasanPerbaikanSelect && String(penugasanPerbaikanSelect.value || '').trim() !== '') {
        syncPenugasanSelects(penugasanPerbaikanSelect);
        loadPenugasanDetail();
    }

    function escapeHtml(value) {
        return String(value ?? '')
            .replaceAll('&', '&amp;')
            .replaceAll('<', '&lt;')
            .replaceAll('>', '&gt;')
            .replaceAll('"', '&quot;')
            .replaceAll("'", '&#039;');
    }
});
</script>
@endif
@endsection
