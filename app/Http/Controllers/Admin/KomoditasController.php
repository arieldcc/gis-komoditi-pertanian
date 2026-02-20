<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Support\AutoCodeService;
use App\Support\MapStyleSupport;
use Illuminate\Database\QueryException;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;
use Illuminate\Support\Facades\DB;
use Illuminate\View\View;

class KomoditasController extends Controller
{
    public function index(): View
    {
        $komoditas = DB::table('komoditas')->orderBy('nama_komoditas')->get();

        return view('admin.komoditas.index', compact('komoditas'));
    }

    public function store(Request $request): RedirectResponse
    {
        $data = $request->validate([
            'nama_komoditas' => ['required', 'string', 'max:120', 'unique:komoditas,nama_komoditas'],
            'satuan_default' => ['required', 'string', 'max:30'],
            'is_active' => ['nullable', 'boolean'],
        ]);

        DB::transaction(function () use ($data): void {
            $id = DB::table('komoditas')->insertGetId([
                ...$data,
                'kode_komoditas' => AutoCodeService::nextKomoditasCode(),
                'is_active' => (bool) ($data['is_active'] ?? false),
                'created_at' => now(),
                'updated_at' => now(),
            ]);

            DB::table('map_marker_styles')->updateOrInsert(
                ['style_code' => 'komoditas:'.$id],
                [
                    'scope' => 'komoditas',
                    'komoditas_id' => $id,
                    ...MapStyleSupport::defaultKomoditasStyle($data['nama_komoditas']),
                    'created_at' => now(),
                    'updated_at' => now(),
                ]
            );
        });

        return back()->with('success', 'Komoditas berhasil ditambahkan.');
    }

    public function update(Request $request, int $id): RedirectResponse
    {
        $data = $request->validate([
            'nama_komoditas' => ['required', 'string', 'max:120', Rule::unique('komoditas', 'nama_komoditas')->ignore($id)],
            'satuan_default' => ['required', 'string', 'max:30'],
            'is_active' => ['nullable', 'boolean'],
        ]);

        DB::table('komoditas')->where('id', $id)->update([
            ...$data,
            'is_active' => (bool) ($data['is_active'] ?? false),
            'updated_at' => now(),
        ]);

        return back()->with('success', 'Komoditas berhasil diperbarui.');
    }

    public function destroy(int $id): RedirectResponse
    {
        try {
            DB::table('map_marker_styles')->where('style_code', 'komoditas:'.$id)->delete();
            DB::table('komoditas')->where('id', $id)->delete();
        } catch (QueryException) {
            return back()->with('error', 'Komoditas tidak dapat dihapus karena sudah terpakai di data lahan.');
        }

        return back()->with('success', 'Komoditas berhasil dihapus.');
    }
}
