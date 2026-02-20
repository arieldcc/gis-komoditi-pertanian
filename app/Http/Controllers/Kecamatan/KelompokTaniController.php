<?php

namespace App\Http\Controllers\Kecamatan;

use App\Http\Controllers\Controller;
use App\Support\AutoCodeService;
use Illuminate\Database\QueryException;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\Rule;
use Illuminate\View\View;

class KelompokTaniController extends Controller
{
    public function index(): View
    {
        return view('kecamatan.kelompok_tani.index');
    }

    public function options(): JsonResponse
    {
        $items = DB::table('master_kelompok_tani')
            ->where('is_active', true)
            ->orderBy('nama_kelompok')
            ->get(['nama_kelompok'])
            ->map(fn ($row): array => [
                'value' => (string) $row->nama_kelompok,
                'label' => (string) $row->nama_kelompok,
            ])
            ->toArray();

        return response()->json($items);
    }

    public function store(Request $request): RedirectResponse
    {
        $data = $request->validate([
            'nama_kelompok' => ['required', 'string', 'max:120', 'unique:master_kelompok_tani,nama_kelompok'],
            'deskripsi' => ['nullable', 'string'],
            'is_active' => ['nullable', 'boolean'],
        ]);

        DB::transaction(function () use ($data): void {
            DB::table('master_kelompok_tani')->insert([
                'kode_kelompok' => AutoCodeService::nextKelompokTaniCode(),
                'nama_kelompok' => $data['nama_kelompok'],
                'deskripsi' => $data['deskripsi'] ?? null,
                'is_active' => (bool) ($data['is_active'] ?? false),
                'created_at' => now(),
                'updated_at' => now(),
            ]);
        });

        return back()->with('success', 'Master kelompok tani berhasil ditambahkan.');
    }

    public function update(Request $request, int $id): RedirectResponse
    {
        $data = $request->validate([
            'nama_kelompok' => ['required', 'string', 'max:120', Rule::unique('master_kelompok_tani', 'nama_kelompok')->ignore($id)],
            'deskripsi' => ['nullable', 'string'],
            'is_active' => ['nullable', 'boolean'],
        ]);

        DB::table('master_kelompok_tani')->where('id', $id)->update([
            'nama_kelompok' => $data['nama_kelompok'],
            'deskripsi' => $data['deskripsi'] ?? null,
            'is_active' => (bool) ($data['is_active'] ?? false),
            'updated_at' => now(),
        ]);

        return back()->with('success', 'Master kelompok tani berhasil diperbarui.');
    }

    public function destroy(int $id): RedirectResponse
    {
        try {
            DB::table('master_kelompok_tani')->where('id', $id)->delete();
        } catch (QueryException) {
            return back()->with('error', 'Master kelompok tani tidak dapat dihapus.');
        }

        return back()->with('success', 'Master kelompok tani berhasil dihapus.');
    }
}
