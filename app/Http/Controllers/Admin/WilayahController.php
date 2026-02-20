<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Support\AutoCodeService;
use App\Support\MapStyleSupport;
use Illuminate\Database\QueryException;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\Rule;
use Illuminate\View\View;

class WilayahController extends Controller
{
    public function kecamatanOptions(): JsonResponse
    {
        $items = DB::table('kecamatan')
            ->orderBy('nama_kecamatan')
            ->get(['id', 'nama_kecamatan'])
            ->map(fn ($row): array => [
                'value' => (string) $row->id,
                'label' => $row->nama_kecamatan,
            ])
            ->toArray();

        return response()->json($items);
    }

    public function index(): View
    {
        $kecamatan = DB::table('kecamatan')->orderBy('nama_kecamatan')->get();
        $desa = DB::table('desa')
            ->join('kecamatan', 'kecamatan.id', '=', 'desa.kecamatan_id')
            ->select('desa.*', 'kecamatan.nama_kecamatan')
            ->orderBy('kecamatan.nama_kecamatan')
            ->orderBy('desa.nama_desa')
            ->get();

        MapStyleSupport::ensureDefaultRows();
        $mapStyles = MapStyleSupport::styleMap();

        $kecamatanMarkers = DB::table('kecamatan')
            ->whereNotNull('centroid_lat')
            ->whereNotNull('centroid_lng')
            ->select('id', 'kode_kecamatan', 'nama_kecamatan', 'alamat', 'centroid_lat as latitude', 'centroid_lng as longitude')
            ->get()
            ->map(fn ($item) => [
                'lat' => (float) $item->latitude,
                'lng' => (float) $item->longitude,
                'title' => 'Kecamatan '.$item->nama_kecamatan,
                'description' => 'Kode: '.$item->kode_kecamatan,
                'style_code' => 'entity:kecamatan',
                'style' => MapStyleSupport::iconForStyleCode('entity:kecamatan', $mapStyles),
                'fields' => [
                    ['label' => 'Nama Kecamatan', 'value' => $item->nama_kecamatan],
                    ['label' => 'Kode', 'value' => $item->kode_kecamatan],
                    ['label' => 'Alamat', 'value' => $item->alamat],
                ],
            ])
            ->toArray();

        $desaMarkers = DB::table('desa')
            ->join('kecamatan', 'kecamatan.id', '=', 'desa.kecamatan_id')
            ->whereNotNull('desa.centroid_lat')
            ->whereNotNull('desa.centroid_lng')
            ->select('desa.id', 'desa.kode_desa', 'desa.nama_desa', 'desa.alamat', 'desa.centroid_lat as latitude', 'desa.centroid_lng as longitude', 'kecamatan.nama_kecamatan')
            ->get()
            ->map(fn ($item) => [
                'lat' => (float) $item->latitude,
                'lng' => (float) $item->longitude,
                'title' => 'Desa '.$item->nama_desa,
                'description' => ($item->kode_desa ? 'Kode: '.$item->kode_desa.' | ' : '').'Kec. '.$item->nama_kecamatan,
                'style_code' => 'entity:desa',
                'style' => MapStyleSupport::iconForStyleCode('entity:desa', $mapStyles),
                'fields' => [
                    ['label' => 'Nama Desa', 'value' => $item->nama_desa],
                    ['label' => 'Kecamatan', 'value' => $item->nama_kecamatan],
                    ['label' => 'Kode Desa', 'value' => $item->kode_desa],
                    ['label' => 'Alamat', 'value' => $item->alamat],
                ],
            ])
            ->toArray();

        return view('admin.wilayah.index', compact('kecamatan', 'desa', 'kecamatanMarkers', 'desaMarkers', 'mapStyles'));
    }

    public function storeKecamatan(Request $request): RedirectResponse
    {
        $data = $request->validate([
            'nama_kecamatan' => ['required', 'string', 'max:120', 'unique:kecamatan,nama_kecamatan'],
            'alamat' => ['nullable', 'string'],
            'centroid_lat' => ['nullable', 'numeric'],
            'centroid_lng' => ['nullable', 'numeric'],
            'is_active' => ['nullable', 'boolean'],
        ]);

        DB::transaction(function () use ($data): void {
            DB::table('kecamatan')->insert([
                ...$data,
                'kode_kecamatan' => AutoCodeService::nextKecamatanCode(),
                'is_active' => (bool) ($data['is_active'] ?? false),
                'created_at' => now(),
                'updated_at' => now(),
            ]);
        });

        return back()->with('success', 'Kecamatan berhasil ditambahkan.');
    }

    public function updateKecamatan(Request $request, int $id): RedirectResponse
    {
        $data = $request->validate([
            'nama_kecamatan' => ['required', 'string', 'max:120', Rule::unique('kecamatan', 'nama_kecamatan')->ignore($id)],
            'alamat' => ['nullable', 'string'],
            'centroid_lat' => ['nullable', 'numeric'],
            'centroid_lng' => ['nullable', 'numeric'],
            'is_active' => ['nullable', 'boolean'],
        ]);

        DB::table('kecamatan')->where('id', $id)->update([
            ...$data,
            'is_active' => (bool) ($data['is_active'] ?? false),
            'updated_at' => now(),
        ]);

        return back()->with('success', 'Kecamatan berhasil diperbarui.');
    }

    public function destroyKecamatan(int $id): RedirectResponse
    {
        try {
            DB::table('kecamatan')->where('id', $id)->delete();
        } catch (QueryException) {
            return back()->with('error', 'Kecamatan tidak dapat dihapus karena masih digunakan tabel lain.');
        }

        return back()->with('success', 'Kecamatan berhasil dihapus.');
    }

    public function storeDesa(Request $request): RedirectResponse
    {
        $data = $request->validate([
            'kecamatan_id' => ['required', 'exists:kecamatan,id'],
            'nama_desa' => ['required', 'string', 'max:120'],
            'alamat' => ['nullable', 'string'],
            'centroid_lat' => ['nullable', 'numeric'],
            'centroid_lng' => ['nullable', 'numeric'],
            'is_active' => ['nullable', 'boolean'],
        ]);

        $inserted = DB::transaction(function () use ($data): bool {
            $existsName = DB::table('desa')
                ->where('kecamatan_id', $data['kecamatan_id'])
                ->where('nama_desa', $data['nama_desa'])
                ->lockForUpdate()
                ->exists();

            if ($existsName) {
                return false;
            }

            DB::table('desa')->insert([
                ...$data,
                'kode_desa' => AutoCodeService::nextDesaCode((int) $data['kecamatan_id']),
                'is_active' => (bool) ($data['is_active'] ?? false),
                'created_at' => now(),
                'updated_at' => now(),
            ]);

            return true;
        });

        if (! $inserted) {
            return back()->with('error', 'Nama desa sudah ada pada kecamatan tersebut.');
        }

        return back()->with('success', 'Desa berhasil ditambahkan.');
    }

    public function updateDesa(Request $request, int $id): RedirectResponse
    {
        $data = $request->validate([
            'kecamatan_id' => ['required', 'exists:kecamatan,id'],
            'nama_desa' => ['required', 'string', 'max:120'],
            'alamat' => ['nullable', 'string'],
            'centroid_lat' => ['nullable', 'numeric'],
            'centroid_lng' => ['nullable', 'numeric'],
            'is_active' => ['nullable', 'boolean'],
        ]);

        $updated = DB::transaction(function () use ($data, $id): bool {
            $current = DB::table('desa')
                ->select('id', 'kecamatan_id', 'kode_desa')
                ->where('id', $id)
                ->lockForUpdate()
                ->first();

            if (! $current) {
                return false;
            }

            $existsName = DB::table('desa')
                ->where('kecamatan_id', $data['kecamatan_id'])
                ->where('nama_desa', $data['nama_desa'])
                ->where('id', '!=', $id)
                ->lockForUpdate()
                ->exists();

            if ($existsName) {
                return false;
            }

            $code = (string) ($current->kode_desa ?? '');
            $kecamatanChanged = (int) $current->kecamatan_id !== (int) $data['kecamatan_id'];

            if ($kecamatanChanged || $code === '') {
                $code = AutoCodeService::nextDesaCode((int) $data['kecamatan_id']);
            } else {
                $codeConflict = DB::table('desa')
                    ->where('kecamatan_id', $data['kecamatan_id'])
                    ->where('kode_desa', $code)
                    ->where('id', '!=', $id)
                    ->lockForUpdate()
                    ->exists();

                if ($codeConflict) {
                    $code = AutoCodeService::nextDesaCode((int) $data['kecamatan_id']);
                }
            }

            DB::table('desa')->where('id', $id)->update([
                ...$data,
                'kode_desa' => $code,
                'is_active' => (bool) ($data['is_active'] ?? false),
                'updated_at' => now(),
            ]);

            return true;
        });

        if (! $updated) {
            return back()->with('error', 'Data desa tidak valid atau nama desa sudah digunakan.');
        }

        return back()->with('success', 'Desa berhasil diperbarui.');
    }

    public function destroyDesa(int $id): RedirectResponse
    {
        try {
            DB::table('desa')->where('id', $id)->delete();
        } catch (QueryException) {
            return back()->with('error', 'Desa tidak dapat dihapus karena masih digunakan tabel lain.');
        }

        return back()->with('success', 'Desa berhasil dihapus.');
    }
}
