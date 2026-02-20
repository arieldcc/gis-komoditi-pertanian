<?php

namespace App\Http\Controllers\Kecamatan;

use App\Http\Controllers\Controller;
use App\Models\User;
use Illuminate\Database\QueryException;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Storage;
use Illuminate\Validation\Rule;
use Illuminate\View\View;

class PenyuluhController extends Controller
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

        $balai = DB::table('balai_penyuluh')
            ->whereIn('kecamatan_id', $kecamatanIds)
            ->orderBy('nama_balai')
            ->get();

        $penyuluh = DB::table('penyuluh')
            ->join('users', 'users.id', '=', 'penyuluh.user_id')
            ->join('balai_penyuluh', 'balai_penyuluh.id', '=', 'penyuluh.balai_id')
            ->whereIn('balai_penyuluh.kecamatan_id', $kecamatanIds)
            ->select('penyuluh.*', 'users.name as nama_user', 'users.email', 'balai_penyuluh.nama_balai')
            ->orderBy('users.name')
            ->get();

        $penyuluhOptions = DB::table('penyuluh')
            ->join('users', 'users.id', '=', 'penyuluh.user_id')
            ->join('balai_penyuluh', 'balai_penyuluh.id', '=', 'penyuluh.balai_id')
            ->whereIn('balai_penyuluh.kecamatan_id', $kecamatanIds)
            ->where('penyuluh.is_active', true)
            ->orderBy('users.name')
            ->select('penyuluh.id', 'users.name as nama_penyuluh', 'balai_penyuluh.nama_balai')
            ->get();

        $lahanOptions = DB::table('lahan')
            ->join('petani', 'petani.id', '=', 'lahan.petani_id')
            ->join('desa', 'desa.id', '=', 'lahan.desa_id')
            ->whereIn('desa.kecamatan_id', $kecamatanIds)
            ->where('lahan.is_active', true)
            ->orderBy('petani.nama_petani')
            ->select('lahan.id', 'petani.nama_petani', 'desa.nama_desa', 'lahan.luas_ha')
            ->get();

        return view('kecamatan.penyuluh.index', compact('balai', 'penyuluh', 'penyuluhOptions', 'lahanOptions'));
    }

    public function options(): JsonResponse
    {
        $kecamatanIds = $this->managedKecamatanIds();

        $rows = DB::table('penyuluh')
            ->join('users', 'users.id', '=', 'penyuluh.user_id')
            ->join('balai_penyuluh', 'balai_penyuluh.id', '=', 'penyuluh.balai_id')
            ->whereIn('balai_penyuluh.kecamatan_id', $kecamatanIds)
            ->where('penyuluh.is_active', true)
            ->orderBy('users.name')
            ->select('penyuluh.id', 'users.name as nama_penyuluh', 'balai_penyuluh.nama_balai')
            ->get()
            ->map(fn ($row): array => [
                'value' => (string) $row->id,
                'label' => $row->nama_penyuluh.' ('.$row->nama_balai.')',
            ])
            ->toArray();

        return response()->json($rows);
    }

    public function lahanOptions(): JsonResponse
    {
        $kecamatanIds = $this->managedKecamatanIds();

        $rows = DB::table('lahan')
            ->join('petani', 'petani.id', '=', 'lahan.petani_id')
            ->join('desa', 'desa.id', '=', 'lahan.desa_id')
            ->whereIn('desa.kecamatan_id', $kecamatanIds)
            ->where('lahan.is_active', true)
            ->orderBy('petani.nama_petani')
            ->select('lahan.id', 'petani.nama_petani', 'desa.nama_desa', 'lahan.luas_ha')
            ->get()
            ->map(fn ($row): array => [
                'value' => (string) $row->id,
                'label' => $row->nama_petani.' - '.$row->nama_desa.' ('.$row->luas_ha.' ha)',
            ])
            ->toArray();

        return response()->json($rows);
    }

    public function store(Request $request): RedirectResponse
    {
        $kecamatanIds = $this->managedKecamatanIds();

        $data = $request->validate([
            'name' => ['required', 'string', 'max:100'],
            'email' => ['required', 'email', 'max:150', 'unique:users,email'],
            'password' => ['required', 'string', 'min:8', 'confirmed'],
            'balai_id' => ['required', 'exists:balai_penyuluh,id'],
            'nip' => ['nullable', 'string', 'max:40', 'unique:penyuluh,nip'],
            'jabatan' => ['nullable', 'string', 'max:120'],
            'lokasi_penugasan' => ['nullable', 'string', 'max:255'],
            'tugas_tambahan' => ['nullable', 'string', 'max:255'],
            'foto_penyuluh' => ['required', 'image', 'mimes:jpg,jpeg,png,webp', 'max:5120'],
            'is_active' => ['nullable', 'boolean'],
        ]);

        $balaiValid = DB::table('balai_penyuluh')
            ->where('id', $data['balai_id'])
            ->whereIn('kecamatan_id', $kecamatanIds)
            ->exists();

        if (! $balaiValid) {
            return back()->with('error', 'Balai tidak termasuk wilayah admin kecamatan Anda.');
        }

        $roleId = DB::table('roles')->where('kode_role', User::ROLE_PENYULUH)->value('id');
        if (! $roleId) {
            return back()->with('error', 'Role penyuluh belum tersedia.');
        }

        $fotoPenyuluhUrl = null;
        if ($request->hasFile('foto_penyuluh')) {
            $path = $request->file('foto_penyuluh')->store('penyuluh/foto', 'public');
            $fotoPenyuluhUrl = Storage::url($path);
        }

        DB::transaction(function () use ($data, $roleId, $fotoPenyuluhUrl): void {
            $now = now();
            $isActive = (bool) ($data['is_active'] ?? false);

            $userId = DB::table('users')->insertGetId([
                'name' => $data['name'],
                'email' => $data['email'],
                'password' => Hash::make($data['password']),
                'role' => User::ROLE_PENYULUH,
                'role_id' => $roleId,
                'is_active' => $isActive,
                'created_at' => $now,
                'updated_at' => $now,
            ]);

            DB::table('penyuluh')->insert([
                'user_id' => $userId,
                'balai_id' => $data['balai_id'],
                'nip' => $data['nip'] ?? null,
                'jabatan' => $data['jabatan'] ?? null,
                'lokasi_penugasan' => $data['lokasi_penugasan'] ?? null,
                'tugas_tambahan' => $data['tugas_tambahan'] ?? null,
                'foto_penyuluh_url' => $fotoPenyuluhUrl,
                'is_active' => $isActive,
                'created_at' => $now,
                'updated_at' => $now,
            ]);
        });

        return back()->with('success', 'Penyuluh dan akun login berhasil ditambahkan.');
    }

    public function update(Request $request, int $id): RedirectResponse
    {
        $kecamatanIds = $this->managedKecamatanIds();

        $existing = DB::table('penyuluh')->where('id', $id)->first(['foto_penyuluh_url']);
        if (! $existing) {
            return back()->with('error', 'Data penyuluh tidak ditemukan di wilayah Anda.');
        }

        $data = $request->validate([
            'balai_id' => ['required', 'exists:balai_penyuluh,id'],
            'nip' => ['nullable', 'string', 'max:40', Rule::unique('penyuluh', 'nip')->ignore($id)],
            'jabatan' => ['nullable', 'string', 'max:120'],
            'lokasi_penugasan' => ['nullable', 'string', 'max:255'],
            'tugas_tambahan' => ['nullable', 'string', 'max:255'],
            'foto_penyuluh' => ['nullable', 'image', 'mimes:jpg,jpeg,png,webp', 'max:5120'],
            'is_active' => ['nullable', 'boolean'],
        ]);

        $target = DB::table('penyuluh')
            ->join('balai_penyuluh', 'balai_penyuluh.id', '=', 'penyuluh.balai_id')
            ->where('penyuluh.id', $id)
            ->whereIn('balai_penyuluh.kecamatan_id', $kecamatanIds)
            ->exists();

        if (! $target) {
            return back()->with('error', 'Data penyuluh tidak ditemukan di wilayah Anda.');
        }

        $fotoPenyuluhUrl = $existing->foto_penyuluh_url;
        if ($request->hasFile('foto_penyuluh')) {
            $newPath = $request->file('foto_penyuluh')->store('penyuluh/foto', 'public');
            $fotoPenyuluhUrl = Storage::url($newPath);

            if (is_string($existing->foto_penyuluh_url) && str_starts_with($existing->foto_penyuluh_url, '/storage/')) {
                $oldPath = str_replace('/storage/', '', $existing->foto_penyuluh_url);
                if ($oldPath !== '' && Storage::disk('public')->exists($oldPath)) {
                    Storage::disk('public')->delete($oldPath);
                }
            }
        }

        DB::table('penyuluh')->where('id', $id)->update([
            'balai_id' => $data['balai_id'],
            'nip' => $data['nip'] ?? null,
            'jabatan' => $data['jabatan'] ?? null,
            'lokasi_penugasan' => $data['lokasi_penugasan'] ?? null,
            'tugas_tambahan' => $data['tugas_tambahan'] ?? null,
            'foto_penyuluh_url' => $fotoPenyuluhUrl,
            'is_active' => (bool) ($data['is_active'] ?? false),
            'updated_at' => now(),
        ]);

        return back()->with('success', 'Penyuluh berhasil diperbarui.');
    }

    public function destroy(int $id): RedirectResponse
    {
        $kecamatanIds = $this->managedKecamatanIds();

        $target = DB::table('penyuluh')
            ->join('balai_penyuluh', 'balai_penyuluh.id', '=', 'penyuluh.balai_id')
            ->where('penyuluh.id', $id)
            ->whereIn('balai_penyuluh.kecamatan_id', $kecamatanIds)
            ->select('penyuluh.id', 'penyuluh.foto_penyuluh_url')
            ->first();

        if (! $target) {
            return back()->with('error', 'Data penyuluh tidak ditemukan di wilayah Anda.');
        }

        try {
            DB::table('penyuluh')->where('id', $id)->delete();

            if (is_string($target->foto_penyuluh_url) && str_starts_with($target->foto_penyuluh_url, '/storage/')) {
                $oldPath = str_replace('/storage/', '', $target->foto_penyuluh_url);
                if ($oldPath !== '' && Storage::disk('public')->exists($oldPath)) {
                    Storage::disk('public')->delete($oldPath);
                }
            }
        } catch (QueryException) {
            return back()->with('error', 'Penyuluh tidak dapat dihapus karena masih memiliki penugasan aktif.');
        }

        return back()->with('success', 'Penyuluh berhasil dihapus.');
    }

    public function storePenugasan(Request $request): RedirectResponse
    {
        $kecamatanIds = $this->managedKecamatanIds();

        $data = $request->validate([
            'penyuluh_id' => ['required', 'exists:penyuluh,id'],
            'lahan_id' => ['required', 'exists:lahan,id'],
            'tanggal_mulai' => ['required', 'date'],
            'tanggal_selesai' => ['nullable', 'date', 'after_or_equal:tanggal_mulai'],
            'status_penugasan' => ['required', 'in:aktif,selesai,dibatalkan'],
        ]);

        $penyuluhValid = DB::table('penyuluh')
            ->join('balai_penyuluh', 'balai_penyuluh.id', '=', 'penyuluh.balai_id')
            ->where('penyuluh.id', $data['penyuluh_id'])
            ->whereIn('balai_penyuluh.kecamatan_id', $kecamatanIds)
            ->exists();

        if (! $penyuluhValid) {
            return back()->with('error', 'Penyuluh tidak termasuk wilayah admin kecamatan Anda.');
        }

        $lahanValid = DB::table('lahan')
            ->join('desa', 'desa.id', '=', 'lahan.desa_id')
            ->where('lahan.id', $data['lahan_id'])
            ->whereIn('desa.kecamatan_id', $kecamatanIds)
            ->exists();

        if (! $lahanValid) {
            return back()->with('error', 'Lahan/petani tidak termasuk wilayah admin kecamatan Anda.');
        }

        if ($data['status_penugasan'] === 'aktif') {
            $exists = DB::table('penugasan_penyuluh')
                ->where('penyuluh_id', $data['penyuluh_id'])
                ->where('lahan_id', $data['lahan_id'])
                ->where('status_penugasan', 'aktif')
                ->exists();

            if ($exists) {
                return back()->with('error', 'Penugasan aktif untuk kombinasi penyuluh dan lahan ini sudah ada.');
            }
        }

        DB::table('penugasan_penyuluh')->insert([
            'penyuluh_id' => $data['penyuluh_id'],
            'lahan_id' => $data['lahan_id'],
            'dibuat_oleh_user_id' => auth()->id(),
            'tanggal_mulai' => $data['tanggal_mulai'],
            'tanggal_selesai' => $data['tanggal_selesai'] ?? null,
            'status_penugasan' => $data['status_penugasan'],
            'created_at' => now(),
            'updated_at' => now(),
        ]);

        return back()->with('success', 'Penugasan penyuluh ke petani berhasil ditambahkan.');
    }

    public function updatePenugasan(Request $request, int $id): RedirectResponse
    {
        $kecamatanIds = $this->managedKecamatanIds();

        $target = DB::table('penugasan_penyuluh')
            ->join('lahan', 'lahan.id', '=', 'penugasan_penyuluh.lahan_id')
            ->join('desa', 'desa.id', '=', 'lahan.desa_id')
            ->where('penugasan_penyuluh.id', $id)
            ->whereIn('desa.kecamatan_id', $kecamatanIds)
            ->select('penugasan_penyuluh.id')
            ->first();

        if (! $target) {
            return back()->with('error', 'Penugasan tidak ditemukan di wilayah Anda.');
        }

        $data = $request->validate([
            'penyuluh_id' => ['required', 'exists:penyuluh,id'],
            'lahan_id' => ['required', 'exists:lahan,id'],
            'tanggal_mulai' => ['required', 'date'],
            'tanggal_selesai' => ['nullable', 'date', 'after_or_equal:tanggal_mulai'],
            'status_penugasan' => ['required', 'in:aktif,selesai,dibatalkan'],
        ]);

        $penyuluhValid = DB::table('penyuluh')
            ->join('balai_penyuluh', 'balai_penyuluh.id', '=', 'penyuluh.balai_id')
            ->where('penyuluh.id', $data['penyuluh_id'])
            ->whereIn('balai_penyuluh.kecamatan_id', $kecamatanIds)
            ->exists();

        if (! $penyuluhValid) {
            return back()->with('error', 'Penyuluh tidak termasuk wilayah admin kecamatan Anda.');
        }

        $lahanValid = DB::table('lahan')
            ->join('desa', 'desa.id', '=', 'lahan.desa_id')
            ->where('lahan.id', $data['lahan_id'])
            ->whereIn('desa.kecamatan_id', $kecamatanIds)
            ->exists();

        if (! $lahanValid) {
            return back()->with('error', 'Lahan/petani tidak termasuk wilayah admin kecamatan Anda.');
        }

        if ($data['status_penugasan'] === 'aktif') {
            $exists = DB::table('penugasan_penyuluh')
                ->where('penyuluh_id', $data['penyuluh_id'])
                ->where('lahan_id', $data['lahan_id'])
                ->where('status_penugasan', 'aktif')
                ->where('id', '!=', $id)
                ->exists();

            if ($exists) {
                return back()->with('error', 'Penugasan aktif untuk kombinasi penyuluh dan lahan ini sudah ada.');
            }
        }

        DB::table('penugasan_penyuluh')->where('id', $id)->update([
            'penyuluh_id' => $data['penyuluh_id'],
            'lahan_id' => $data['lahan_id'],
            'tanggal_mulai' => $data['tanggal_mulai'],
            'tanggal_selesai' => $data['tanggal_selesai'] ?? null,
            'status_penugasan' => $data['status_penugasan'],
            'updated_at' => now(),
        ]);

        return back()->with('success', 'Penugasan berhasil diperbarui.');
    }

    public function destroyPenugasan(int $id): RedirectResponse
    {
        $kecamatanIds = $this->managedKecamatanIds();

        $target = DB::table('penugasan_penyuluh')
            ->join('lahan', 'lahan.id', '=', 'penugasan_penyuluh.lahan_id')
            ->join('desa', 'desa.id', '=', 'lahan.desa_id')
            ->where('penugasan_penyuluh.id', $id)
            ->whereIn('desa.kecamatan_id', $kecamatanIds)
            ->select('penugasan_penyuluh.id')
            ->first();

        if (! $target) {
            return back()->with('error', 'Penugasan tidak ditemukan di wilayah Anda.');
        }

        $alreadyUsed = DB::table('kunjungan_monitoring')
            ->where('penugasan_id', $id)
            ->exists();

        if ($alreadyUsed) {
            return back()->with('error', 'Penugasan tidak dapat dihapus karena sudah memiliki data kunjungan. Ubah status menjadi selesai/dibatalkan.');
        }

        DB::table('penugasan_penyuluh')->where('id', $id)->delete();

        return back()->with('success', 'Penugasan berhasil dihapus.');
    }
}
