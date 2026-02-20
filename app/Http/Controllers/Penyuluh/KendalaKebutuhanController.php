<?php

namespace App\Http\Controllers\Penyuluh;

use App\Http\Controllers\Controller;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\Rule;
use Illuminate\View\View;

class KendalaKebutuhanController extends Controller
{
    private function penyuluhId(): ?int
    {
        return DB::table('penyuluh')->where('user_id', auth()->id())->value('id');
    }

    public function index(): View
    {
        $penyuluhId = $this->penyuluhId();

        $kunjungan = collect();
        $kategoriKendala = DB::table('kategori_kendala')->orderBy('nama_kategori')->get();
        $kategoriKebutuhan = DB::table('kategori_kebutuhan')->orderBy('nama_kategori')->get();
        $kendala = collect();
        $kebutuhan = collect();

        if ($penyuluhId) {
            $kunjungan = DB::table('kunjungan_monitoring')
                ->join('penugasan_penyuluh', 'penugasan_penyuluh.id', '=', 'kunjungan_monitoring.penugasan_id')
                ->join('lahan', 'lahan.id', '=', 'penugasan_penyuluh.lahan_id')
                ->join('petani', 'petani.id', '=', 'lahan.petani_id')
                ->where('penugasan_penyuluh.penyuluh_id', $penyuluhId)
                ->select('kunjungan_monitoring.id', 'petani.nama_petani', 'kunjungan_monitoring.tanggal_kunjungan')
                ->orderByDesc('kunjungan_monitoring.tanggal_kunjungan')
                ->get();

            $kendala = DB::table('kendala_kunjungan')
                ->join('kunjungan_monitoring', 'kunjungan_monitoring.id', '=', 'kendala_kunjungan.kunjungan_id')
                ->join('penugasan_penyuluh', 'penugasan_penyuluh.id', '=', 'kunjungan_monitoring.penugasan_id')
                ->join('kategori_kendala', 'kategori_kendala.id', '=', 'kendala_kunjungan.kategori_kendala_id')
                ->join('lahan', 'lahan.id', '=', 'penugasan_penyuluh.lahan_id')
                ->join('petani', 'petani.id', '=', 'lahan.petani_id')
                ->where('penugasan_penyuluh.penyuluh_id', $penyuluhId)
                ->select('kendala_kunjungan.*', 'kategori_kendala.nama_kategori', 'petani.nama_petani')
                ->orderByDesc('kendala_kunjungan.id')
                ->get();

            $kebutuhan = DB::table('kebutuhan_kunjungan')
                ->join('kunjungan_monitoring', 'kunjungan_monitoring.id', '=', 'kebutuhan_kunjungan.kunjungan_id')
                ->join('penugasan_penyuluh', 'penugasan_penyuluh.id', '=', 'kunjungan_monitoring.penugasan_id')
                ->join('kategori_kebutuhan', 'kategori_kebutuhan.id', '=', 'kebutuhan_kunjungan.kategori_kebutuhan_id')
                ->join('lahan', 'lahan.id', '=', 'penugasan_penyuluh.lahan_id')
                ->join('petani', 'petani.id', '=', 'lahan.petani_id')
                ->where('penugasan_penyuluh.penyuluh_id', $penyuluhId)
                ->select('kebutuhan_kunjungan.*', 'kategori_kebutuhan.nama_kategori', 'petani.nama_petani')
                ->orderByDesc('kebutuhan_kunjungan.id')
                ->get();
        }

        return view('penyuluh.kendala.index', compact('penyuluhId', 'kunjungan', 'kategoriKendala', 'kategoriKebutuhan', 'kendala', 'kebutuhan'));
    }

    public function storeKendala(Request $request): RedirectResponse
    {
        $data = $request->validate([
            'kunjungan_id' => ['required', 'exists:kunjungan_monitoring,id'],
            'kategori_kendala_id' => ['required', 'exists:kategori_kendala,id'],
            'deskripsi_kendala' => ['required', 'string'],
            'tingkat_keparahan' => ['required', Rule::in(['rendah', 'sedang', 'tinggi', 'kritis'])],
            'perlu_tindak_lanjut' => ['nullable', 'boolean'],
        ]);

        DB::table('kendala_kunjungan')->insert([
            ...$data,
            'perlu_tindak_lanjut' => (bool) ($data['perlu_tindak_lanjut'] ?? false),
            'created_at' => now(),
            'updated_at' => now(),
        ]);

        return back()->with('success', 'Data kendala berhasil ditambahkan.');
    }

    public function destroyKendala(int $id): RedirectResponse
    {
        DB::table('kendala_kunjungan')->where('id', $id)->delete();

        return back()->with('success', 'Data kendala berhasil dihapus.');
    }

    public function updateKendala(Request $request, int $id): RedirectResponse
    {
        $data = $request->validate([
            'kunjungan_id' => ['required', 'exists:kunjungan_monitoring,id'],
            'kategori_kendala_id' => ['required', 'exists:kategori_kendala,id'],
            'deskripsi_kendala' => ['required', 'string'],
            'tingkat_keparahan' => ['required', Rule::in(['rendah', 'sedang', 'tinggi', 'kritis'])],
            'perlu_tindak_lanjut' => ['nullable', 'boolean'],
        ]);

        DB::table('kendala_kunjungan')->where('id', $id)->update([
            ...$data,
            'perlu_tindak_lanjut' => (bool) ($data['perlu_tindak_lanjut'] ?? false),
            'updated_at' => now(),
        ]);

        return back()->with('success', 'Data kendala berhasil diperbarui.');
    }

    public function storeKebutuhan(Request $request): RedirectResponse
    {
        $data = $request->validate([
            'kunjungan_id' => ['required', 'exists:kunjungan_monitoring,id'],
            'kategori_kebutuhan_id' => ['required', 'exists:kategori_kebutuhan,id'],
            'deskripsi_kebutuhan' => ['required', 'string'],
            'jumlah' => ['nullable', 'numeric', 'min:0'],
            'satuan' => ['nullable', 'string', 'max:30'],
            'prioritas' => ['required', Rule::in(['rendah', 'sedang', 'tinggi'])],
        ]);

        DB::table('kebutuhan_kunjungan')->insert([
            ...$data,
            'created_at' => now(),
            'updated_at' => now(),
        ]);

        return back()->with('success', 'Data kebutuhan berhasil ditambahkan.');
    }

    public function destroyKebutuhan(int $id): RedirectResponse
    {
        DB::table('kebutuhan_kunjungan')->where('id', $id)->delete();

        return back()->with('success', 'Data kebutuhan berhasil dihapus.');
    }

    public function updateKebutuhan(Request $request, int $id): RedirectResponse
    {
        $data = $request->validate([
            'kunjungan_id' => ['required', 'exists:kunjungan_monitoring,id'],
            'kategori_kebutuhan_id' => ['required', 'exists:kategori_kebutuhan,id'],
            'deskripsi_kebutuhan' => ['required', 'string'],
            'jumlah' => ['nullable', 'numeric', 'min:0'],
            'satuan' => ['nullable', 'string', 'max:30'],
            'prioritas' => ['required', Rule::in(['rendah', 'sedang', 'tinggi'])],
        ]);

        DB::table('kebutuhan_kunjungan')->where('id', $id)->update([
            ...$data,
            'updated_at' => now(),
        ]);

        return back()->with('success', 'Data kebutuhan berhasil diperbarui.');
    }
}
