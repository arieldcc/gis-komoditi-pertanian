<?php

namespace App\Http\Controllers\Kecamatan;

use App\Http\Controllers\Controller;
use App\Support\MapStyleSupport;
use Illuminate\Database\QueryException;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Storage;
use Illuminate\Validation\Rule;
use Illuminate\View\View;

class PetaniLahanController extends Controller
{
    private function managedKecamatanIds(): array
    {
        return DB::table('user_wilayah')
            ->where('user_id', auth()->id())
            ->pluck('kecamatan_id')
            ->map(fn ($x) => (int) $x)
            ->toArray();
    }

    public function index(): View
    {
        $kecamatanIds = $this->managedKecamatanIds();

        $desa = DB::table('desa')
            ->join('kecamatan', 'kecamatan.id', '=', 'desa.kecamatan_id')
            ->whereIn('desa.kecamatan_id', $kecamatanIds)
            ->select('desa.*', 'kecamatan.nama_kecamatan')
            ->orderBy('kecamatan.nama_kecamatan')
            ->orderBy('desa.nama_desa')
            ->get();

        $petani = DB::table('petani')
            ->join('desa', 'desa.id', '=', 'petani.desa_id')
            ->join('kecamatan', 'kecamatan.id', '=', 'desa.kecamatan_id')
            ->whereIn('desa.kecamatan_id', $kecamatanIds)
            ->select('petani.*', 'desa.nama_desa', 'kecamatan.nama_kecamatan')
            ->orderBy('petani.nama_petani')
            ->get();

        $lahan = DB::table('lahan')
            ->join('petani', 'petani.id', '=', 'lahan.petani_id')
            ->join('desa', 'desa.id', '=', 'lahan.desa_id')
            ->join('kecamatan', 'kecamatan.id', '=', 'desa.kecamatan_id')
            ->whereIn('desa.kecamatan_id', $kecamatanIds)
            ->select('lahan.*', 'petani.nama_petani', 'desa.nama_desa', 'kecamatan.nama_kecamatan')
            ->orderByDesc('lahan.id')
            ->get();

        $komoditas = DB::table('komoditas')->orderBy('nama_komoditas')->get();
        $kelompokTani = DB::table('master_kelompok_tani')
            ->where('is_active', true)
            ->orderBy('nama_kelompok')
            ->get(['nama_kelompok']);

        $lahanKomoditas = DB::table('lahan_komoditas')
            ->join('lahan', 'lahan.id', '=', 'lahan_komoditas.lahan_id')
            ->join('petani', 'petani.id', '=', 'lahan.petani_id')
            ->join('desa', 'desa.id', '=', 'lahan.desa_id')
            ->join('komoditas', 'komoditas.id', '=', 'lahan_komoditas.komoditas_id')
            ->whereIn('desa.kecamatan_id', $kecamatanIds)
            ->select('lahan_komoditas.*', 'petani.nama_petani', 'komoditas.nama_komoditas')
            ->orderByDesc('lahan_komoditas.id')
            ->get();

        MapStyleSupport::ensureDefaultRows();
        $mapStyles = MapStyleSupport::styleMap();

        $lahanMarkers = DB::table('lahan')
            ->join('petani', 'petani.id', '=', 'lahan.petani_id')
            ->join('desa', 'desa.id', '=', 'lahan.desa_id')
            ->leftJoin('lahan_komoditas', 'lahan_komoditas.lahan_id', '=', 'lahan.id')
            ->leftJoin('komoditas', 'komoditas.id', '=', 'lahan_komoditas.komoditas_id')
            ->whereIn('desa.kecamatan_id', $kecamatanIds)
            ->whereNotNull('lahan.latitude')
            ->whereNotNull('lahan.longitude')
            ->select(
                'lahan.id',
                'lahan.latitude',
                'lahan.longitude',
                'lahan.alamat_lahan',
                'lahan.foto_lahan_url',
                'petani.nama_petani',
                'desa.nama_desa',
                'komoditas.id as komoditas_id',
                'komoditas.nama_komoditas'
            )
            ->orderByDesc('lahan.id')
            ->get()
            ->map(function ($item) use ($mapStyles) {
                $description = 'Petani: '.$item->nama_petani.' | Desa: '.$item->nama_desa;
                if ($item->nama_komoditas) {
                    $description .= ' | Komoditas: '.$item->nama_komoditas;
                }

                $styleCode = $item->komoditas_id ? 'komoditas:'.$item->komoditas_id : 'entity:komoditas_default';

                return [
                    'lat' => (float) $item->latitude,
                    'lng' => (float) $item->longitude,
                    'title' => 'Lahan '.$item->nama_petani,
                    'description' => trim(($item->alamat_lahan ? $item->alamat_lahan.' | ' : '').$description),
                    'style_code' => $styleCode,
                    'style' => MapStyleSupport::iconForStyleCode($styleCode, $mapStyles),
                    'image_url' => $item->foto_lahan_url,
                    'fields' => [
                        ['label' => 'Petani', 'value' => $item->nama_petani],
                        ['label' => 'Desa', 'value' => $item->nama_desa],
                        ['label' => 'Komoditas', 'value' => $item->nama_komoditas],
                    ],
                ];
            })
            ->toArray();

        $komoditasMarkers = DB::table('lahan_komoditas')
            ->join('lahan', 'lahan.id', '=', 'lahan_komoditas.lahan_id')
            ->join('petani', 'petani.id', '=', 'lahan.petani_id')
            ->join('desa', 'desa.id', '=', 'lahan.desa_id')
            ->join('kecamatan', 'kecamatan.id', '=', 'desa.kecamatan_id')
            ->join('komoditas', 'komoditas.id', '=', 'lahan_komoditas.komoditas_id')
            ->whereIn('desa.kecamatan_id', $kecamatanIds)
            ->whereNotNull('lahan_komoditas.latitude')
            ->whereNotNull('lahan_komoditas.longitude')
            ->select(
                'lahan_komoditas.latitude',
                'lahan_komoditas.longitude',
                'lahan_komoditas.alamat_titik',
                'lahan.foto_lahan_url',
                'petani.nama_petani',
                'desa.nama_desa',
                'kecamatan.nama_kecamatan',
                'komoditas.id as komoditas_id',
                'komoditas.nama_komoditas'
            )
            ->orderBy('komoditas.nama_komoditas')
            ->get()
            ->map(function ($item) use ($mapStyles): array {
                $styleCode = 'komoditas:'.$item->komoditas_id;

                return [
                    'lat' => (float) $item->latitude,
                    'lng' => (float) $item->longitude,
                    'title' => 'Komoditas '.$item->nama_komoditas,
                    'description' => trim(($item->alamat_titik ? $item->alamat_titik.' | ' : '').'Petani: '.$item->nama_petani),
                    'komoditas_id' => (string) $item->komoditas_id,
                    'style_code' => $styleCode,
                    'style' => MapStyleSupport::iconForStyleCode($styleCode, $mapStyles),
                    'image_url' => $item->foto_lahan_url,
                    'fields' => [
                        ['label' => 'Komoditas', 'value' => $item->nama_komoditas],
                        ['label' => 'Petani', 'value' => $item->nama_petani],
                        ['label' => 'Desa', 'value' => $item->nama_desa],
                        ['label' => 'Kecamatan', 'value' => $item->nama_kecamatan],
                    ],
                ];
            })
            ->toArray();

        return view('kecamatan.petani_lahan.index', compact(
            'desa',
            'petani',
            'lahan',
            'komoditas',
            'kelompokTani',
            'lahanKomoditas',
            'mapStyles',
            'lahanMarkers',
            'komoditasMarkers'
        ));
    }

    public function storePetani(Request $request): RedirectResponse
    {
        $data = $request->validate([
            'desa_id' => ['required', 'exists:desa,id'],
            'nama_petani' => ['required', 'string', 'max:150'],
            'no_hp' => ['nullable', 'string', 'max:30'],
            'alamat_domisili' => ['nullable', 'string'],
            'kelompok_tani' => ['nullable', 'string', 'max:120', Rule::exists('master_kelompok_tani', 'nama_kelompok')],
            'foto_petani' => ['required', 'image', 'mimes:jpg,jpeg,png,webp', 'max:5120'],
            'is_active' => ['nullable', 'boolean'],
        ]);

        $fotoPetaniUrl = null;
        if ($request->hasFile('foto_petani')) {
            $path = $request->file('foto_petani')->store('petani/foto', 'public');
            $fotoPetaniUrl = Storage::url($path);
        }

        DB::table('petani')->insert([
            'desa_id' => $data['desa_id'],
            'nik' => null,
            'nama_petani' => $data['nama_petani'],
            'no_hp' => $data['no_hp'] ?? null,
            'alamat_domisili' => $data['alamat_domisili'] ?? null,
            'kelompok_tani' => $data['kelompok_tani'] ?? null,
            'foto_petani_url' => $fotoPetaniUrl,
            'is_active' => (bool) ($data['is_active'] ?? false),
            'created_at' => now(),
            'updated_at' => now(),
        ]);

        return back()->with('success', 'Petani berhasil ditambahkan.');
    }

    public function updatePetani(Request $request, int $id): RedirectResponse
    {
        $existing = DB::table('petani')->where('id', $id)->first(['foto_petani_url']);
        if (! $existing) {
            return back()->with('error', 'Data petani tidak ditemukan.');
        }

        $data = $request->validate([
            'desa_id' => ['required', 'exists:desa,id'],
            'nama_petani' => ['required', 'string', 'max:150'],
            'no_hp' => ['nullable', 'string', 'max:30'],
            'alamat_domisili' => ['nullable', 'string'],
            'kelompok_tani' => ['nullable', 'string', 'max:120', Rule::exists('master_kelompok_tani', 'nama_kelompok')],
            'foto_petani' => ['nullable', 'image', 'mimes:jpg,jpeg,png,webp', 'max:5120'],
            'is_active' => ['nullable', 'boolean'],
        ]);

        $fotoPetaniUrl = $existing->foto_petani_url;
        if ($request->hasFile('foto_petani')) {
            $newPath = $request->file('foto_petani')->store('petani/foto', 'public');
            $fotoPetaniUrl = Storage::url($newPath);

            if (is_string($existing->foto_petani_url) && str_starts_with($existing->foto_petani_url, '/storage/')) {
                $oldPath = str_replace('/storage/', '', $existing->foto_petani_url);
                if ($oldPath !== '' && Storage::disk('public')->exists($oldPath)) {
                    Storage::disk('public')->delete($oldPath);
                }
            }
        }

        DB::table('petani')->where('id', $id)->update([
            'desa_id' => $data['desa_id'],
            'nama_petani' => $data['nama_petani'],
            'no_hp' => $data['no_hp'] ?? null,
            'alamat_domisili' => $data['alamat_domisili'] ?? null,
            'kelompok_tani' => $data['kelompok_tani'] ?? null,
            'foto_petani_url' => $fotoPetaniUrl,
            'is_active' => (bool) ($data['is_active'] ?? false),
            'updated_at' => now(),
        ]);

        return back()->with('success', 'Petani berhasil diperbarui.');
    }

    public function destroyPetani(int $id): RedirectResponse
    {
        $existing = DB::table('petani')->where('id', $id)->first(['foto_petani_url']);
        if (! $existing) {
            return back()->with('error', 'Data petani tidak ditemukan.');
        }

        try {
            DB::table('petani')->where('id', $id)->delete();

            if (is_string($existing->foto_petani_url) && str_starts_with($existing->foto_petani_url, '/storage/')) {
                $oldPath = str_replace('/storage/', '', $existing->foto_petani_url);
                if ($oldPath !== '' && Storage::disk('public')->exists($oldPath)) {
                    Storage::disk('public')->delete($oldPath);
                }
            }
        } catch (QueryException) {
            return back()->with('error', 'Petani tidak dapat dihapus karena masih memiliki data lahan.');
        }

        return back()->with('success', 'Petani berhasil dihapus.');
    }

    public function storeLahan(Request $request): RedirectResponse
    {
        $data = $request->validate([
            'petani_id' => ['required', 'exists:petani,id'],
            'desa_id' => ['required', 'exists:desa,id'],
            'alamat_lahan' => ['nullable', 'string'],
            'luas_ha' => ['nullable', 'numeric'],
            'kondisi_lahan' => ['nullable', 'in:baik,sedang,rusak,kritis'],
            'latitude' => ['nullable', 'numeric'],
            'longitude' => ['nullable', 'numeric'],
            'foto_lahan' => ['required', 'image', 'mimes:jpg,jpeg,png,webp', 'max:5120'],
            'is_active' => ['nullable', 'boolean'],
        ]);

        $fotoLahanUrl = null;
        if ($request->hasFile('foto_lahan')) {
            $path = $request->file('foto_lahan')->store('lahan/foto', 'public');
            $fotoLahanUrl = Storage::url($path);
        }

        DB::table('lahan')->insert([
            'petani_id' => $data['petani_id'],
            'desa_id' => $data['desa_id'],
            'alamat_lahan' => $data['alamat_lahan'] ?? null,
            'luas_ha' => $data['luas_ha'] ?? null,
            'kondisi_lahan' => $data['kondisi_lahan'] ?? null,
            'latitude' => $data['latitude'] ?? null,
            'longitude' => $data['longitude'] ?? null,
            'foto_lahan_url' => $fotoLahanUrl,
            'is_active' => (bool) ($data['is_active'] ?? false),
            'created_at' => now(),
            'updated_at' => now(),
        ]);

        return back()->with('success', 'Lahan berhasil ditambahkan.');
    }

    public function updateLahan(Request $request, int $id): RedirectResponse
    {
        $existing = DB::table('lahan')->where('id', $id)->first(['foto_lahan_url']);
        if (! $existing) {
            return back()->with('error', 'Data lahan tidak ditemukan.');
        }

        $data = $request->validate([
            'petani_id' => ['required', 'exists:petani,id'],
            'desa_id' => ['required', 'exists:desa,id'],
            'alamat_lahan' => ['nullable', 'string'],
            'luas_ha' => ['nullable', 'numeric'],
            'kondisi_lahan' => ['nullable', 'in:baik,sedang,rusak,kritis'],
            'latitude' => ['nullable', 'numeric'],
            'longitude' => ['nullable', 'numeric'],
            'foto_lahan' => ['nullable', 'image', 'mimes:jpg,jpeg,png,webp', 'max:5120'],
            'is_active' => ['nullable', 'boolean'],
        ]);

        $fotoLahanUrl = $existing->foto_lahan_url;
        if ($request->hasFile('foto_lahan')) {
            $newPath = $request->file('foto_lahan')->store('lahan/foto', 'public');
            $fotoLahanUrl = Storage::url($newPath);

            if (is_string($existing->foto_lahan_url) && str_starts_with($existing->foto_lahan_url, '/storage/')) {
                $oldPath = str_replace('/storage/', '', $existing->foto_lahan_url);
                if ($oldPath !== '' && Storage::disk('public')->exists($oldPath)) {
                    Storage::disk('public')->delete($oldPath);
                }
            }
        }

        DB::table('lahan')->where('id', $id)->update([
            'petani_id' => $data['petani_id'],
            'desa_id' => $data['desa_id'],
            'alamat_lahan' => $data['alamat_lahan'] ?? null,
            'luas_ha' => $data['luas_ha'] ?? null,
            'kondisi_lahan' => $data['kondisi_lahan'] ?? null,
            'latitude' => $data['latitude'] ?? null,
            'longitude' => $data['longitude'] ?? null,
            'foto_lahan_url' => $fotoLahanUrl,
            'is_active' => (bool) ($data['is_active'] ?? false),
            'updated_at' => now(),
        ]);

        return back()->with('success', 'Lahan berhasil diperbarui.');
    }

    public function destroyLahan(int $id): RedirectResponse
    {
        $existing = DB::table('lahan')->where('id', $id)->first(['foto_lahan_url']);
        if (! $existing) {
            return back()->with('error', 'Data lahan tidak ditemukan.');
        }

        try {
            DB::table('lahan')->where('id', $id)->delete();

            if (is_string($existing->foto_lahan_url) && str_starts_with($existing->foto_lahan_url, '/storage/')) {
                $oldPath = str_replace('/storage/', '', $existing->foto_lahan_url);
                if ($oldPath !== '' && Storage::disk('public')->exists($oldPath)) {
                    Storage::disk('public')->delete($oldPath);
                }
            }
        } catch (QueryException) {
            return back()->with('error', 'Lahan tidak dapat dihapus karena masih dipakai penugasan/komoditas.');
        }

        return back()->with('success', 'Lahan berhasil dihapus.');
    }

    public function storeLahanKomoditas(Request $request): RedirectResponse
    {
        $data = $request->validate([
            'lahan_id' => ['required', 'exists:lahan,id'],
            'komoditas_id' => ['required', 'exists:komoditas,id'],
            'tahun_tanam' => ['nullable', 'integer', 'between:1900,2100'],
            'luas_tanam_ha' => ['nullable', 'numeric'],
            'status_tanam' => ['required', 'in:rencana,tanam,panen,bera,gagal'],
            'catatan' => ['nullable', 'string'],
            'latitude' => ['required', 'numeric'],
            'longitude' => ['required', 'numeric'],
            'alamat_titik' => ['nullable', 'string'],
        ]);

        $exists = DB::table('lahan_komoditas')
            ->where('lahan_id', $data['lahan_id'])
            ->where('komoditas_id', $data['komoditas_id'])
            ->where('tahun_tanam', $data['tahun_tanam'])
            ->exists();

        if ($exists) {
            return back()->with('error', 'Kombinasi lahan, komoditas, dan tahun tanam sudah ada.');
        }

        DB::table('lahan_komoditas')->insert([
            ...$data,
            'created_at' => now(),
            'updated_at' => now(),
        ]);

        return back()->with('success', 'Data lahan komoditas berhasil ditambahkan.');
    }

    public function updateLahanKomoditas(Request $request, int $id): RedirectResponse
    {
        $data = $request->validate([
            'tahun_tanam' => ['nullable', 'integer', 'between:1900,2100'],
            'luas_tanam_ha' => ['nullable', 'numeric'],
            'status_tanam' => ['required', 'in:rencana,tanam,panen,bera,gagal'],
            'catatan' => ['nullable', 'string'],
            'latitude' => ['required', 'numeric'],
            'longitude' => ['required', 'numeric'],
            'alamat_titik' => ['nullable', 'string'],
        ]);

        DB::table('lahan_komoditas')->where('id', $id)->update([
            ...$data,
            'updated_at' => now(),
        ]);

        return back()->with('success', 'Data lahan komoditas berhasil diperbarui.');
    }

    public function destroyLahanKomoditas(int $id): RedirectResponse
    {
        try {
            DB::table('lahan_komoditas')->where('id', $id)->delete();
        } catch (QueryException) {
            return back()->with('error', 'Data lahan komoditas tidak dapat dihapus karena masih dipakai produksi panen.');
        }

        return back()->with('success', 'Data lahan komoditas berhasil dihapus.');
    }
}
