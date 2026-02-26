<?php

namespace App\Http\Controllers\Penyuluh;

use App\Http\Controllers\Controller;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Storage;
use Illuminate\View\View;

class KunjunganController extends Controller
{
    private function actionResponse(Request $request, bool $success, string $message, int $errorStatus = 422): RedirectResponse|JsonResponse
    {
        if ($request->expectsJson()) {
            if ($success) {
                return response()->json(['message' => $message]);
            }

            return response()->json(['message' => $message], $errorStatus);
        }

        return back()->with($success ? 'success' : 'error', $message);
    }

    private function penyuluhId(): ?int
    {
        return DB::table('penyuluh')->where('user_id', auth()->id())->value('id');
    }

    private function ownPenugasanIds(int $penyuluhId): array
    {
        return DB::table('penugasan_penyuluh')
            ->where('penyuluh_id', $penyuluhId)
            ->where('status_penugasan', 'aktif')
            ->pluck('id')
            ->map(fn ($x) => (int) $x)
            ->toArray();
    }

    public function index(): View
    {
        $penyuluhId = $this->penyuluhId();

        $penugasan = collect();
        if ($penyuluhId) {
            $penugasan = DB::table('penugasan_penyuluh')
                ->join('lahan', 'lahan.id', '=', 'penugasan_penyuluh.lahan_id')
                ->join('petani', 'petani.id', '=', 'lahan.petani_id')
                ->join('desa', 'desa.id', '=', 'lahan.desa_id')
                ->select(
                    'penugasan_penyuluh.id',
                    'petani.nama_petani',
                    'desa.nama_desa',
                    'lahan.alamat_lahan'
                )
                ->where('penugasan_penyuluh.penyuluh_id', $penyuluhId)
                ->where('penugasan_penyuluh.status_penugasan', 'aktif')
                ->orderBy('petani.nama_petani')
                ->get();
        }

        $kategoriKendala = DB::table('kategori_kendala')->orderBy('nama_kategori')->get(['id', 'nama_kategori']);
        $kategoriKebutuhan = DB::table('kategori_kebutuhan')->orderBy('nama_kategori')->get(['id', 'nama_kategori']);
        $periodeLaporan = DB::table('periode_laporan')
            ->orderByDesc('tahun')
            ->orderByDesc('bulan')
            ->get(['id', 'bulan', 'tahun', 'status_periode']);

        return view('penyuluh.kunjungan.index', compact(
            'penyuluhId',
            'penugasan',
            'kategoriKendala',
            'kategoriKebutuhan',
            'periodeLaporan'
        ));
    }

    public function detail(int $id): JsonResponse
    {
        $penyuluhId = $this->penyuluhId();
        if (! $penyuluhId) {
            return response()->json(['message' => 'Profil penyuluh tidak ditemukan.'], 403);
        }

        $detail = DB::table('penugasan_penyuluh')
            ->join('lahan', 'lahan.id', '=', 'penugasan_penyuluh.lahan_id')
            ->join('petani', 'petani.id', '=', 'lahan.petani_id')
            ->join('desa', 'desa.id', '=', 'lahan.desa_id')
            ->join('kecamatan', 'kecamatan.id', '=', 'desa.kecamatan_id')
            ->where('penugasan_penyuluh.id', $id)
            ->where('penugasan_penyuluh.penyuluh_id', $penyuluhId)
            ->where('penugasan_penyuluh.status_penugasan', 'aktif')
            ->select(
                'penugasan_penyuluh.id as penugasan_id',
                'penugasan_penyuluh.lahan_id',
                'petani.id as petani_id',
                'petani.nama_petani',
                'petani.no_hp',
                'petani.alamat_domisili',
                'petani.kelompok_tani',
                'petani.foto_petani_url',
                'lahan.alamat_lahan',
                'lahan.luas_ha',
                'lahan.kondisi_lahan',
                'lahan.latitude',
                'lahan.longitude',
                'lahan.foto_lahan_url',
                'desa.nama_desa',
                'kecamatan.nama_kecamatan'
            )
            ->first();

        if (! $detail) {
            return response()->json(['message' => 'Penugasan tidak ditemukan.'], 404);
        }

        $komoditas = DB::table('lahan_komoditas')
            ->join('komoditas', 'komoditas.id', '=', 'lahan_komoditas.komoditas_id')
            ->where('lahan_komoditas.lahan_id', $detail->lahan_id)
            ->orderBy('komoditas.nama_komoditas')
            ->select(
                'lahan_komoditas.id',
                'lahan_komoditas.komoditas_id',
                'komoditas.nama_komoditas',
                'lahan_komoditas.tahun_tanam',
                'lahan_komoditas.luas_tanam_ha',
                'lahan_komoditas.status_tanam',
                'lahan_komoditas.latitude',
                'lahan_komoditas.longitude',
                'lahan_komoditas.alamat_titik'
            )
            ->get();

        return response()->json([
            'penugasan_id' => (int) $detail->penugasan_id,
            'petani' => [
                'id' => (int) $detail->petani_id,
                'nama_petani' => $detail->nama_petani,
                'no_hp' => $detail->no_hp,
                'alamat_domisili' => $detail->alamat_domisili,
                'kelompok_tani' => $detail->kelompok_tani,
                'foto_petani_url' => $detail->foto_petani_url,
                'nama_desa' => $detail->nama_desa,
                'nama_kecamatan' => $detail->nama_kecamatan,
            ],
            'lahan' => [
                'id' => (int) $detail->lahan_id,
                'alamat_lahan' => $detail->alamat_lahan,
                'luas_ha' => $detail->luas_ha,
                'kondisi_lahan' => $detail->kondisi_lahan,
                'latitude' => $detail->latitude,
                'longitude' => $detail->longitude,
                'foto_lahan_url' => $detail->foto_lahan_url,
            ],
            'komoditas' => $komoditas,
        ]);
    }

    public function store(Request $request): RedirectResponse
    {
        $penyuluhId = $this->penyuluhId();
        if (! $penyuluhId) {
            return back()->with('error', 'Profil penyuluh untuk akun ini belum dibuat.');
        }

        $data = $request->validate([
            'penugasan_id' => ['required', 'exists:penugasan_penyuluh,id'],
            'tanggal_kunjungan' => ['required', 'date'],
            'kondisi_tanaman' => ['nullable', 'string'],
            'catatan_umum' => ['nullable', 'string'],
            'rekomendasi' => ['nullable', 'string'],
            'status_verifikasi' => ['required', 'in:draft,menunggu,revisi'],
            'foto_kunjungan' => ['required', 'image', 'mimes:jpg,jpeg,png,webp', 'max:5120'],

            'produksi_lahan_komoditas_id' => ['nullable', 'exists:lahan_komoditas,id', 'required_with:produksi_jumlah_produksi,produksi_tanggal_panen,produksi_harga_jual,produksi_catatan'],
            'produksi_periode_id' => ['nullable', 'exists:periode_laporan,id'],
            'produksi_tanggal_panen' => ['nullable', 'date'],
            'produksi_jumlah_produksi' => ['nullable', 'numeric', 'min:0', 'required_with:produksi_lahan_komoditas_id,produksi_tanggal_panen,produksi_harga_jual,produksi_catatan'],
            'produksi_produktivitas_kg_ha' => ['nullable', 'numeric', 'min:0'],
            'produksi_harga_jual' => ['nullable', 'numeric', 'min:0'],
            'produksi_catatan' => ['nullable', 'string'],

            'kendala_kategori_id' => ['nullable', 'exists:kategori_kendala,id', 'required_with:kendala_deskripsi,kendala_tingkat_keparahan,kendala_perlu_tindak_lanjut,foto_kendala'],
            'kendala_deskripsi' => ['nullable', 'string', 'required_with:kendala_kategori_id,kendala_tingkat_keparahan,kendala_perlu_tindak_lanjut,foto_kendala'],
            'kendala_tingkat_keparahan' => ['nullable', 'in:rendah,sedang,tinggi,kritis'],
            'kendala_perlu_tindak_lanjut' => ['nullable', 'boolean'],
            'foto_kendala' => ['nullable', 'image', 'mimes:jpg,jpeg,png,webp', 'max:5120', 'required_with:kendala_kategori_id,kendala_deskripsi,kendala_tingkat_keparahan,kendala_perlu_tindak_lanjut'],

            'kebutuhan_kategori_id' => ['nullable', 'exists:kategori_kebutuhan,id', 'required_with:kebutuhan_deskripsi,kebutuhan_jumlah,kebutuhan_satuan,kebutuhan_prioritas'],
            'kebutuhan_deskripsi' => ['nullable', 'string', 'required_with:kebutuhan_kategori_id,kebutuhan_jumlah,kebutuhan_satuan,kebutuhan_prioritas'],
            'kebutuhan_jumlah' => ['nullable', 'numeric', 'min:0'],
            'kebutuhan_satuan' => ['nullable', 'string', 'max:30'],
            'kebutuhan_prioritas' => ['nullable', 'in:rendah,sedang,tinggi'],

            'koreksi_lahan_latitude' => ['nullable', 'numeric'],
            'koreksi_lahan_longitude' => ['nullable', 'numeric'],
            'koreksi_lahan_luas_ha' => ['nullable', 'numeric', 'min:0'],
            'koreksi_lahan_kondisi_lahan' => ['nullable', 'in:baik,sedang,rusak,kritis'],
            'koreksi_lahan_alamat_lahan' => ['nullable', 'string'],

            'koreksi_komoditas_id' => ['nullable', 'exists:lahan_komoditas,id', 'required_with:koreksi_komoditas_latitude,koreksi_komoditas_longitude,koreksi_komoditas_luas_tanam_ha,koreksi_komoditas_status_tanam,koreksi_komoditas_alamat_titik'],
            'koreksi_komoditas_latitude' => ['nullable', 'numeric'],
            'koreksi_komoditas_longitude' => ['nullable', 'numeric'],
            'koreksi_komoditas_luas_tanam_ha' => ['nullable', 'numeric', 'min:0'],
            'koreksi_komoditas_status_tanam' => ['nullable', 'in:rencana,tanam,panen,bera,gagal'],
            'koreksi_komoditas_alamat_titik' => ['nullable', 'string'],

            'catatan_koreksi' => ['nullable', 'string'],
        ], [
            'foto_kunjungan.required' => 'Foto kunjungan wajib diupload.',
            'foto_kunjungan.image' => 'Foto kunjungan harus berupa gambar.',
            'foto_kunjungan.mimes' => 'Format foto kunjungan harus jpg, jpeg, png, atau webp.',
            'foto_kunjungan.max' => 'Ukuran foto kunjungan maksimal 5MB.',
            'foto_kendala.required_with' => 'Foto kendala wajib diupload saat Anda mengisi data kendala.',
            'foto_kendala.image' => 'Foto kendala harus berupa gambar.',
            'foto_kendala.mimes' => 'Format foto kendala harus jpg, jpeg, png, atau webp.',
            'foto_kendala.max' => 'Ukuran foto kendala maksimal 5MB.',
            'produksi_lahan_komoditas_id.required_with' => 'Pilih komoditas lahan untuk data produksi.',
            'produksi_jumlah_produksi.required_with' => 'Jumlah produksi wajib diisi saat input produksi.',
            'kendala_kategori_id.required_with' => 'Kategori kendala wajib diisi saat melaporkan kendala.',
            'kendala_deskripsi.required_with' => 'Deskripsi kendala wajib diisi saat melaporkan kendala.',
            'kebutuhan_kategori_id.required_with' => 'Kategori kebutuhan wajib diisi saat melaporkan kebutuhan.',
            'kebutuhan_deskripsi.required_with' => 'Deskripsi kebutuhan wajib diisi saat melaporkan kebutuhan.',
            'koreksi_komoditas_id.required_with' => 'Pilih komoditas target saat mengusulkan koreksi komoditas.',
        ], [
            'penugasan_id' => 'penugasan petani',
            'tanggal_kunjungan' => 'tanggal kunjungan',
            'status_verifikasi' => 'status laporan',
            'produksi_lahan_komoditas_id' => 'komoditas lahan produksi',
            'produksi_jumlah_produksi' => 'jumlah produksi',
            'kendala_kategori_id' => 'kategori kendala',
            'kendala_deskripsi' => 'deskripsi kendala',
            'kebutuhan_kategori_id' => 'kategori kebutuhan',
            'kebutuhan_deskripsi' => 'deskripsi kebutuhan',
            'koreksi_komoditas_id' => 'komoditas koreksi',
        ]);

        $ownIds = $this->ownPenugasanIds($penyuluhId);
        if (! in_array((int) $data['penugasan_id'], $ownIds, true)) {
            return back()->with('error', 'Penugasan tidak valid untuk akun penyuluh Anda.');
        }

        $penugasanDetail = DB::table('penugasan_penyuluh')
            ->join('lahan', 'lahan.id', '=', 'penugasan_penyuluh.lahan_id')
            ->join('petani', 'petani.id', '=', 'lahan.petani_id')
            ->where('penugasan_penyuluh.id', $data['penugasan_id'])
            ->where('penugasan_penyuluh.penyuluh_id', $penyuluhId)
            ->select(
                'penugasan_penyuluh.id',
                'penugasan_penyuluh.lahan_id',
                'lahan.petani_id',
                'lahan.alamat_lahan',
                'lahan.luas_ha',
                'lahan.kondisi_lahan',
                'lahan.latitude',
                'lahan.longitude',
                'petani.nama_petani'
            )
            ->first();

        if (! $penugasanDetail) {
            return back()->with('error', 'Data penugasan tidak ditemukan.');
        }

        $hasProduksiInput =
            $request->filled('produksi_lahan_komoditas_id')
            || $request->filled('produksi_jumlah_produksi')
            || $request->filled('produksi_tanggal_panen')
            || $request->filled('produksi_harga_jual')
            || $request->filled('produksi_catatan');

        $validLahanKomoditasId = null;
        $periodeId = null;
        if ($hasProduksiInput) {
            if (! $request->filled('produksi_lahan_komoditas_id') || ! $request->filled('produksi_jumlah_produksi')) {
                return back()->withInput()->with('error', 'Input produksi memerlukan komoditas lahan dan jumlah produksi.');
            }

            $validLahanKomoditas = DB::table('lahan_komoditas')
                ->where('id', $data['produksi_lahan_komoditas_id'])
                ->where('lahan_id', $penugasanDetail->lahan_id)
                ->first(['id']);
            if (! $validLahanKomoditas) {
                return back()->withInput()->with('error', 'Komoditas lahan untuk produksi tidak valid.');
            }
            $validLahanKomoditasId = (int) $validLahanKomoditas->id;

            $periodeId = (int) ($data['produksi_periode_id'] ?? $this->defaultPeriodeId());
            if (! $periodeId) {
                return back()->withInput()->with('error', 'Periode laporan belum tersedia.');
            }
        }

        $hasKendalaInput = $request->filled('kendala_deskripsi') || $request->filled('kendala_kategori_id');

        $hasKebutuhanInput = $request->filled('kebutuhan_deskripsi') || $request->filled('kebutuhan_kategori_id');

        $komoditasCorrectionFields = [
            'koreksi_komoditas_latitude',
            'koreksi_komoditas_longitude',
            'koreksi_komoditas_luas_tanam_ha',
            'koreksi_komoditas_status_tanam',
            'koreksi_komoditas_alamat_titik',
        ];
        $hasKomoditasCorrection = collect($komoditasCorrectionFields)->contains(fn ($field) => $request->filled($field));
        if ($hasKomoditasCorrection) {
            if (! $request->filled('koreksi_komoditas_id')) {
                return back()->withInput()->with('error', 'Pilih komoditas target untuk usulan koreksi komoditas.');
            }

            $validKoreksiKomoditas = DB::table('lahan_komoditas')
                ->where('id', $data['koreksi_komoditas_id'])
                ->where('lahan_id', $penugasanDetail->lahan_id)
                ->exists();
            if (! $validKoreksiKomoditas) {
                return back()->withInput()->with('error', 'Komoditas untuk usulan koreksi tidak valid.');
            }
        }

        $fallbackKomoditas = DB::table('lahan_komoditas')
            ->where('lahan_id', $penugasanDetail->lahan_id)
            ->whereNotNull('latitude')
            ->whereNotNull('longitude')
            ->orderByDesc('id')
            ->first(['latitude', 'longitude']);

        $kunjunganLatitude = $penugasanDetail->latitude ?? $fallbackKomoditas?->latitude;
        $kunjunganLongitude = $penugasanDetail->longitude ?? $fallbackKomoditas?->longitude;

        $fotoKunjunganPath = $request->file('foto_kunjungan')->store('kunjungan/utama', 'public');
        $fotoKunjunganUrl = Storage::url($fotoKunjunganPath);
        $fotoKendalaUrl = null;
        if ($request->hasFile('foto_kendala')) {
            $fotoKendalaPath = $request->file('foto_kendala')->store('kunjungan/kendala', 'public');
            $fotoKendalaUrl = Storage::url($fotoKendalaPath);
        }

        $kunjunganId = 0;
        $usulanCount = 0;

        DB::transaction(function () use (
            $data,
            $penugasanDetail,
            $kunjunganLatitude,
            $kunjunganLongitude,
            $hasProduksiInput,
            $validLahanKomoditasId,
            $periodeId,
            $hasKendalaInput,
            $hasKebutuhanInput,
            $request,
            $fotoKunjunganUrl,
            $fotoKendalaUrl,
            &$kunjunganId,
            &$usulanCount
        ): void {
            $kunjunganId = (int) DB::table('kunjungan_monitoring')->insertGetId([
                'penugasan_id' => $data['penugasan_id'],
                'tanggal_kunjungan' => $data['tanggal_kunjungan'],
                'kondisi_tanaman' => $data['kondisi_tanaman'] ?? null,
                'catatan_umum' => $data['catatan_umum'] ?? null,
                'rekomendasi' => $data['rekomendasi'] ?? null,
                'latitude' => $kunjunganLatitude,
                'longitude' => $kunjunganLongitude,
                'status_verifikasi' => $data['status_verifikasi'],
                'created_at' => now(),
                'updated_at' => now(),
            ]);

            DB::table('lampiran_media')->insert([
                'kunjungan_id' => $kunjunganId,
                'uploaded_by_user_id' => auth()->id(),
                'file_url' => $fotoKunjunganUrl,
                'file_type' => 'image',
                'taken_at' => $data['tanggal_kunjungan'],
                'uploaded_at' => now(),
                'created_at' => now(),
                'updated_at' => now(),
            ]);

            if ($hasProduksiInput && $validLahanKomoditasId && $periodeId) {
                DB::table('produksi_panen')->insert([
                    'lahan_komoditas_id' => $validLahanKomoditasId,
                    'periode_id' => $periodeId,
                    'kunjungan_id' => $kunjunganId,
                    'tanggal_panen' => $data['produksi_tanggal_panen'] ?? Carbon::parse($data['tanggal_kunjungan'])->toDateString(),
                    'jumlah_produksi' => $data['produksi_jumlah_produksi'],
                    'produktivitas_kg_ha' => $data['produksi_produktivitas_kg_ha'] ?? null,
                    'harga_jual' => $data['produksi_harga_jual'] ?? null,
                    'catatan' => $data['produksi_catatan'] ?? null,
                    'created_at' => now(),
                    'updated_at' => now(),
                ]);
            }

            if ($hasKendalaInput) {
                DB::table('kendala_kunjungan')->insert([
                    'kunjungan_id' => $kunjunganId,
                    'kategori_kendala_id' => $data['kendala_kategori_id'],
                    'deskripsi_kendala' => $data['kendala_deskripsi'],
                    'tingkat_keparahan' => $data['kendala_tingkat_keparahan'] ?? 'sedang',
                    'perlu_tindak_lanjut' => (bool) ($data['kendala_perlu_tindak_lanjut'] ?? true),
                    'created_at' => now(),
                    'updated_at' => now(),
                ]);

                if ($fotoKendalaUrl) {
                    DB::table('lampiran_media')->insert([
                        'kunjungan_id' => $kunjunganId,
                        'uploaded_by_user_id' => auth()->id(),
                        'file_url' => $fotoKendalaUrl,
                        'file_type' => 'image',
                        'taken_at' => $data['tanggal_kunjungan'],
                        'uploaded_at' => now(),
                        'created_at' => now(),
                        'updated_at' => now(),
                    ]);
                }
            }

            if ($hasKebutuhanInput) {
                DB::table('kebutuhan_kunjungan')->insert([
                    'kunjungan_id' => $kunjunganId,
                    'kategori_kebutuhan_id' => $data['kebutuhan_kategori_id'],
                    'deskripsi_kebutuhan' => $data['kebutuhan_deskripsi'],
                    'jumlah' => $data['kebutuhan_jumlah'] ?? null,
                    'satuan' => $data['kebutuhan_satuan'] ?? null,
                    'prioritas' => $data['kebutuhan_prioritas'] ?? 'sedang',
                    'created_at' => now(),
                    'updated_at' => now(),
                ]);
            }

            $usulanCount = $this->createUsulanPerubahan($request, $data, $penugasanDetail, $kunjunganId);
        });

        $this->notifyAdminKecamatan(
            (int) $data['penugasan_id'],
            'Laporan kunjungan baru',
            'Penyuluh mengirim laporan kunjungan untuk petani '.$penugasanDetail->nama_petani.'.',
            'kunjungan_monitoring',
            $kunjunganId
        );

        if ($usulanCount > 0) {
            $this->notifyAdminKecamatan(
                (int) $data['penugasan_id'],
                'Usulan perubahan data lapangan',
                'Terdapat '.$usulanCount.' usulan perubahan data dari penyuluh untuk petani '.$penugasanDetail->nama_petani.'.',
                'usulan_perubahan_data',
                $kunjunganId
            );
        }

        return back()->with('success', 'Kunjungan terpadu berhasil disimpan.');
    }

    public function storePerbaikan(Request $request): RedirectResponse
    {
        $penyuluhId = $this->penyuluhId();
        if (! $penyuluhId) {
            return back()->with('error', 'Profil penyuluh untuk akun ini belum dibuat.');
        }

        $data = $request->validate([
            'penugasan_id' => ['required', 'exists:penugasan_penyuluh,id'],
            'waktu_pengajuan' => ['required', 'date'],
            'catatan_koreksi' => ['required', 'string'],

            'koreksi_lahan_latitude' => ['nullable', 'numeric'],
            'koreksi_lahan_longitude' => ['nullable', 'numeric'],
            'koreksi_lahan_luas_ha' => ['nullable', 'numeric', 'min:0'],
            'koreksi_lahan_kondisi_lahan' => ['nullable', 'in:baik,sedang,rusak,kritis'],
            'koreksi_lahan_alamat_lahan' => ['nullable', 'string'],

            'koreksi_komoditas_id' => ['nullable', 'exists:lahan_komoditas,id', 'required_with:koreksi_komoditas_latitude,koreksi_komoditas_longitude,koreksi_komoditas_luas_tanam_ha,koreksi_komoditas_status_tanam,koreksi_komoditas_alamat_titik'],
            'koreksi_komoditas_latitude' => ['nullable', 'numeric'],
            'koreksi_komoditas_longitude' => ['nullable', 'numeric'],
            'koreksi_komoditas_luas_tanam_ha' => ['nullable', 'numeric', 'min:0'],
            'koreksi_komoditas_status_tanam' => ['nullable', 'in:rencana,tanam,panen,bera,gagal'],
            'koreksi_komoditas_alamat_titik' => ['nullable', 'string'],
        ], [
            'catatan_koreksi.required' => 'Alasan perubahan wajib diisi.',
            'waktu_pengajuan.required' => 'Waktu pengajuan wajib diisi.',
            'koreksi_komoditas_id.required_with' => 'Pilih komoditas target saat mengusulkan koreksi komoditas.',
        ]);

        $ownIds = $this->ownPenugasanIds($penyuluhId);
        if (! in_array((int) $data['penugasan_id'], $ownIds, true)) {
            return back()->withInput()->with('error', 'Penugasan tidak valid untuk akun penyuluh Anda.');
        }

        $penugasanDetail = DB::table('penugasan_penyuluh')
            ->join('lahan', 'lahan.id', '=', 'penugasan_penyuluh.lahan_id')
            ->where('penugasan_penyuluh.id', $data['penugasan_id'])
            ->where('penugasan_penyuluh.penyuluh_id', $penyuluhId)
            ->select(
                'penugasan_penyuluh.id',
                'penugasan_penyuluh.lahan_id',
                'lahan.alamat_lahan',
                'lahan.luas_ha',
                'lahan.kondisi_lahan',
                'lahan.latitude',
                'lahan.longitude'
            )
            ->first();

        if (! $penugasanDetail) {
            return back()->withInput()->with('error', 'Data penugasan tidak ditemukan.');
        }

        $komoditasCorrectionFields = [
            'koreksi_komoditas_latitude',
            'koreksi_komoditas_longitude',
            'koreksi_komoditas_luas_tanam_ha',
            'koreksi_komoditas_status_tanam',
            'koreksi_komoditas_alamat_titik',
        ];
        $hasKomoditasCorrection = collect($komoditasCorrectionFields)->contains(fn ($field) => $request->filled($field));
        if ($hasKomoditasCorrection) {
            $validKoreksiKomoditas = DB::table('lahan_komoditas')
                ->where('id', $data['koreksi_komoditas_id'])
                ->where('lahan_id', $penugasanDetail->lahan_id)
                ->exists();
            if (! $validKoreksiKomoditas) {
                return back()->withInput()->with('error', 'Komoditas untuk usulan koreksi tidak valid.');
            }
        }

        $usulanCount = $this->createUsulanPerubahan($request, $data, $penugasanDetail, null);
        if ($usulanCount < 1) {
            return back()->withInput()->with('error', 'Tidak ada perubahan yang diajukan. Ubah minimal satu field data lapangan.');
        }

        $this->notifyAdminKecamatan(
            (int) $data['penugasan_id'],
            'Pengajuan perbaikan data lapangan',
            'Penyuluh mengajukan '.$usulanCount.' usulan perbaikan data lapangan.',
            'usulan_perubahan_data',
            (int) $data['penugasan_id']
        );

        return back()->with('success', 'Pengajuan perbaikan data lapangan berhasil dikirim.');
    }

    public function update(Request $request, int $id): RedirectResponse|JsonResponse
    {
        $penyuluhId = $this->penyuluhId();
        if (! $penyuluhId) {
            return $this->actionResponse($request, false, 'Profil penyuluh tidak tersedia.', 403);
        }

        $target = DB::table('kunjungan_monitoring')
            ->join('penugasan_penyuluh', 'penugasan_penyuluh.id', '=', 'kunjungan_monitoring.penugasan_id')
            ->where('kunjungan_monitoring.id', $id)
            ->where('penugasan_penyuluh.penyuluh_id', $penyuluhId)
            ->select(
                'kunjungan_monitoring.id',
                'kunjungan_monitoring.penugasan_id',
                'kunjungan_monitoring.tanggal_kunjungan',
                'penugasan_penyuluh.lahan_id'
            )
            ->first();

        if (! $target) {
            return $this->actionResponse($request, false, 'Kunjungan tidak ditemukan.', 404);
        }

        $data = $request->validate([
            'kondisi_tanaman' => ['nullable', 'string'],
            'catatan_umum' => ['nullable', 'string'],
            'rekomendasi' => ['nullable', 'string'],
            'foto_kunjungan' => ['nullable', 'image', 'mimes:jpg,jpeg,png,webp', 'max:5120'],

            'produksi_lahan_komoditas_id' => ['nullable', 'exists:lahan_komoditas,id', 'required_with:produksi_jumlah_produksi,produksi_tanggal_panen,produksi_harga_jual,produksi_catatan'],
            'produksi_periode_id' => ['nullable', 'exists:periode_laporan,id'],
            'produksi_tanggal_panen' => ['nullable', 'date'],
            'produksi_jumlah_produksi' => ['nullable', 'numeric', 'min:0', 'required_with:produksi_lahan_komoditas_id,produksi_tanggal_panen,produksi_harga_jual,produksi_catatan'],
            'produksi_produktivitas_kg_ha' => ['nullable', 'numeric', 'min:0'],
            'produksi_harga_jual' => ['nullable', 'numeric', 'min:0'],
            'produksi_catatan' => ['nullable', 'string'],

            'kendala_kategori_id' => ['nullable', 'exists:kategori_kendala,id', 'required_with:kendala_deskripsi,kendala_tingkat_keparahan,kendala_perlu_tindak_lanjut,foto_kendala'],
            'kendala_deskripsi' => ['nullable', 'string', 'required_with:kendala_kategori_id,kendala_tingkat_keparahan,kendala_perlu_tindak_lanjut,foto_kendala'],
            'kendala_tingkat_keparahan' => ['nullable', 'in:rendah,sedang,tinggi,kritis'],
            'kendala_perlu_tindak_lanjut' => ['nullable', 'boolean'],
            'foto_kendala' => ['nullable', 'image', 'mimes:jpg,jpeg,png,webp', 'max:5120', 'required_with:kendala_kategori_id,kendala_deskripsi,kendala_tingkat_keparahan,kendala_perlu_tindak_lanjut'],

            'kebutuhan_kategori_id' => ['nullable', 'exists:kategori_kebutuhan,id', 'required_with:kebutuhan_deskripsi,kebutuhan_jumlah,kebutuhan_satuan,kebutuhan_prioritas'],
            'kebutuhan_deskripsi' => ['nullable', 'string', 'required_with:kebutuhan_kategori_id,kebutuhan_jumlah,kebutuhan_satuan,kebutuhan_prioritas'],
            'kebutuhan_jumlah' => ['nullable', 'numeric', 'min:0'],
            'kebutuhan_satuan' => ['nullable', 'string', 'max:30'],
            'kebutuhan_prioritas' => ['nullable', 'in:rendah,sedang,tinggi'],
        ], [
            'foto_kunjungan.image' => 'Foto kunjungan harus berupa gambar.',
            'foto_kunjungan.mimes' => 'Format foto kunjungan harus jpg, jpeg, png, atau webp.',
            'foto_kunjungan.max' => 'Ukuran foto kunjungan maksimal 5MB.',
            'foto_kendala.required_with' => 'Foto kendala wajib diupload saat Anda mengisi data kendala.',
            'foto_kendala.image' => 'Foto kendala harus berupa gambar.',
            'foto_kendala.mimes' => 'Format foto kendala harus jpg, jpeg, png, atau webp.',
            'foto_kendala.max' => 'Ukuran foto kendala maksimal 5MB.',
            'produksi_lahan_komoditas_id.required_with' => 'Pilih komoditas lahan untuk data produksi.',
            'produksi_jumlah_produksi.required_with' => 'Jumlah produksi wajib diisi saat input produksi.',
            'kendala_kategori_id.required_with' => 'Kategori kendala wajib diisi saat melaporkan kendala.',
            'kendala_deskripsi.required_with' => 'Deskripsi kendala wajib diisi saat melaporkan kendala.',
            'kebutuhan_kategori_id.required_with' => 'Kategori kebutuhan wajib diisi saat melaporkan kebutuhan.',
            'kebutuhan_deskripsi.required_with' => 'Deskripsi kebutuhan wajib diisi saat melaporkan kebutuhan.',
        ]);

        $existingProduksi = DB::table('produksi_panen')
            ->where('kunjungan_id', $id)
            ->orderByDesc('id')
            ->first(['id', 'periode_id', 'tanggal_panen']);
        $existingKendala = DB::table('kendala_kunjungan')
            ->where('kunjungan_id', $id)
            ->orderByDesc('id')
            ->first(['id', 'tingkat_keparahan', 'perlu_tindak_lanjut']);
        $existingKebutuhan = DB::table('kebutuhan_kunjungan')
            ->where('kunjungan_id', $id)
            ->orderByDesc('id')
            ->first(['id', 'prioritas']);

        $hasProduksiInput =
            $request->filled('produksi_lahan_komoditas_id')
            || $request->filled('produksi_jumlah_produksi')
            || $request->filled('produksi_tanggal_panen')
            || $request->filled('produksi_harga_jual')
            || $request->filled('produksi_catatan');

        $produksiPeriodeId = null;
        if ($hasProduksiInput) {
            if (! $request->filled('produksi_lahan_komoditas_id') || ! $request->filled('produksi_jumlah_produksi')) {
                return $this->actionResponse($request, false, 'Input produksi memerlukan komoditas lahan dan jumlah produksi.');
            }

            $validLahanKomoditas = DB::table('lahan_komoditas')
                ->where('id', $data['produksi_lahan_komoditas_id'])
                ->where('lahan_id', $target->lahan_id)
                ->exists();
            if (! $validLahanKomoditas) {
                return $this->actionResponse($request, false, 'Komoditas lahan untuk produksi tidak valid.');
            }

            $produksiPeriodeId = (int) ($data['produksi_periode_id'] ?? ($existingProduksi?->periode_id ?? $this->defaultPeriodeId()));
            if ($produksiPeriodeId <= 0) {
                return $this->actionResponse($request, false, 'Periode laporan belum tersedia.');
            }
        }

        $hasKendalaInput =
            $request->filled('kendala_kategori_id')
            || $request->filled('kendala_deskripsi')
            || $request->filled('kendala_tingkat_keparahan')
            || $request->filled('kendala_perlu_tindak_lanjut')
            || $request->hasFile('foto_kendala');
        if ($hasKendalaInput && (! $request->filled('kendala_kategori_id') || ! $request->filled('kendala_deskripsi'))) {
            return $this->actionResponse($request, false, 'Input kendala memerlukan kategori dan deskripsi kendala.');
        }

        $hasKebutuhanInput =
            $request->filled('kebutuhan_kategori_id')
            || $request->filled('kebutuhan_deskripsi')
            || $request->filled('kebutuhan_jumlah')
            || $request->filled('kebutuhan_satuan')
            || $request->filled('kebutuhan_prioritas');
        if ($hasKebutuhanInput && (! $request->filled('kebutuhan_kategori_id') || ! $request->filled('kebutuhan_deskripsi'))) {
            return $this->actionResponse($request, false, 'Input kebutuhan memerlukan kategori dan deskripsi kebutuhan.');
        }

        DB::transaction(function () use (
            $request,
            $data,
            $id,
            $target,
            $hasProduksiInput,
            $produksiPeriodeId,
            $existingProduksi,
            $hasKendalaInput,
            $existingKendala,
            $hasKebutuhanInput,
            $existingKebutuhan
        ): void {
            DB::table('kunjungan_monitoring')->where('id', $id)->update([
                'kondisi_tanaman' => $data['kondisi_tanaman'] ?? null,
                'catatan_umum' => $data['catatan_umum'] ?? null,
                'rekomendasi' => $data['rekomendasi'] ?? null,
                'updated_at' => now(),
            ]);

            if ($request->hasFile('foto_kunjungan')) {
                $this->upsertKunjunganImage($id, 'kunjungan/utama', 'kunjungan/utama', $request, 'foto_kunjungan', $target->tanggal_kunjungan);
            }

            if ($hasProduksiInput && $produksiPeriodeId) {
                $tanggalPanen = $data['produksi_tanggal_panen']
                    ?? ($existingProduksi?->tanggal_panen
                        ? Carbon::parse((string) $existingProduksi->tanggal_panen)->toDateString()
                        : Carbon::parse((string) $target->tanggal_kunjungan)->toDateString());

                $produksiPayload = [
                    'lahan_komoditas_id' => $data['produksi_lahan_komoditas_id'],
                    'periode_id' => $produksiPeriodeId,
                    'kunjungan_id' => $id,
                    'tanggal_panen' => $tanggalPanen,
                    'jumlah_produksi' => $data['produksi_jumlah_produksi'],
                    'produktivitas_kg_ha' => $data['produksi_produktivitas_kg_ha'] ?? null,
                    'harga_jual' => $data['produksi_harga_jual'] ?? null,
                    'catatan' => $data['produksi_catatan'] ?? null,
                    'updated_at' => now(),
                ];

                if ($existingProduksi) {
                    DB::table('produksi_panen')->where('id', $existingProduksi->id)->update($produksiPayload);
                } else {
                    DB::table('produksi_panen')->insert([
                        ...$produksiPayload,
                        'created_at' => now(),
                    ]);
                }
            }

            if ($hasKendalaInput) {
                $kendalaPayload = [
                    'kunjungan_id' => $id,
                    'kategori_kendala_id' => $data['kendala_kategori_id'],
                    'deskripsi_kendala' => $data['kendala_deskripsi'],
                    'tingkat_keparahan' => $data['kendala_tingkat_keparahan'] ?? ($existingKendala?->tingkat_keparahan ?? 'sedang'),
                    'perlu_tindak_lanjut' => $request->filled('kendala_perlu_tindak_lanjut')
                        ? (bool) $request->input('kendala_perlu_tindak_lanjut')
                        : (bool) ($existingKendala?->perlu_tindak_lanjut ?? true),
                    'updated_at' => now(),
                ];

                if ($existingKendala) {
                    DB::table('kendala_kunjungan')->where('id', $existingKendala->id)->update($kendalaPayload);
                } else {
                    DB::table('kendala_kunjungan')->insert([
                        ...$kendalaPayload,
                        'created_at' => now(),
                    ]);
                }

                if ($request->hasFile('foto_kendala')) {
                    $this->upsertKunjunganImage($id, 'kunjungan/kendala', 'kunjungan/kendala', $request, 'foto_kendala', $target->tanggal_kunjungan);
                }
            }

            if ($hasKebutuhanInput) {
                $kebutuhanPayload = [
                    'kunjungan_id' => $id,
                    'kategori_kebutuhan_id' => $data['kebutuhan_kategori_id'],
                    'deskripsi_kebutuhan' => $data['kebutuhan_deskripsi'],
                    'jumlah' => $data['kebutuhan_jumlah'] ?? null,
                    'satuan' => $data['kebutuhan_satuan'] ?? null,
                    'prioritas' => $data['kebutuhan_prioritas'] ?? ($existingKebutuhan?->prioritas ?? 'sedang'),
                    'updated_at' => now(),
                ];

                if ($existingKebutuhan) {
                    DB::table('kebutuhan_kunjungan')->where('id', $existingKebutuhan->id)->update($kebutuhanPayload);
                } else {
                    DB::table('kebutuhan_kunjungan')->insert([
                        ...$kebutuhanPayload,
                        'created_at' => now(),
                    ]);
                }
            }
        });

        return $this->actionResponse($request, true, 'Kunjungan monitoring berhasil diperbarui.');
    }

    public function destroy(Request $request, int $id): RedirectResponse|JsonResponse
    {
        return $this->actionResponse($request, false, 'Penyuluh tidak memiliki akses hapus laporan kunjungan. Hapus hanya dapat dilakukan admin kecamatan.', 403);
    }

    private function upsertKunjunganImage(int $kunjunganId, string $fileUrlSegment, string $storageDir, Request $request, string $fileField, string $takenAt): void
    {
        if (! $request->hasFile($fileField)) {
            return;
        }

        $existing = DB::table('lampiran_media')
            ->where('kunjungan_id', $kunjunganId)
            ->where('file_type', 'image')
            ->where('file_url', 'like', '%/'.$fileUrlSegment.'/%')
            ->orderByDesc('id')
            ->first(['id', 'file_url']);

        $path = $request->file($fileField)->store($storageDir, 'public');
        $fileUrl = Storage::url($path);

        if ($existing) {
            DB::table('lampiran_media')->where('id', $existing->id)->update([
                'uploaded_by_user_id' => auth()->id(),
                'file_url' => $fileUrl,
                'taken_at' => $takenAt,
                'uploaded_at' => now(),
                'updated_at' => now(),
            ]);
        } else {
            DB::table('lampiran_media')->insert([
                'kunjungan_id' => $kunjunganId,
                'uploaded_by_user_id' => auth()->id(),
                'file_url' => $fileUrl,
                'file_type' => 'image',
                'taken_at' => $takenAt,
                'uploaded_at' => now(),
                'created_at' => now(),
                'updated_at' => now(),
            ]);
        }

        if ($existing && is_string($existing->file_url) && str_starts_with($existing->file_url, '/storage/')) {
            $oldPath = str_replace('/storage/', '', $existing->file_url);
            if ($oldPath !== '' && Storage::disk('public')->exists($oldPath)) {
                Storage::disk('public')->delete($oldPath);
            }
        }
    }

    private function defaultPeriodeId(): ?int
    {
        return DB::table('periode_laporan')
            ->where('status_periode', 'terbuka')
            ->orderByDesc('tahun')
            ->orderByDesc('bulan')
            ->value('id')
            ?? DB::table('periode_laporan')
                ->orderByDesc('tahun')
                ->orderByDesc('bulan')
                ->value('id');
    }

    private function createUsulanPerubahan(Request $request, array $data, object $penugasanDetail, ?int $kunjunganId): int
    {
        $usulanCount = 0;
        $alasan = $data['catatan_koreksi'] ?? null;
        $waktuPengajuan = $data['waktu_pengajuan'] ?? now();

        $lahanMap = [
            'koreksi_lahan_latitude' => 'latitude',
            'koreksi_lahan_longitude' => 'longitude',
            'koreksi_lahan_luas_ha' => 'luas_ha',
            'koreksi_lahan_kondisi_lahan' => 'kondisi_lahan',
            'koreksi_lahan_alamat_lahan' => 'alamat_lahan',
        ];

        foreach ($lahanMap as $requestField => $fieldName) {
            if (! $request->filled($requestField)) {
                continue;
            }

            $nilaiLama = $penugasanDetail->{$fieldName};
            $nilaiUsulan = $request->input($requestField);

            if ((string) $nilaiLama === (string) $nilaiUsulan) {
                continue;
            }

            DB::table('usulan_perubahan_data')->insert([
                'penugasan_id' => $data['penugasan_id'],
                'kunjungan_id' => $kunjunganId,
                'diajukan_oleh_user_id' => auth()->id(),
                'target_tipe' => 'lahan',
                'target_id' => $penugasanDetail->lahan_id,
                'field_name' => $fieldName,
                'nilai_lama' => $nilaiLama,
                'nilai_usulan' => $nilaiUsulan,
                'alasan' => $alasan,
                'waktu_pengajuan' => $waktuPengajuan,
                'status' => 'menunggu',
                'created_at' => now(),
                'updated_at' => now(),
            ]);

            $usulanCount++;
        }

        $komoditasMap = [
            'koreksi_komoditas_latitude' => 'latitude',
            'koreksi_komoditas_longitude' => 'longitude',
            'koreksi_komoditas_luas_tanam_ha' => 'luas_tanam_ha',
            'koreksi_komoditas_status_tanam' => 'status_tanam',
            'koreksi_komoditas_alamat_titik' => 'alamat_titik',
        ];

        $hasKomoditasCorrection = collect(array_keys($komoditasMap))
            ->contains(fn ($field) => $request->filled($field));

        if ($hasKomoditasCorrection) {
            if (! $request->filled('koreksi_komoditas_id')) {
                return $usulanCount;
            }

            $komoditasRow = DB::table('lahan_komoditas')
                ->where('id', $data['koreksi_komoditas_id'])
                ->where('lahan_id', $penugasanDetail->lahan_id)
                ->first();

            if (! $komoditasRow) {
                return $usulanCount;
            }

            foreach ($komoditasMap as $requestField => $fieldName) {
                if (! $request->filled($requestField)) {
                    continue;
                }

                $nilaiLama = $komoditasRow->{$fieldName};
                $nilaiUsulan = $request->input($requestField);

                if ((string) $nilaiLama === (string) $nilaiUsulan) {
                    continue;
                }

                DB::table('usulan_perubahan_data')->insert([
                    'penugasan_id' => $data['penugasan_id'],
                    'kunjungan_id' => $kunjunganId,
                    'diajukan_oleh_user_id' => auth()->id(),
                    'target_tipe' => 'lahan_komoditas',
                    'target_id' => $komoditasRow->id,
                    'field_name' => $fieldName,
                    'nilai_lama' => $nilaiLama,
                    'nilai_usulan' => $nilaiUsulan,
                    'alasan' => $alasan,
                    'waktu_pengajuan' => $waktuPengajuan,
                    'status' => 'menunggu',
                    'created_at' => now(),
                    'updated_at' => now(),
                ]);

                $usulanCount++;
            }
        }

        return $usulanCount;
    }

    private function notifyAdminKecamatan(int $penugasanId, string $judul, string $pesan, string $refTipe, int $refId): void
    {
        $kecamatanId = DB::table('penugasan_penyuluh')
            ->join('lahan', 'lahan.id', '=', 'penugasan_penyuluh.lahan_id')
            ->join('desa', 'desa.id', '=', 'lahan.desa_id')
            ->where('penugasan_penyuluh.id', $penugasanId)
            ->value('desa.kecamatan_id');

        if (! $kecamatanId) {
            return;
        }

        $adminIds = DB::table('users')
            ->join('user_wilayah', 'user_wilayah.user_id', '=', 'users.id')
            ->where('users.role', 'admin_kecamatan')
            ->where('users.is_active', true)
            ->where('user_wilayah.kecamatan_id', $kecamatanId)
            ->distinct()
            ->pluck('users.id');

        if ($adminIds->isEmpty()) {
            return;
        }

        $rows = $adminIds->map(fn ($userId): array => [
            'user_id' => $userId,
            'judul' => $judul,
            'pesan' => $pesan,
            'ref_tipe' => $refTipe,
            'ref_id' => $refId,
            'is_read' => false,
            'created_at' => now(),
            'updated_at' => now(),
        ])->all();

        DB::table('notifikasi')->insert($rows);
    }
}
