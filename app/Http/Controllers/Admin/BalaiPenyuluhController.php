<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\User;
use App\Support\MapStyleSupport;
use Illuminate\Database\QueryException;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Storage;
use Illuminate\Validation\Rule;
use Illuminate\View\View;

class BalaiPenyuluhController extends Controller
{
    private function adminKecamatanByKecamatan(int $kecamatanId, ?int $exceptUserId = null): ?object
    {
        return DB::table('user_wilayah')
            ->join('users', 'users.id', '=', 'user_wilayah.user_id')
            ->where('user_wilayah.kecamatan_id', $kecamatanId)
            ->where('users.role', User::ROLE_ADMIN_KECAMATAN)
            ->when($exceptUserId, fn ($q) => $q->where('users.id', '!=', $exceptUserId))
            ->select('users.id', 'users.name', 'users.email')
            ->orderByDesc('user_wilayah.is_primary')
            ->orderBy('user_wilayah.id')
            ->first();
    }

    public function index(): View
    {
        $kecamatan = DB::table('kecamatan')->orderBy('nama_kecamatan')->get();

        $balai = DB::table('balai_penyuluh')
            ->join('kecamatan', 'kecamatan.id', '=', 'balai_penyuluh.kecamatan_id')
            ->select('balai_penyuluh.*', 'kecamatan.nama_kecamatan')
            ->orderBy('kecamatan.nama_kecamatan')
            ->orderBy('balai_penyuluh.nama_balai')
            ->get()
            ->map(function ($row) {
                $admin = $this->adminKecamatanByKecamatan((int) $row->kecamatan_id);
                $row->admin_kecamatan_name = $admin?->name;
                $row->admin_kecamatan_email = $admin?->email;

                return $row;
            });

        $penyuluh = DB::table('penyuluh')
            ->join('users', 'users.id', '=', 'penyuluh.user_id')
            ->join('balai_penyuluh', 'balai_penyuluh.id', '=', 'penyuluh.balai_id')
            ->select('penyuluh.*', 'users.name as nama_user', 'users.email', 'balai_penyuluh.nama_balai')
            ->orderBy('users.name')
            ->get();

        MapStyleSupport::ensureDefaultRows();
        $mapStyles = MapStyleSupport::styleMap();

        $balaiMarkers = DB::table('balai_penyuluh')
            ->join('kecamatan', 'kecamatan.id', '=', 'balai_penyuluh.kecamatan_id')
            ->whereNotNull('balai_penyuluh.latitude')
            ->whereNotNull('balai_penyuluh.longitude')
            ->select('balai_penyuluh.nama_balai', 'balai_penyuluh.alamat_balai', 'balai_penyuluh.latitude', 'balai_penyuluh.longitude', 'balai_penyuluh.foto_balai_url', 'kecamatan.nama_kecamatan')
            ->get()
            ->map(fn ($item) => [
                'lat' => (float) $item->latitude,
                'lng' => (float) $item->longitude,
                'title' => $item->nama_balai,
                'description' => trim(($item->alamat_balai ? $item->alamat_balai.' | ' : '').'Kec. '.$item->nama_kecamatan),
                'style_code' => 'entity:balai',
                'style' => MapStyleSupport::iconForStyleCode('entity:balai', $mapStyles),
                'image_url' => $item->foto_balai_url,
                'fields' => [
                    ['label' => 'Balai', 'value' => $item->nama_balai],
                    ['label' => 'Kecamatan', 'value' => $item->nama_kecamatan],
                    ['label' => 'Alamat', 'value' => $item->alamat_balai],
                ],
            ])
            ->toArray();

        $kecamatanMarkers = DB::table('kecamatan')
            ->whereNotNull('centroid_lat')
            ->whereNotNull('centroid_lng')
            ->select('nama_kecamatan', 'centroid_lat as latitude', 'centroid_lng as longitude')
            ->get()
            ->map(fn ($item) => [
                'lat' => (float) $item->latitude,
                'lng' => (float) $item->longitude,
                'title' => 'Kecamatan '.$item->nama_kecamatan,
                'description' => 'Pusat wilayah',
                'style_code' => 'entity:kecamatan',
                'style' => MapStyleSupport::iconForStyleCode('entity:kecamatan', $mapStyles),
                'fields' => [
                    ['label' => 'Kecamatan', 'value' => $item->nama_kecamatan],
                ],
            ])
            ->toArray();

        $mapMarkers = array_merge($kecamatanMarkers, $balaiMarkers);

        return view('admin.balai.index', compact('kecamatan', 'balai', 'penyuluh', 'mapStyles', 'mapMarkers'));
    }

    public function storeBalai(Request $request): RedirectResponse
    {
        $data = $request->validate([
            'kecamatan_id' => ['required', 'exists:kecamatan,id'],
            'nama_balai' => ['required', 'string', 'max:180'],
            'alamat_balai' => ['nullable', 'string'],
            'latitude' => ['nullable', 'numeric'],
            'longitude' => ['nullable', 'numeric'],
            'foto_balai' => ['nullable', 'image', 'mimes:jpg,jpeg,png,webp', 'max:5120'],
            'admin_kecamatan_name' => ['required', 'string', 'max:100'],
            'admin_kecamatan_email' => ['required', 'email', 'max:150', 'unique:users,email'],
            'admin_kecamatan_password' => ['required', 'string', 'min:8', 'confirmed'],
            'is_active' => ['nullable', 'boolean'],
        ]);

        $exists = DB::table('balai_penyuluh')
            ->where('kecamatan_id', $data['kecamatan_id'])
            ->exists();

        if ($exists) {
            return back()->with('error', 'Setiap kecamatan hanya boleh memiliki 1 balai penyuluh.');
        }

        $existingAdmin = $this->adminKecamatanByKecamatan((int) $data['kecamatan_id']);
        if ($existingAdmin) {
            return back()->with('error', 'Kecamatan ini sudah memiliki akun admin kecamatan.');
        }

        $roleIdAdminKecamatan = DB::table('roles')->where('kode_role', User::ROLE_ADMIN_KECAMATAN)->value('id');
        if (! $roleIdAdminKecamatan) {
            return back()->with('error', 'Role admin kecamatan belum tersedia.');
        }

        $fotoBalaiUrl = null;
        if ($request->hasFile('foto_balai')) {
            $path = $request->file('foto_balai')->store('balai/foto', 'public');
            $fotoBalaiUrl = Storage::url($path);
        }

        DB::transaction(function () use ($data, $roleIdAdminKecamatan, $fotoBalaiUrl): void {
            $now = now();
            $isActive = (bool) ($data['is_active'] ?? false);

            $adminUserId = DB::table('users')->insertGetId([
                'name' => $data['admin_kecamatan_name'],
                'email' => $data['admin_kecamatan_email'],
                'password' => Hash::make($data['admin_kecamatan_password']),
                'role' => User::ROLE_ADMIN_KECAMATAN,
                'role_id' => $roleIdAdminKecamatan,
                'is_active' => $isActive,
                'created_at' => $now,
                'updated_at' => $now,
            ]);

            DB::table('user_wilayah')->insert([
                'user_id' => $adminUserId,
                'kecamatan_id' => $data['kecamatan_id'],
                'is_primary' => true,
                'created_at' => $now,
                'updated_at' => $now,
            ]);

            DB::table('balai_penyuluh')->insert([
                'kecamatan_id' => $data['kecamatan_id'],
                'nama_balai' => $data['nama_balai'],
                'alamat_balai' => $data['alamat_balai'] ?? null,
                'latitude' => $data['latitude'] ?? null,
                'longitude' => $data['longitude'] ?? null,
                'foto_balai_url' => $fotoBalaiUrl,
                'is_active' => $isActive,
                'created_at' => $now,
                'updated_at' => $now,
            ]);
        });

        return back()->with('success', 'Balai penyuluh dan akun admin kecamatan berhasil ditambahkan.');
    }

    public function updateBalai(Request $request, int $id): RedirectResponse
    {
        $existing = DB::table('balai_penyuluh')->where('id', $id)->first(['kecamatan_id', 'foto_balai_url']);
        if (! $existing) {
            return back()->with('error', 'Data balai tidak ditemukan.');
        }

        $existingAdmin = $this->adminKecamatanByKecamatan((int) $existing->kecamatan_id);

        $data = $request->validate([
            'kecamatan_id' => ['required', 'exists:kecamatan,id'],
            'nama_balai' => ['required', 'string', 'max:180'],
            'alamat_balai' => ['nullable', 'string'],
            'latitude' => ['nullable', 'numeric'],
            'longitude' => ['nullable', 'numeric'],
            'foto_balai' => ['nullable', 'image', 'mimes:jpg,jpeg,png,webp', 'max:5120'],
            'admin_kecamatan_name' => ['nullable', 'string', 'max:100'],
            'admin_kecamatan_email' => ['nullable', 'email', 'max:150', Rule::unique('users', 'email')->ignore($existingAdmin?->id)],
            'admin_kecamatan_password' => ['nullable', 'string', 'min:8', 'confirmed'],
            'is_active' => ['nullable', 'boolean'],
        ]);

        $exists = DB::table('balai_penyuluh')
            ->where('kecamatan_id', $data['kecamatan_id'])
            ->where('id', '!=', $id)
            ->exists();

        if ($exists) {
            return back()->with('error', 'Setiap kecamatan hanya boleh memiliki 1 balai penyuluh.');
        }

        $targetKecamatanId = (int) $data['kecamatan_id'];
        $adminInTargetKecamatan = $this->adminKecamatanByKecamatan($targetKecamatanId, $existingAdmin?->id);
        if ($adminInTargetKecamatan) {
            return back()->with('error', 'Kecamatan tujuan sudah memiliki akun admin kecamatan lain.');
        }

        $fotoBalaiUrl = $existing->foto_balai_url;
        if ($request->hasFile('foto_balai')) {
            $newPath = $request->file('foto_balai')->store('balai/foto', 'public');
            $fotoBalaiUrl = Storage::url($newPath);

            if (is_string($existing->foto_balai_url) && str_starts_with($existing->foto_balai_url, '/storage/')) {
                $oldPath = str_replace('/storage/', '', $existing->foto_balai_url);
                if ($oldPath !== '' && Storage::disk('public')->exists($oldPath)) {
                    Storage::disk('public')->delete($oldPath);
                }
            }
        }

        if (! $existingAdmin && (empty($data['admin_kecamatan_name']) || empty($data['admin_kecamatan_email']) || empty($data['admin_kecamatan_password']))) {
            return back()->with('error', 'Balai ini belum memiliki akun admin kecamatan. Lengkapi nama, email, dan password admin kecamatan.');
        }

        $roleIdAdminKecamatan = DB::table('roles')->where('kode_role', User::ROLE_ADMIN_KECAMATAN)->value('id');
        if (! $existingAdmin && ! $roleIdAdminKecamatan) {
            return back()->with('error', 'Role admin kecamatan belum tersedia.');
        }

        DB::transaction(function () use ($data, $id, $existing, $existingAdmin, $fotoBalaiUrl, $targetKecamatanId, $roleIdAdminKecamatan): void {
            $isActive = (bool) ($data['is_active'] ?? false);

            DB::table('balai_penyuluh')->where('id', $id)->update([
                'kecamatan_id' => $data['kecamatan_id'],
                'nama_balai' => $data['nama_balai'],
                'alamat_balai' => $data['alamat_balai'] ?? null,
                'latitude' => $data['latitude'] ?? null,
                'longitude' => $data['longitude'] ?? null,
                'foto_balai_url' => $fotoBalaiUrl,
                'is_active' => $isActive,
                'updated_at' => now(),
            ]);

            if ($existingAdmin) {
                $userPayload = [
                    'is_active' => $isActive,
                    'updated_at' => now(),
                ];

                if (! empty($data['admin_kecamatan_name'])) {
                    $userPayload['name'] = $data['admin_kecamatan_name'];
                }
                if (! empty($data['admin_kecamatan_email'])) {
                    $userPayload['email'] = $data['admin_kecamatan_email'];
                }
                if (! empty($data['admin_kecamatan_password'])) {
                    $userPayload['password'] = Hash::make($data['admin_kecamatan_password']);
                }

                DB::table('users')->where('id', $existingAdmin->id)->update($userPayload);

                DB::table('user_wilayah')
                    ->where('user_id', $existingAdmin->id)
                    ->update([
                        'is_primary' => false,
                        'updated_at' => now(),
                    ]);

                DB::table('user_wilayah')->updateOrInsert(
                    ['user_id' => $existingAdmin->id, 'kecamatan_id' => $targetKecamatanId],
                    ['is_primary' => true, 'updated_at' => now(), 'created_at' => now()]
                );
            } else {
                $newAdminId = DB::table('users')->insertGetId([
                    'name' => $data['admin_kecamatan_name'],
                    'email' => $data['admin_kecamatan_email'],
                    'password' => Hash::make($data['admin_kecamatan_password']),
                    'role' => User::ROLE_ADMIN_KECAMATAN,
                    'role_id' => $roleIdAdminKecamatan,
                    'is_active' => $isActive,
                    'created_at' => now(),
                    'updated_at' => now(),
                ]);

                DB::table('user_wilayah')->insert([
                    'user_id' => $newAdminId,
                    'kecamatan_id' => $targetKecamatanId,
                    'is_primary' => true,
                    'created_at' => now(),
                    'updated_at' => now(),
                ]);
            }
        });

        return back()->with('success', 'Balai penyuluh berhasil diperbarui.');
    }

    public function destroyBalai(int $id): RedirectResponse
    {
        $targetBalai = DB::table('balai_penyuluh')->where('id', $id)->first(['id', 'kecamatan_id']);
        if (! $targetBalai) {
            return back()->with('error', 'Data balai tidak ditemukan.');
        }

        $adminKecamatan = $this->adminKecamatanByKecamatan((int) $targetBalai->kecamatan_id);

        try {
            DB::transaction(function () use ($targetBalai, $adminKecamatan): void {
                DB::table('balai_penyuluh')->where('id', $targetBalai->id)->delete();

                if ($adminKecamatan) {
                    DB::table('user_wilayah')
                        ->where('user_id', $adminKecamatan->id)
                        ->where('kecamatan_id', $targetBalai->kecamatan_id)
                        ->delete();

                    DB::table('users')
                        ->where('id', $adminKecamatan->id)
                        ->update([
                            'is_active' => false,
                            'updated_at' => now(),
                        ]);
                }
            });
        } catch (QueryException) {
            return back()->with('error', 'Balai tidak dapat dihapus karena masih dipakai data penyuluh/media.');
        }

        return back()->with('success', 'Balai penyuluh berhasil dihapus. Akun admin kecamatan terkait dinonaktifkan.');
    }

    public function storePenyuluh(Request $request): RedirectResponse
    {
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

        return back()->with('success', 'Data penyuluh dan akun login berhasil ditambahkan.');
    }

    public function updatePenyuluh(Request $request, int $id): RedirectResponse
    {
        $existing = DB::table('penyuluh')->where('id', $id)->first(['foto_penyuluh_url']);
        if (! $existing) {
            return back()->with('error', 'Data penyuluh tidak ditemukan.');
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

        return back()->with('success', 'Data penyuluh berhasil diperbarui.');
    }

    public function destroyPenyuluh(int $id): RedirectResponse
    {
        $existing = DB::table('penyuluh')->where('id', $id)->first(['foto_penyuluh_url']);
        if (! $existing) {
            return back()->with('error', 'Data penyuluh tidak ditemukan.');
        }

        try {
            DB::table('penyuluh')->where('id', $id)->delete();

            if (is_string($existing->foto_penyuluh_url) && str_starts_with($existing->foto_penyuluh_url, '/storage/')) {
                $oldPath = str_replace('/storage/', '', $existing->foto_penyuluh_url);
                if ($oldPath !== '' && Storage::disk('public')->exists($oldPath)) {
                    Storage::disk('public')->delete($oldPath);
                }
            }
        } catch (QueryException) {
            return back()->with('error', 'Data penyuluh tidak dapat dihapus karena masih dipakai penugasan.');
        }

        return back()->with('success', 'Data penyuluh berhasil dihapus.');
    }
}
