<?php

namespace App\Http\Controllers;

use App\Models\User;
use App\Support\MapStyleSupport;
use Illuminate\Database\Query\Builder;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\HtmlString;

class PanelDataTableController extends Controller
{
    public function index(Request $request, string $source): JsonResponse
    {
        if (! $this->isSourceAuthorized($source)) {
            return response()->json(['message' => 'Akses ditolak.'], 403);
        }

        return match ($source) {
            'admin_users' => $this->adminUsers($request),
            'admin_kecamatan' => $this->adminKecamatan($request),
            'admin_desa' => $this->adminDesa($request),
            'admin_komoditas' => $this->adminKomoditas($request),
            'admin_kelompok_tani' => $this->adminKelompokTani($request),
            'admin_balai' => $this->adminBalai($request),
            'admin_penyuluh' => $this->adminPenyuluh($request),
            'kecamatan_kelompok_tani' => $this->kecamatanKelompokTani($request),
            'kecamatan_penyuluh' => $this->kecamatanPenyuluh($request),
            'kecamatan_penugasan' => $this->kecamatanPenugasan($request),
            'kecamatan_petani' => $this->kecamatanPetani($request),
            'kecamatan_lahan' => $this->kecamatanLahan($request),
            'kecamatan_lahan_komoditas' => $this->kecamatanLahanKomoditas($request),
            'kecamatan_verifikasi' => $this->kecamatanVerifikasi($request),
            'kecamatan_usulan_perubahan' => $this->kecamatanUsulanPerubahan($request),
            'kecamatan_rekap_summary' => $this->kecamatanRekapSummary($request),
            'kecamatan_rekap_komoditas' => $this->kecamatanRekapKomoditas($request),
            'admin_monitoring' => $this->adminMonitoring($request),
            'admin_laporan_periode' => $this->adminLaporanPeriode($request),
            'admin_laporan_list' => $this->adminLaporanList($request),
            'admin_laporan_detail' => $this->adminLaporanDetail($request),
            'admin_map_style_komoditas' => $this->adminMapStyleKomoditas($request),
            'penyuluh_kunjungan' => $this->penyuluhKunjungan($request),
            'penyuluh_produksi' => $this->penyuluhProduksi($request),
            'penyuluh_kendala' => $this->penyuluhKendala($request),
            'penyuluh_kebutuhan' => $this->penyuluhKebutuhan($request),
            'penyuluh_petani_binaan' => $this->penyuluhPetaniBinaan($request),
            'penyuluh_riwayat' => $this->penyuluhRiwayat($request),
            'pimpinan_laporan_list' => $this->pimpinanLaporanList($request),
            'pimpinan_laporan_detail' => $this->pimpinanLaporanDetail($request),
            'pimpinan_analitik_komoditas' => $this->pimpinanAnalitikKomoditas($request),
            'pimpinan_analitik_periode' => $this->pimpinanAnalitikPeriode($request),
            'pimpinan_unduh_laporan' => $this->pimpinanUnduhLaporan($request),
            'pimpinan_peta_kecamatan' => $this->pimpinanPetaKecamatan($request),
            'pimpinan_peta_balai' => $this->pimpinanPetaBalai($request),
            default => response()->json(['message' => 'Sumber data tidak valid.'], 404),
        };
    }

    private function isSourceAuthorized(string $source): bool
    {
        $role = auth()->user()?->role;

        if (str_starts_with($source, 'admin_')) {
            return $role === User::ROLE_ADMIN_DINAS;
        }

        if (str_starts_with($source, 'kecamatan_')) {
            return $role === User::ROLE_ADMIN_KECAMATAN;
        }

        if (str_starts_with($source, 'penyuluh_')) {
            return $role === User::ROLE_PENYULUH;
        }

        if (str_starts_with($source, 'pimpinan_')) {
            return $role === User::ROLE_PIMPINAN_DINAS;
        }

        return false;
    }

    private function adminUsers(Request $request): JsonResponse
    {
        $query = DB::table('users')
            ->select('id', 'name', 'email', 'role', 'is_active', 'created_at');

        return $this->respond(
            $request,
            $query,
            ['name', 'email', 'role', 'is_active', 'created_at'],
            ['name', 'email', 'role'],
            'name',
            function ($row): array {
                return [
                    'name' => e($row->name),
                    'email' => e($row->email),
                    'role' => e(ucwords(str_replace('_', ' ', $row->role))),
                    'is_active' => (bool) $row->is_active ? 'Aktif' : 'Nonaktif',
                    'created_at' => (string) $row->created_at,
                    'actions' => $this->actionsHtml($this->payload([
                        'title' => 'Detail User',
                        'fields' => [
                            ['label' => 'Nama', 'value' => $row->name],
                            ['label' => 'Email', 'value' => $row->email],
                            ['label' => 'Role', 'value' => ucwords(str_replace('_', ' ', $row->role))],
                            ['label' => 'Status', 'value' => (bool) $row->is_active ? 'Aktif' : 'Nonaktif'],
                            ['label' => 'Dibuat', 'value' => (string) $row->created_at],
                        ],
                        'edit' => [
                            'url' => route('admin.users.update', $row->id),
                            'method' => 'PUT',
                            'fields' => [
                                ['name' => 'name', 'label' => 'Nama', 'type' => 'text', 'value' => $row->name, 'required' => true],
                                ['name' => 'email', 'label' => 'Email', 'type' => 'email', 'value' => $row->email, 'required' => true],
                                [
                                    'name' => 'role',
                                    'label' => 'Role',
                                    'type' => 'select',
                                    'value' => $row->role,
                                    'required' => true,
                                    'options' => collect(User::AVAILABLE_ROLES)->map(fn ($role): array => [
                                        'value' => $role,
                                        'label' => ucwords(str_replace('_', ' ', $role)),
                                    ])->toArray(),
                                ],
                                [
                                    'name' => 'is_active',
                                    'label' => 'Status',
                                    'type' => 'select',
                                    'value' => (bool) $row->is_active ? '1' : '0',
                                    'required' => true,
                                    'options' => [
                                        ['value' => '1', 'label' => 'Aktif'],
                                        ['value' => '0', 'label' => 'Nonaktif'],
                                    ],
                                ],
                                ['name' => 'password', 'label' => 'Password Baru (opsional)', 'type' => 'password', 'value' => '', 'required' => false],
                                ['name' => 'password_confirmation', 'label' => 'Konfirmasi Password', 'type' => 'password', 'value' => '', 'required' => false],
                            ],
                        ],
                        'delete' => [
                            'url' => route('admin.users.destroy', $row->id),
                            'method' => 'DELETE',
                            'label' => 'Hapus user '.$row->name.'?',
                        ],
                    ])),
                ];
            }
        );
    }

    private function adminKecamatan(Request $request): JsonResponse
    {
        $query = DB::table('kecamatan')
            ->select('id', 'kode_kecamatan', 'nama_kecamatan', 'alamat', 'centroid_lat', 'centroid_lng', 'is_active');

        return $this->respond(
            $request,
            $query,
            ['kode_kecamatan', 'nama_kecamatan', 'centroid_lat', 'centroid_lng', 'is_active'],
            ['kode_kecamatan', 'nama_kecamatan'],
            'nama_kecamatan',
            function ($row): array {
                return [
                    'kode_kecamatan' => e($row->kode_kecamatan),
                    'nama_kecamatan' => e($row->nama_kecamatan),
                    'centroid_lat' => $row->centroid_lat,
                    'centroid_lng' => $row->centroid_lng,
                    'is_active' => (bool) $row->is_active ? 'Aktif' : 'Nonaktif',
                    'actions' => $this->actionsHtml($this->payload([
                        'title' => 'Detail Kecamatan',
                        'fields' => [
                            ['label' => 'Kode', 'value' => $row->kode_kecamatan],
                            ['label' => 'Nama Kecamatan', 'value' => $row->nama_kecamatan],
                            ['label' => 'Alamat', 'value' => $row->alamat],
                            ['label' => 'Latitude', 'value' => $row->centroid_lat],
                            ['label' => 'Longitude', 'value' => $row->centroid_lng],
                            ['label' => 'Status', 'value' => (bool) $row->is_active ? 'Aktif' : 'Nonaktif'],
                        ],
                        'spatial' => [
                            'lat' => $row->centroid_lat,
                            'lng' => $row->centroid_lng,
                            'label' => $row->nama_kecamatan,
                            'style' => MapStyleSupport::entityDefaults()['entity:kecamatan'],
                        ],
                        'edit' => [
                            'url' => route('admin.wilayah.kecamatan.update', $row->id),
                            'method' => 'PUT',
                            'fields' => [
                                ['name' => 'kode_kecamatan', 'label' => 'Kode Kecamatan', 'type' => 'text', 'value' => $row->kode_kecamatan, 'required' => false, 'readonly' => true],
                                ['name' => 'nama_kecamatan', 'label' => 'Nama Kecamatan', 'type' => 'text', 'value' => $row->nama_kecamatan, 'required' => true],
                                ['name' => 'alamat', 'label' => 'Alamat', 'type' => 'textarea', 'value' => $row->alamat, 'required' => false],
                                ['name' => 'centroid_lat', 'label' => 'Latitude', 'type' => 'number', 'value' => $row->centroid_lat, 'required' => false, 'step' => '0.0000001'],
                                ['name' => 'centroid_lng', 'label' => 'Longitude', 'type' => 'number', 'value' => $row->centroid_lng, 'required' => false, 'step' => '0.0000001'],
                                [
                                    'name' => 'is_active',
                                    'label' => 'Status',
                                    'type' => 'select',
                                    'value' => (bool) $row->is_active ? '1' : '0',
                                    'required' => true,
                                    'options' => [
                                        ['value' => '1', 'label' => 'Aktif'],
                                        ['value' => '0', 'label' => 'Nonaktif'],
                                    ],
                                ],
                            ],
                            'spatial' => ['lat_field' => 'centroid_lat', 'lng_field' => 'centroid_lng', 'address_field' => 'alamat'],
                        ],
                        'delete' => [
                            'url' => route('admin.wilayah.kecamatan.destroy', $row->id),
                            'method' => 'DELETE',
                            'label' => 'Hapus kecamatan '.$row->nama_kecamatan.'?',
                        ],
                    ])),
                ];
            }
        );
    }

    private function adminDesa(Request $request): JsonResponse
    {
        $query = DB::table('desa')
            ->join('kecamatan', 'kecamatan.id', '=', 'desa.kecamatan_id')
            ->select(
                'desa.id',
                'desa.kecamatan_id',
                'desa.kode_desa',
                'desa.nama_desa',
                'desa.alamat',
                'desa.centroid_lat',
                'desa.centroid_lng',
                'desa.is_active',
                'kecamatan.nama_kecamatan'
            );

        $kecamatanOptions = DB::table('kecamatan')->orderBy('nama_kecamatan')->get(['id', 'nama_kecamatan'])
            ->map(fn ($x): array => ['value' => (string) $x->id, 'label' => $x->nama_kecamatan])
            ->toArray();

        return $this->respond(
            $request,
            $query,
            ['kecamatan.nama_kecamatan', 'desa.kode_desa', 'desa.nama_desa', 'desa.centroid_lat', 'desa.centroid_lng', 'desa.is_active'],
            ['kecamatan.nama_kecamatan', 'desa.kode_desa', 'desa.nama_desa'],
            'desa.nama_desa',
            function ($row) use ($kecamatanOptions): array {
                return [
                    'nama_kecamatan' => e($row->nama_kecamatan),
                    'kode_desa' => e((string) $row->kode_desa),
                    'nama_desa' => e($row->nama_desa),
                    'centroid_lat' => $row->centroid_lat,
                    'centroid_lng' => $row->centroid_lng,
                    'is_active' => (bool) $row->is_active ? 'Aktif' : 'Nonaktif',
                    'actions' => $this->actionsHtml($this->payload([
                        'title' => 'Detail Desa',
                        'fields' => [
                            ['label' => 'Kecamatan', 'value' => $row->nama_kecamatan],
                            ['label' => 'Kode Desa', 'value' => $row->kode_desa],
                            ['label' => 'Nama Desa', 'value' => $row->nama_desa],
                            ['label' => 'Alamat', 'value' => $row->alamat],
                            ['label' => 'Latitude', 'value' => $row->centroid_lat],
                            ['label' => 'Longitude', 'value' => $row->centroid_lng],
                            ['label' => 'Status', 'value' => (bool) $row->is_active ? 'Aktif' : 'Nonaktif'],
                        ],
                        'spatial' => [
                            'lat' => $row->centroid_lat,
                            'lng' => $row->centroid_lng,
                            'label' => $row->nama_desa,
                            'style' => MapStyleSupport::entityDefaults()['entity:desa'],
                        ],
                        'edit' => [
                            'url' => route('admin.wilayah.desa.update', $row->id),
                            'method' => 'PUT',
                            'fields' => [
                                ['name' => 'kecamatan_id', 'label' => 'Kecamatan', 'type' => 'select', 'value' => (string) $row->kecamatan_id, 'required' => true, 'options' => $kecamatanOptions],
                                ['name' => 'kode_desa', 'label' => 'Kode Desa', 'type' => 'text', 'value' => $row->kode_desa, 'required' => false, 'readonly' => true],
                                ['name' => 'nama_desa', 'label' => 'Nama Desa', 'type' => 'text', 'value' => $row->nama_desa, 'required' => true],
                                ['name' => 'alamat', 'label' => 'Alamat', 'type' => 'textarea', 'value' => $row->alamat, 'required' => false],
                                ['name' => 'centroid_lat', 'label' => 'Latitude', 'type' => 'number', 'value' => $row->centroid_lat, 'required' => false, 'step' => '0.0000001'],
                                ['name' => 'centroid_lng', 'label' => 'Longitude', 'type' => 'number', 'value' => $row->centroid_lng, 'required' => false, 'step' => '0.0000001'],
                                [
                                    'name' => 'is_active',
                                    'label' => 'Status',
                                    'type' => 'select',
                                    'value' => (bool) $row->is_active ? '1' : '0',
                                    'required' => true,
                                    'options' => [
                                        ['value' => '1', 'label' => 'Aktif'],
                                        ['value' => '0', 'label' => 'Nonaktif'],
                                    ],
                                ],
                            ],
                            'spatial' => ['lat_field' => 'centroid_lat', 'lng_field' => 'centroid_lng', 'address_field' => 'alamat'],
                        ],
                        'delete' => [
                            'url' => route('admin.wilayah.desa.destroy', $row->id),
                            'method' => 'DELETE',
                            'label' => 'Hapus desa '.$row->nama_desa.'?',
                        ],
                    ])),
                ];
            }
        );
    }

    private function adminKomoditas(Request $request): JsonResponse
    {
        $query = DB::table('komoditas')
            ->select('id', 'kode_komoditas', 'nama_komoditas', 'satuan_default', 'is_active');

        return $this->respond(
            $request,
            $query,
            ['kode_komoditas', 'nama_komoditas', 'satuan_default', 'is_active'],
            ['kode_komoditas', 'nama_komoditas', 'satuan_default'],
            'nama_komoditas',
            function ($row): array {
                return [
                    'kode_komoditas' => e($row->kode_komoditas),
                    'nama_komoditas' => e($row->nama_komoditas),
                    'satuan_default' => e($row->satuan_default),
                    'is_active' => (bool) $row->is_active ? 'Aktif' : 'Nonaktif',
                    'actions' => $this->actionsHtml($this->payload([
                        'title' => 'Detail Komoditas',
                        'fields' => [
                            ['label' => 'Kode', 'value' => $row->kode_komoditas],
                            ['label' => 'Nama', 'value' => $row->nama_komoditas],
                            ['label' => 'Satuan Default', 'value' => $row->satuan_default],
                            ['label' => 'Status', 'value' => (bool) $row->is_active ? 'Aktif' : 'Nonaktif'],
                        ],
                        'edit' => [
                            'url' => route('admin.komoditas.update', $row->id),
                            'method' => 'PUT',
                            'fields' => [
                                ['name' => 'nama_komoditas', 'label' => 'Nama Komoditas', 'type' => 'text', 'value' => $row->nama_komoditas, 'required' => true],
                                ['name' => 'satuan_default', 'label' => 'Satuan', 'type' => 'text', 'value' => $row->satuan_default, 'required' => true],
                                [
                                    'name' => 'is_active',
                                    'label' => 'Status',
                                    'type' => 'select',
                                    'value' => (bool) $row->is_active ? '1' : '0',
                                    'required' => true,
                                    'options' => [
                                        ['value' => '1', 'label' => 'Aktif'],
                                        ['value' => '0', 'label' => 'Nonaktif'],
                                    ],
                                ],
                            ],
                        ],
                        'delete' => [
                            'url' => route('admin.komoditas.destroy', $row->id),
                            'method' => 'DELETE',
                            'label' => 'Hapus komoditas '.$row->nama_komoditas.'?',
                        ],
                    ])),
                ];
            }
        );
    }

    private function adminKelompokTani(Request $request): JsonResponse
    {
        return $this->kelompokTaniDataTable(
            request: $request,
            updateRoute: fn (int $id): string => route('admin.kelompok_tani.update', $id),
            deleteRoute: fn (int $id): string => route('admin.kelompok_tani.destroy', $id),
            dialogTitle: 'Detail Master Kelompok Tani'
        );
    }

    private function kecamatanKelompokTani(Request $request): JsonResponse
    {
        return $this->kelompokTaniDataTable(
            request: $request,
            updateRoute: fn (int $id): string => route('kecamatan.kelompok_tani.update', $id),
            deleteRoute: fn (int $id): string => route('kecamatan.kelompok_tani.destroy', $id),
            dialogTitle: 'Detail Master Kelompok Tani Kecamatan'
        );
    }

    private function kelompokTaniDataTable(
        Request $request,
        callable $updateRoute,
        callable $deleteRoute,
        string $dialogTitle
    ): JsonResponse {
        $query = DB::table('master_kelompok_tani')
            ->select('id', 'kode_kelompok', 'nama_kelompok', 'deskripsi', 'is_active');

        return $this->respond(
            $request,
            $query,
            ['kode_kelompok', 'nama_kelompok', 'deskripsi', 'is_active', 'actions'],
            ['kode_kelompok', 'nama_kelompok', 'deskripsi'],
            'nama_kelompok',
            function ($row) use ($updateRoute, $deleteRoute, $dialogTitle): array {
                return [
                    'kode_kelompok' => e($row->kode_kelompok),
                    'nama_kelompok' => e($row->nama_kelompok),
                    'deskripsi' => e((string) $row->deskripsi),
                    'status' => (bool) $row->is_active ? 'Aktif' : 'Nonaktif',
                    'actions' => $this->actionsHtml($this->payload([
                        'title' => $dialogTitle,
                        'fields' => [
                            ['label' => 'Kode', 'value' => $row->kode_kelompok],
                            ['label' => 'Nama Kelompok', 'value' => $row->nama_kelompok],
                            ['label' => 'Deskripsi', 'value' => $row->deskripsi],
                            ['label' => 'Status', 'value' => (bool) $row->is_active ? 'Aktif' : 'Nonaktif'],
                        ],
                        'edit' => [
                            'url' => $updateRoute((int) $row->id),
                            'method' => 'PUT',
                            'fields' => [
                                ['name' => 'nama_kelompok', 'label' => 'Nama Kelompok', 'type' => 'text', 'value' => $row->nama_kelompok, 'required' => true],
                                ['name' => 'deskripsi', 'label' => 'Deskripsi', 'type' => 'textarea', 'value' => $row->deskripsi, 'required' => false],
                                [
                                    'name' => 'is_active',
                                    'label' => 'Status',
                                    'type' => 'select',
                                    'value' => (bool) $row->is_active ? '1' : '0',
                                    'required' => true,
                                    'options' => [
                                        ['value' => '1', 'label' => 'Aktif'],
                                        ['value' => '0', 'label' => 'Nonaktif'],
                                    ],
                                ],
                            ],
                        ],
                        'delete' => [
                            'url' => $deleteRoute((int) $row->id),
                            'method' => 'DELETE',
                            'label' => 'Hapus master kelompok tani '.$row->nama_kelompok.'?',
                        ],
                    ])),
                ];
            }
        );
    }

    private function adminBalai(Request $request): JsonResponse
    {
        $query = DB::table('balai_penyuluh')
            ->join('kecamatan', 'kecamatan.id', '=', 'balai_penyuluh.kecamatan_id')
            ->select(
                'balai_penyuluh.id',
                'balai_penyuluh.kecamatan_id',
                'balai_penyuluh.nama_balai',
                'balai_penyuluh.alamat_balai',
                'balai_penyuluh.latitude',
                'balai_penyuluh.longitude',
                'balai_penyuluh.foto_balai_url',
                'balai_penyuluh.is_active',
                'kecamatan.nama_kecamatan'
            )
            ->selectSub(function ($sub) {
                $sub->from('user_wilayah')
                    ->join('users as u_admin', 'u_admin.id', '=', 'user_wilayah.user_id')
                    ->whereColumn('user_wilayah.kecamatan_id', 'balai_penyuluh.kecamatan_id')
                    ->where('u_admin.role', User::ROLE_ADMIN_KECAMATAN)
                    ->orderByDesc('user_wilayah.is_primary')
                    ->orderBy('user_wilayah.id')
                    ->select('u_admin.name')
                    ->limit(1);
            }, 'admin_kecamatan_name')
            ->selectSub(function ($sub) {
                $sub->from('user_wilayah')
                    ->join('users as u_admin', 'u_admin.id', '=', 'user_wilayah.user_id')
                    ->whereColumn('user_wilayah.kecamatan_id', 'balai_penyuluh.kecamatan_id')
                    ->where('u_admin.role', User::ROLE_ADMIN_KECAMATAN)
                    ->orderByDesc('user_wilayah.is_primary')
                    ->orderBy('user_wilayah.id')
                    ->select('u_admin.email')
                    ->limit(1);
            }, 'admin_kecamatan_email'
            );

        $kecamatanOptions = DB::table('kecamatan')->orderBy('nama_kecamatan')->get(['id', 'nama_kecamatan'])
            ->map(fn ($x): array => ['value' => (string) $x->id, 'label' => $x->nama_kecamatan])
            ->toArray();

        return $this->respond(
            $request,
            $query,
            ['kecamatan.nama_kecamatan', 'balai_penyuluh.nama_balai', 'admin_kecamatan_name', 'balai_penyuluh.alamat_balai', 'balai_penyuluh.latitude', 'balai_penyuluh.is_active', 'actions'],
            ['kecamatan.nama_kecamatan', 'balai_penyuluh.nama_balai', 'balai_penyuluh.alamat_balai'],
            'balai_penyuluh.nama_balai',
            function ($row) use ($kecamatanOptions): array {
                $adminName = $row->admin_kecamatan_name ?? '-';
                $adminEmail = $row->admin_kecamatan_email ?? '-';

                return [
                    'nama_kecamatan' => e($row->nama_kecamatan),
                    'nama_balai' => e($row->nama_balai),
                    'admin_kecamatan_name' => e((string) $adminName),
                    'alamat_balai' => e((string) $row->alamat_balai),
                    'koordinat' => trim(($row->latitude ?? '-').', '.($row->longitude ?? '-')),
                    'status' => (bool) $row->is_active ? 'Aktif' : 'Nonaktif',
                    'actions' => $this->actionsHtml($this->payload([
                        'title' => 'Detail Balai Penyuluh',
                        'fields' => [
                            ['label' => 'Kecamatan', 'value' => $row->nama_kecamatan],
                            ['label' => 'Nama Balai', 'value' => $row->nama_balai],
                            ['label' => 'Admin Kecamatan', 'value' => $adminName],
                            ['label' => 'Email Admin Kecamatan', 'value' => $adminEmail],
                            ['label' => 'Alamat', 'value' => $row->alamat_balai],
                            ['label' => 'Status', 'value' => (bool) $row->is_active ? 'Aktif' : 'Nonaktif'],
                        ],
                        'image_url' => $row->foto_balai_url,
                        'spatial' => [
                            'lat' => $row->latitude,
                            'lng' => $row->longitude,
                            'label' => $row->nama_balai,
                            'address' => $row->alamat_balai,
                        ],
                        'edit' => [
                            'url' => route('admin.balai.update', $row->id),
                            'method' => 'PUT',
                            'fields' => [
                                ['name' => 'kecamatan_id', 'label' => 'Kecamatan', 'type' => 'select', 'value' => (string) $row->kecamatan_id, 'required' => true, 'options' => $kecamatanOptions],
                                ['name' => 'nama_balai', 'label' => 'Nama Balai', 'type' => 'text', 'value' => $row->nama_balai, 'required' => true],
                                ['name' => 'alamat_balai', 'label' => 'Alamat', 'type' => 'textarea', 'value' => $row->alamat_balai, 'required' => false],
                                ['name' => 'latitude', 'label' => 'Latitude', 'type' => 'number', 'value' => $row->latitude, 'required' => false, 'step' => '0.0000001'],
                                ['name' => 'longitude', 'label' => 'Longitude', 'type' => 'number', 'value' => $row->longitude, 'required' => false, 'step' => '0.0000001'],
                                ['name' => 'foto_balai', 'label' => 'Upload Foto Balai (opsional)', 'type' => 'file', 'required' => false],
                                ['name' => 'admin_kecamatan_name', 'label' => 'Nama Admin Kecamatan', 'type' => 'text', 'value' => $adminName === '-' ? '' : $adminName, 'required' => false],
                                ['name' => 'admin_kecamatan_email', 'label' => 'Email Admin Kecamatan', 'type' => 'email', 'value' => $adminEmail === '-' ? '' : $adminEmail, 'required' => false],
                                ['name' => 'admin_kecamatan_password', 'label' => 'Password Baru Admin Kecamatan (opsional)', 'type' => 'password', 'value' => '', 'required' => false],
                                ['name' => 'admin_kecamatan_password_confirmation', 'label' => 'Konfirmasi Password Admin Kecamatan', 'type' => 'password', 'value' => '', 'required' => false],
                                [
                                    'name' => 'is_active',
                                    'label' => 'Status',
                                    'type' => 'select',
                                    'value' => (bool) $row->is_active ? '1' : '0',
                                    'required' => true,
                                    'options' => [
                                        ['value' => '1', 'label' => 'Aktif'],
                                        ['value' => '0', 'label' => 'Nonaktif'],
                                    ],
                                ],
                            ],
                            'spatial' => ['lat_field' => 'latitude', 'lng_field' => 'longitude', 'address_field' => 'alamat_balai'],
                        ],
                        'delete' => [
                            'url' => route('admin.balai.destroy', $row->id),
                            'method' => 'DELETE',
                            'label' => 'Hapus balai '.$row->nama_balai.'?',
                        ],
                    ])),
                ];
            }
        );
    }

    private function adminPenyuluh(Request $request): JsonResponse
    {
        $query = DB::table('penyuluh')
            ->join('users', 'users.id', '=', 'penyuluh.user_id')
            ->join('balai_penyuluh', 'balai_penyuluh.id', '=', 'penyuluh.balai_id')
            ->select(
                'penyuluh.id',
                'penyuluh.balai_id',
                'penyuluh.nip',
                'penyuluh.jabatan',
                'penyuluh.lokasi_penugasan',
                'penyuluh.tugas_tambahan',
                'penyuluh.foto_penyuluh_url',
                'penyuluh.is_active',
                'users.name as nama_user',
                'users.email',
                'balai_penyuluh.nama_balai'
            );

        $balaiOptions = DB::table('balai_penyuluh')->orderBy('nama_balai')->get(['id', 'nama_balai'])
            ->map(fn ($x): array => ['value' => (string) $x->id, 'label' => $x->nama_balai])
            ->toArray();

        return $this->respond(
            $request,
            $query,
            ['users.name', 'balai_penyuluh.nama_balai', 'penyuluh.nip', 'penyuluh.jabatan', 'penyuluh.is_active'],
            ['users.name', 'users.email', 'balai_penyuluh.nama_balai', 'penyuluh.nip'],
            'users.name',
            function ($row) use ($balaiOptions): array {
                return [
                    'nama_user' => e($row->nama_user),
                    'nama_balai' => e($row->nama_balai),
                    'nip' => e((string) $row->nip),
                    'jabatan' => e((string) $row->jabatan),
                    'status' => (bool) $row->is_active ? 'Aktif' : 'Nonaktif',
                    'actions' => $this->actionsHtml($this->payload([
                        'title' => 'Detail Penyuluh',
                        'fields' => [
                            ['label' => 'Nama', 'value' => $row->nama_user],
                            ['label' => 'Email', 'value' => $row->email],
                            ['label' => 'Balai', 'value' => $row->nama_balai],
                            ['label' => 'NIP', 'value' => $row->nip],
                            ['label' => 'Jabatan', 'value' => $row->jabatan],
                            ['label' => 'Lokasi Penugasan', 'value' => $row->lokasi_penugasan],
                            ['label' => 'Tugas Tambahan', 'value' => $row->tugas_tambahan],
                        ],
                        'image_url' => $row->foto_penyuluh_url,
                        'edit' => [
                            'url' => route('admin.balai.penyuluh.update', $row->id),
                            'method' => 'PUT',
                            'fields' => [
                                ['name' => 'balai_id', 'label' => 'Balai', 'type' => 'select', 'value' => (string) $row->balai_id, 'required' => true, 'options' => $balaiOptions],
                                ['name' => 'nip', 'label' => 'NIP', 'type' => 'text', 'value' => $row->nip, 'required' => false],
                                ['name' => 'jabatan', 'label' => 'Jabatan', 'type' => 'text', 'value' => $row->jabatan, 'required' => false],
                                ['name' => 'lokasi_penugasan', 'label' => 'Lokasi Penugasan', 'type' => 'text', 'value' => $row->lokasi_penugasan, 'required' => false],
                                ['name' => 'tugas_tambahan', 'label' => 'Tugas Tambahan', 'type' => 'text', 'value' => $row->tugas_tambahan, 'required' => false],
                                ['name' => 'foto_penyuluh', 'label' => 'Upload Foto Penyuluh (maks 5MB)', 'type' => 'file', 'required' => false],
                                [
                                    'name' => 'is_active',
                                    'label' => 'Status',
                                    'type' => 'select',
                                    'value' => (bool) $row->is_active ? '1' : '0',
                                    'required' => true,
                                    'options' => [
                                        ['value' => '1', 'label' => 'Aktif'],
                                        ['value' => '0', 'label' => 'Nonaktif'],
                                    ],
                                ],
                            ],
                        ],
                        'delete' => [
                            'url' => route('admin.balai.penyuluh.destroy', $row->id),
                            'method' => 'DELETE',
                            'label' => 'Hapus penyuluh '.$row->nama_user.'?',
                        ],
                    ])),
                ];
            }
        );
    }

    private function kecamatanPenyuluh(Request $request): JsonResponse
    {
        $kecamatanIds = $this->managedKecamatanIds();

        $query = DB::table('penyuluh')
            ->join('users', 'users.id', '=', 'penyuluh.user_id')
            ->join('balai_penyuluh', 'balai_penyuluh.id', '=', 'penyuluh.balai_id')
            ->whereIn('balai_penyuluh.kecamatan_id', $kecamatanIds)
            ->select(
                'penyuluh.id',
                'penyuluh.balai_id',
                'penyuluh.nip',
                'penyuluh.jabatan',
                'penyuluh.lokasi_penugasan',
                'penyuluh.tugas_tambahan',
                'penyuluh.foto_penyuluh_url',
                'penyuluh.is_active',
                'users.name as nama_user',
                'users.email',
                'balai_penyuluh.nama_balai'
            );

        $balaiOptions = DB::table('balai_penyuluh')
            ->whereIn('kecamatan_id', $kecamatanIds)
            ->orderBy('nama_balai')
            ->get(['id', 'nama_balai'])
            ->map(fn ($x): array => ['value' => (string) $x->id, 'label' => $x->nama_balai])
            ->toArray();

        return $this->respond(
            $request,
            $query,
            ['users.name', 'balai_penyuluh.nama_balai', 'penyuluh.nip', 'penyuluh.jabatan', 'penyuluh.is_active'],
            ['users.name', 'users.email', 'balai_penyuluh.nama_balai', 'penyuluh.nip'],
            'users.name',
            function ($row) use ($balaiOptions): array {
                return [
                    'nama_user' => e($row->nama_user),
                    'nama_balai' => e($row->nama_balai),
                    'nip' => e((string) $row->nip),
                    'jabatan' => e((string) $row->jabatan),
                    'status' => (bool) $row->is_active ? 'Aktif' : 'Nonaktif',
                    'actions' => $this->actionsHtml($this->payload([
                        'title' => 'Detail Penyuluh Kecamatan',
                        'fields' => [
                            ['label' => 'Nama', 'value' => $row->nama_user],
                            ['label' => 'Email', 'value' => $row->email],
                            ['label' => 'Balai', 'value' => $row->nama_balai],
                            ['label' => 'NIP', 'value' => $row->nip],
                            ['label' => 'Jabatan', 'value' => $row->jabatan],
                        ],
                        'image_url' => $row->foto_penyuluh_url,
                        'edit' => [
                            'url' => route('kecamatan.penyuluh.update', $row->id),
                            'method' => 'PUT',
                            'fields' => [
                                ['name' => 'balai_id', 'label' => 'Balai', 'type' => 'select', 'value' => (string) $row->balai_id, 'required' => true, 'options' => $balaiOptions],
                                ['name' => 'nip', 'label' => 'NIP', 'type' => 'text', 'value' => $row->nip, 'required' => false],
                                ['name' => 'jabatan', 'label' => 'Jabatan', 'type' => 'text', 'value' => $row->jabatan, 'required' => false],
                                ['name' => 'lokasi_penugasan', 'label' => 'Lokasi Penugasan', 'type' => 'text', 'value' => $row->lokasi_penugasan, 'required' => false],
                                ['name' => 'tugas_tambahan', 'label' => 'Tugas Tambahan', 'type' => 'text', 'value' => $row->tugas_tambahan, 'required' => false],
                                ['name' => 'foto_penyuluh', 'label' => 'Upload Foto Penyuluh (maks 5MB)', 'type' => 'file', 'required' => false],
                                [
                                    'name' => 'is_active',
                                    'label' => 'Status',
                                    'type' => 'select',
                                    'value' => (bool) $row->is_active ? '1' : '0',
                                    'required' => true,
                                    'options' => [
                                        ['value' => '1', 'label' => 'Aktif'],
                                        ['value' => '0', 'label' => 'Nonaktif'],
                                    ],
                                ],
                            ],
                        ],
                        'delete' => [
                            'url' => route('kecamatan.penyuluh.destroy', $row->id),
                            'method' => 'DELETE',
                            'label' => 'Hapus penyuluh '.$row->nama_user.'?',
                        ],
                    ])),
                ];
            }
        );
    }

    private function kecamatanPenugasan(Request $request): JsonResponse
    {
        $kecamatanIds = $this->managedKecamatanIds();

        $query = DB::table('penugasan_penyuluh')
            ->join('penyuluh', 'penyuluh.id', '=', 'penugasan_penyuluh.penyuluh_id')
            ->join('users', 'users.id', '=', 'penyuluh.user_id')
            ->join('lahan', 'lahan.id', '=', 'penugasan_penyuluh.lahan_id')
            ->join('petani', 'petani.id', '=', 'lahan.petani_id')
            ->join('desa', 'desa.id', '=', 'lahan.desa_id')
            ->join('kecamatan', 'kecamatan.id', '=', 'desa.kecamatan_id')
            ->whereIn('desa.kecamatan_id', $kecamatanIds)
            ->select(
                'penugasan_penyuluh.id',
                'penugasan_penyuluh.penyuluh_id',
                'penugasan_penyuluh.lahan_id',
                'penugasan_penyuluh.tanggal_mulai',
                'penugasan_penyuluh.tanggal_selesai',
                'penugasan_penyuluh.status_penugasan',
                'users.name as nama_penyuluh',
                'petani.nama_petani',
                'desa.nama_desa',
                'kecamatan.nama_kecamatan'
            );

        $penyuluhOptions = DB::table('penyuluh')
            ->join('users', 'users.id', '=', 'penyuluh.user_id')
            ->join('balai_penyuluh', 'balai_penyuluh.id', '=', 'penyuluh.balai_id')
            ->whereIn('balai_penyuluh.kecamatan_id', $kecamatanIds)
            ->where('penyuluh.is_active', true)
            ->orderBy('users.name')
            ->select('penyuluh.id', 'users.name as nama_penyuluh', 'balai_penyuluh.nama_balai')
            ->get()
            ->map(fn ($x): array => ['value' => (string) $x->id, 'label' => $x->nama_penyuluh.' ('.$x->nama_balai.')'])
            ->toArray();

        $lahanOptions = DB::table('lahan')
            ->join('petani', 'petani.id', '=', 'lahan.petani_id')
            ->join('desa', 'desa.id', '=', 'lahan.desa_id')
            ->whereIn('desa.kecamatan_id', $kecamatanIds)
            ->where('lahan.is_active', true)
            ->orderBy('petani.nama_petani')
            ->select('lahan.id', 'petani.nama_petani', 'desa.nama_desa', 'lahan.luas_ha')
            ->get()
            ->map(fn ($x): array => ['value' => (string) $x->id, 'label' => $x->nama_petani.' - '.$x->nama_desa.' ('.$x->luas_ha.' ha)'])
            ->toArray();

        return $this->respond(
            $request,
            $query,
            ['users.name', 'petani.nama_petani', 'desa.nama_desa', 'penugasan_penyuluh.tanggal_mulai', 'penugasan_penyuluh.status_penugasan', 'actions'],
            ['users.name', 'petani.nama_petani', 'desa.nama_desa', 'kecamatan.nama_kecamatan', 'penugasan_penyuluh.status_penugasan'],
            'penugasan_penyuluh.tanggal_mulai',
            function ($row) use ($penyuluhOptions, $lahanOptions): array {
                return [
                    'nama_penyuluh' => e($row->nama_penyuluh),
                    'nama_petani' => e($row->nama_petani),
                    'nama_desa' => e($row->nama_desa),
                    'tanggal_mulai' => (string) $row->tanggal_mulai,
                    'status_penugasan' => e(ucfirst((string) $row->status_penugasan)),
                    'actions' => $this->actionsHtml($this->payload([
                        'title' => 'Detail Penugasan Penyuluh',
                        'fields' => [
                            ['label' => 'Penyuluh', 'value' => $row->nama_penyuluh],
                            ['label' => 'Petani', 'value' => $row->nama_petani],
                            ['label' => 'Desa', 'value' => $row->nama_desa],
                            ['label' => 'Kecamatan', 'value' => $row->nama_kecamatan],
                            ['label' => 'Tanggal Mulai', 'value' => $row->tanggal_mulai],
                            ['label' => 'Tanggal Selesai', 'value' => $row->tanggal_selesai],
                            ['label' => 'Status', 'value' => ucfirst((string) $row->status_penugasan)],
                        ],
                        'edit' => [
                            'url' => route('kecamatan.penyuluh.penugasan.update', $row->id),
                            'method' => 'PUT',
                            'fields' => [
                                ['name' => 'penyuluh_id', 'label' => 'Penyuluh', 'type' => 'select', 'value' => (string) $row->penyuluh_id, 'required' => true, 'options' => $penyuluhOptions],
                                ['name' => 'lahan_id', 'label' => 'Petani/Lahan', 'type' => 'select', 'value' => (string) $row->lahan_id, 'required' => true, 'options' => $lahanOptions],
                                ['name' => 'tanggal_mulai', 'label' => 'Tanggal Mulai', 'type' => 'date', 'value' => $row->tanggal_mulai, 'required' => true],
                                ['name' => 'tanggal_selesai', 'label' => 'Tanggal Selesai', 'type' => 'date', 'value' => $row->tanggal_selesai, 'required' => false],
                                [
                                    'name' => 'status_penugasan',
                                    'label' => 'Status Penugasan',
                                    'type' => 'select',
                                    'value' => $row->status_penugasan,
                                    'required' => true,
                                    'options' => collect(['aktif', 'selesai', 'dibatalkan'])->map(fn ($x): array => ['value' => $x, 'label' => ucfirst($x)])->toArray(),
                                ],
                            ],
                        ],
                        'delete' => [
                            'url' => route('kecamatan.penyuluh.penugasan.destroy', $row->id),
                            'method' => 'DELETE',
                            'label' => 'Hapus penugasan penyuluh untuk petani '.$row->nama_petani.'?',
                        ],
                    ])),
                ];
            }
        );
    }

    private function kecamatanPetani(Request $request): JsonResponse
    {
        $kecamatanIds = $this->managedKecamatanIds();

        $query = DB::table('petani')
            ->join('desa', 'desa.id', '=', 'petani.desa_id')
            ->join('kecamatan', 'kecamatan.id', '=', 'desa.kecamatan_id')
            ->whereIn('desa.kecamatan_id', $kecamatanIds)
            ->select(
                'petani.id',
                'petani.desa_id',
                'petani.nama_petani',
                'petani.no_hp',
                'petani.alamat_domisili',
                'petani.kelompok_tani',
                'petani.foto_petani_url',
                'petani.is_active',
                'desa.nama_desa',
                'kecamatan.nama_kecamatan'
            );

        $desaOptions = DB::table('desa')
            ->whereIn('kecamatan_id', $kecamatanIds)
            ->orderBy('nama_desa')
            ->get(['id', 'nama_desa'])
            ->map(fn ($x): array => ['value' => (string) $x->id, 'label' => $x->nama_desa])
            ->toArray();

        $kelompokOptions = DB::table('master_kelompok_tani')
            ->where('is_active', true)
            ->orderBy('nama_kelompok')
            ->get(['nama_kelompok'])
            ->map(fn ($x): array => ['value' => (string) $x->nama_kelompok, 'label' => $x->nama_kelompok])
            ->toArray();

        return $this->respond(
            $request,
            $query,
            ['petani.nama_petani', 'desa.nama_desa', 'kecamatan.nama_kecamatan', 'petani.kelompok_tani', 'petani.no_hp', 'petani.is_active', 'actions'],
            ['petani.nama_petani', 'petani.no_hp', 'petani.kelompok_tani', 'desa.nama_desa', 'kecamatan.nama_kecamatan'],
            'petani.nama_petani',
            function ($row) use ($desaOptions, $kelompokOptions): array {
                return [
                    'nama_petani' => e($row->nama_petani),
                    'nama_desa' => e($row->nama_desa),
                    'nama_kecamatan' => e($row->nama_kecamatan),
                    'kelompok_tani' => e((string) $row->kelompok_tani),
                    'no_hp' => e((string) $row->no_hp),
                    'status' => (bool) $row->is_active ? 'Aktif' : 'Nonaktif',
                    'actions' => $this->actionsHtml($this->payload([
                        'title' => 'Detail Petani',
                        'fields' => [
                            ['label' => 'Nama Petani', 'value' => $row->nama_petani],
                            ['label' => 'No HP', 'value' => $row->no_hp],
                            ['label' => 'Kecamatan', 'value' => $row->nama_kecamatan],
                            ['label' => 'Desa', 'value' => $row->nama_desa],
                            ['label' => 'Kelompok Tani', 'value' => $row->kelompok_tani],
                            ['label' => 'Alamat', 'value' => $row->alamat_domisili],
                            ['label' => 'Status', 'value' => (bool) $row->is_active ? 'Aktif' : 'Nonaktif'],
                        ],
                        'image_url' => $row->foto_petani_url,
                        'edit' => [
                            'url' => route('kecamatan.petani_lahan.petani.update', $row->id),
                            'method' => 'PUT',
                            'fields' => [
                                ['name' => 'desa_id', 'label' => 'Desa', 'type' => 'select', 'value' => (string) $row->desa_id, 'required' => true, 'options' => $desaOptions],
                                ['name' => 'nama_petani', 'label' => 'Nama Petani', 'type' => 'text', 'value' => $row->nama_petani, 'required' => true],
                                ['name' => 'no_hp', 'label' => 'No HP', 'type' => 'text', 'value' => $row->no_hp, 'required' => false],
                                ['name' => 'kelompok_tani', 'label' => 'Kelompok Tani', 'type' => 'select', 'value' => $row->kelompok_tani, 'required' => false, 'options' => $kelompokOptions],
                                ['name' => 'alamat_domisili', 'label' => 'Alamat', 'type' => 'textarea', 'value' => $row->alamat_domisili, 'required' => false],
                                ['name' => 'foto_petani', 'label' => 'Upload Foto Petani (maks 5MB)', 'type' => 'file', 'required' => false],
                                [
                                    'name' => 'is_active',
                                    'label' => 'Status',
                                    'type' => 'select',
                                    'value' => (bool) $row->is_active ? '1' : '0',
                                    'required' => true,
                                    'options' => [
                                        ['value' => '1', 'label' => 'Aktif'],
                                        ['value' => '0', 'label' => 'Nonaktif'],
                                    ],
                                ],
                            ],
                        ],
                        'delete' => [
                            'url' => route('kecamatan.petani_lahan.petani.destroy', $row->id),
                            'method' => 'DELETE',
                            'label' => 'Hapus petani '.$row->nama_petani.'?',
                        ],
                    ])),
                ];
            }
        );
    }

    private function kecamatanLahan(Request $request): JsonResponse
    {
        $kecamatanIds = $this->managedKecamatanIds();

        $query = DB::table('lahan')
            ->join('petani', 'petani.id', '=', 'lahan.petani_id')
            ->join('desa', 'desa.id', '=', 'lahan.desa_id')
            ->join('kecamatan', 'kecamatan.id', '=', 'desa.kecamatan_id')
            ->whereIn('desa.kecamatan_id', $kecamatanIds)
            ->select(
                'lahan.id',
                'lahan.petani_id',
                'lahan.desa_id',
                'lahan.alamat_lahan',
                'lahan.luas_ha',
                'lahan.kondisi_lahan',
                'lahan.latitude',
                'lahan.longitude',
                'lahan.foto_lahan_url',
                'lahan.is_active',
                'petani.nama_petani',
                'desa.nama_desa',
                'kecamatan.nama_kecamatan'
            );

        $petaniOptions = DB::table('petani')
            ->join('desa', 'desa.id', '=', 'petani.desa_id')
            ->whereIn('desa.kecamatan_id', $kecamatanIds)
            ->orderBy('petani.nama_petani')
            ->select('petani.id', 'petani.nama_petani')
            ->get()
            ->map(fn ($x): array => ['value' => (string) $x->id, 'label' => $x->nama_petani])
            ->toArray();

        $desaOptions = DB::table('desa')
            ->whereIn('kecamatan_id', $kecamatanIds)
            ->orderBy('nama_desa')
            ->get(['id', 'nama_desa'])
            ->map(fn ($x): array => ['value' => (string) $x->id, 'label' => $x->nama_desa])
            ->toArray();

        return $this->respond(
            $request,
            $query,
            ['petani.nama_petani', 'desa.nama_desa', 'lahan.luas_ha', 'lahan.kondisi_lahan', 'lahan.latitude', 'lahan.is_active'],
            ['petani.nama_petani', 'desa.nama_desa', 'kecamatan.nama_kecamatan', 'lahan.alamat_lahan'],
            'lahan.id',
            function ($row) use ($petaniOptions, $desaOptions): array {
                return [
                    'nama_petani' => e($row->nama_petani),
                    'nama_desa' => e($row->nama_desa),
                    'luas_ha' => $row->luas_ha,
                    'kondisi_lahan' => e((string) $row->kondisi_lahan),
                    'koordinat' => trim(($row->latitude ?? '-').', '.($row->longitude ?? '-')),
                    'status' => (bool) $row->is_active ? 'Aktif' : 'Nonaktif',
                    'actions' => $this->actionsHtml($this->payload([
                        'title' => 'Detail Lahan',
                        'fields' => [
                            ['label' => 'Petani', 'value' => $row->nama_petani],
                            ['label' => 'Kecamatan', 'value' => $row->nama_kecamatan],
                            ['label' => 'Desa', 'value' => $row->nama_desa],
                            ['label' => 'Luas (ha)', 'value' => $row->luas_ha],
                            ['label' => 'Kondisi', 'value' => $row->kondisi_lahan],
                            ['label' => 'Alamat', 'value' => $row->alamat_lahan],
                            ['label' => 'Latitude', 'value' => $row->latitude],
                            ['label' => 'Longitude', 'value' => $row->longitude],
                            ['label' => 'Status', 'value' => (bool) $row->is_active ? 'Aktif' : 'Nonaktif'],
                        ],
                        'image_url' => $row->foto_lahan_url,
                        'spatial' => [
                            'lat' => $row->latitude,
                            'lng' => $row->longitude,
                            'label' => 'Lahan '.$row->nama_petani,
                            'address' => $row->alamat_lahan,
                        ],
                        'edit' => [
                            'url' => route('kecamatan.petani_lahan.lahan.update', $row->id),
                            'method' => 'PUT',
                            'fields' => [
                                ['name' => 'petani_id', 'label' => 'Petani', 'type' => 'select', 'value' => (string) $row->petani_id, 'required' => true, 'options' => $petaniOptions],
                                ['name' => 'desa_id', 'label' => 'Desa', 'type' => 'select', 'value' => (string) $row->desa_id, 'required' => true, 'options' => $desaOptions],
                                ['name' => 'alamat_lahan', 'label' => 'Alamat Lahan', 'type' => 'textarea', 'value' => $row->alamat_lahan, 'required' => false],
                                ['name' => 'luas_ha', 'label' => 'Luas (ha)', 'type' => 'number', 'value' => $row->luas_ha, 'required' => false, 'step' => '0.01'],
                                [
                                    'name' => 'kondisi_lahan',
                                    'label' => 'Kondisi',
                                    'type' => 'select',
                                    'value' => $row->kondisi_lahan,
                                    'required' => false,
                                    'options' => collect(['baik', 'sedang', 'rusak', 'kritis'])->map(fn ($x): array => ['value' => $x, 'label' => ucfirst($x)])->toArray(),
                                ],
                                ['name' => 'latitude', 'label' => 'Latitude', 'type' => 'number', 'value' => $row->latitude, 'required' => false, 'step' => '0.0000001'],
                                ['name' => 'longitude', 'label' => 'Longitude', 'type' => 'number', 'value' => $row->longitude, 'required' => false, 'step' => '0.0000001'],
                                ['name' => 'foto_lahan', 'label' => 'Upload Foto Lahan (maks 5MB)', 'type' => 'file', 'required' => false],
                                [
                                    'name' => 'is_active',
                                    'label' => 'Status',
                                    'type' => 'select',
                                    'value' => (bool) $row->is_active ? '1' : '0',
                                    'required' => true,
                                    'options' => [
                                        ['value' => '1', 'label' => 'Aktif'],
                                        ['value' => '0', 'label' => 'Nonaktif'],
                                    ],
                                ],
                            ],
                            'spatial' => ['lat_field' => 'latitude', 'lng_field' => 'longitude', 'address_field' => 'alamat_lahan'],
                        ],
                        'delete' => [
                            'url' => route('kecamatan.petani_lahan.lahan.destroy', $row->id),
                            'method' => 'DELETE',
                            'label' => 'Hapus data lahan petani '.$row->nama_petani.'?',
                        ],
                    ])),
                ];
            }
        );
    }

    private function kecamatanLahanKomoditas(Request $request): JsonResponse
    {
        $kecamatanIds = $this->managedKecamatanIds();

        $query = DB::table('lahan_komoditas')
            ->join('lahan', 'lahan.id', '=', 'lahan_komoditas.lahan_id')
            ->join('petani', 'petani.id', '=', 'lahan.petani_id')
            ->join('desa', 'desa.id', '=', 'lahan.desa_id')
            ->join('komoditas', 'komoditas.id', '=', 'lahan_komoditas.komoditas_id')
            ->whereIn('desa.kecamatan_id', $kecamatanIds)
            ->select(
                'lahan_komoditas.id',
                'lahan_komoditas.tahun_tanam',
                'lahan_komoditas.luas_tanam_ha',
                'lahan_komoditas.status_tanam',
                'lahan_komoditas.catatan',
                'lahan_komoditas.latitude',
                'lahan_komoditas.longitude',
                'lahan_komoditas.alamat_titik',
                'lahan.foto_lahan_url',
                'petani.nama_petani',
                'komoditas.nama_komoditas'
            );

        return $this->respond(
            $request,
            $query,
            ['petani.nama_petani', 'komoditas.nama_komoditas', 'lahan_komoditas.tahun_tanam', 'lahan_komoditas.luas_tanam_ha', 'lahan_komoditas.latitude', 'lahan_komoditas.status_tanam'],
            ['petani.nama_petani', 'komoditas.nama_komoditas', 'lahan_komoditas.status_tanam', 'lahan_komoditas.alamat_titik'],
            'lahan_komoditas.id',
            function ($row): array {
                return [
                    'nama_petani' => e($row->nama_petani),
                    'nama_komoditas' => e($row->nama_komoditas),
                    'tahun_tanam' => (string) $row->tahun_tanam,
                    'luas_tanam_ha' => (string) $row->luas_tanam_ha,
                    'koordinat' => trim(($row->latitude ?? '-').', '.($row->longitude ?? '-')),
                    'status_tanam' => e((string) $row->status_tanam),
                    'actions' => $this->actionsHtml($this->payload([
                        'title' => 'Detail Lahan Komoditas',
                        'fields' => [
                            ['label' => 'Petani', 'value' => $row->nama_petani],
                            ['label' => 'Komoditas', 'value' => $row->nama_komoditas],
                            ['label' => 'Tahun Tanam', 'value' => $row->tahun_tanam],
                            ['label' => 'Luas Tanam (ha)', 'value' => $row->luas_tanam_ha],
                            ['label' => 'Status Tanam', 'value' => $row->status_tanam],
                            ['label' => 'Latitude', 'value' => $row->latitude],
                            ['label' => 'Longitude', 'value' => $row->longitude],
                            ['label' => 'Alamat Titik', 'value' => $row->alamat_titik],
                            ['label' => 'Catatan', 'value' => $row->catatan],
                        ],
                        'image_url' => $row->foto_lahan_url,
                        'spatial' => [
                            'lat' => $row->latitude,
                            'lng' => $row->longitude,
                            'label' => 'Titik Komoditas '.$row->nama_komoditas,
                            'address' => $row->alamat_titik,
                        ],
                        'edit' => [
                            'url' => route('kecamatan.petani_lahan.lahan_komoditas.update', $row->id),
                            'method' => 'PUT',
                            'fields' => [
                                ['name' => 'tahun_tanam', 'label' => 'Tahun Tanam', 'type' => 'number', 'value' => $row->tahun_tanam, 'required' => false],
                                ['name' => 'luas_tanam_ha', 'label' => 'Luas Tanam (ha)', 'type' => 'number', 'value' => $row->luas_tanam_ha, 'required' => false, 'step' => '0.01'],
                                [
                                    'name' => 'status_tanam',
                                    'label' => 'Status Tanam',
                                    'type' => 'select',
                                    'value' => $row->status_tanam,
                                    'required' => true,
                                    'options' => collect(['rencana', 'tanam', 'panen', 'bera', 'gagal'])->map(fn ($x): array => ['value' => $x, 'label' => ucfirst($x)])->toArray(),
                                ],
                                ['name' => 'latitude', 'label' => 'Latitude Titik Komoditas', 'type' => 'number', 'value' => $row->latitude, 'required' => true, 'step' => '0.0000001'],
                                ['name' => 'longitude', 'label' => 'Longitude Titik Komoditas', 'type' => 'number', 'value' => $row->longitude, 'required' => true, 'step' => '0.0000001'],
                                ['name' => 'alamat_titik', 'label' => 'Alamat Titik', 'type' => 'textarea', 'value' => $row->alamat_titik, 'required' => false],
                                ['name' => 'catatan', 'label' => 'Catatan', 'type' => 'textarea', 'value' => $row->catatan, 'required' => false],
                            ],
                            'spatial' => ['lat_field' => 'latitude', 'lng_field' => 'longitude', 'address_field' => 'alamat_titik'],
                        ],
                        'delete' => [
                            'url' => route('kecamatan.petani_lahan.lahan_komoditas.destroy', $row->id),
                            'method' => 'DELETE',
                            'label' => 'Hapus data lahan komoditas?',
                        ],
                    ])),
                ];
            }
        );
    }

    private function penyuluhKunjungan(Request $request): JsonResponse
    {
        $penyuluhId = $this->penyuluhId();
        if (! $penyuluhId) {
            return $this->empty($request);
        }

        $query = DB::table('kunjungan_monitoring')
            ->join('penugasan_penyuluh', 'penugasan_penyuluh.id', '=', 'kunjungan_monitoring.penugasan_id')
            ->join('lahan', 'lahan.id', '=', 'penugasan_penyuluh.lahan_id')
            ->join('petani', 'petani.id', '=', 'lahan.petani_id')
            ->leftJoin('lampiran_media as foto_kunjungan', function ($join): void {
                $join->on('foto_kunjungan.kunjungan_id', '=', 'kunjungan_monitoring.id')
                    ->where('foto_kunjungan.file_type', '=', 'image')
                    ->whereRaw("foto_kunjungan.id = (SELECT MAX(fu.id) FROM lampiran_media fu WHERE fu.kunjungan_id = kunjungan_monitoring.id AND fu.file_url LIKE '%/kunjungan/utama/%')");
            })
            ->leftJoin('lampiran_media as foto_kendala', function ($join): void {
                $join->on('foto_kendala.kunjungan_id', '=', 'kunjungan_monitoring.id')
                    ->where('foto_kendala.file_type', '=', 'image')
                    ->whereRaw("foto_kendala.id = (SELECT MAX(fk.id) FROM lampiran_media fk WHERE fk.kunjungan_id = kunjungan_monitoring.id AND fk.file_url LIKE '%/kunjungan/kendala/%')");
            })
            ->where('penugasan_penyuluh.penyuluh_id', $penyuluhId)
            ->select(
                'kunjungan_monitoring.id',
                'kunjungan_monitoring.tanggal_kunjungan',
                'kunjungan_monitoring.kondisi_tanaman',
                'kunjungan_monitoring.catatan_umum',
                'kunjungan_monitoring.rekomendasi',
                'kunjungan_monitoring.latitude',
                'kunjungan_monitoring.longitude',
                'kunjungan_monitoring.status_verifikasi',
                'petani.nama_petani',
                'foto_kunjungan.file_url as foto_kunjungan_url',
                'foto_kendala.file_url as foto_kendala_url'
            );

        return $this->respond(
            $request,
            $query,
            ['kunjungan_monitoring.tanggal_kunjungan', 'petani.nama_petani', 'kunjungan_monitoring.status_verifikasi', 'kunjungan_monitoring.latitude'],
            ['petani.nama_petani', 'kunjungan_monitoring.kondisi_tanaman', 'kunjungan_monitoring.catatan_umum', 'kunjungan_monitoring.rekomendasi', 'kunjungan_monitoring.status_verifikasi'],
            'kunjungan_monitoring.tanggal_kunjungan',
            function ($row): array {
                return [
                    'tanggal_kunjungan' => (string) $row->tanggal_kunjungan,
                    'nama_petani' => e($row->nama_petani),
                    'status_verifikasi' => e((string) $row->status_verifikasi),
                    'koordinat' => trim(($row->latitude ?? '-').', '.($row->longitude ?? '-')),
                    'actions' => $this->actionsHtml($this->payload([
                        'title' => 'Detail Kunjungan',
                        'fields' => [
                            ['label' => 'Tanggal Kunjungan', 'value' => $row->tanggal_kunjungan],
                            ['label' => 'Petani', 'value' => $row->nama_petani],
                            ['label' => 'Status Verifikasi', 'value' => $row->status_verifikasi],
                            ['label' => 'Kondisi Tanaman', 'value' => $row->kondisi_tanaman],
                            ['label' => 'Catatan Umum', 'value' => $row->catatan_umum],
                            ['label' => 'Rekomendasi', 'value' => $row->rekomendasi],
                            ['label' => 'Foto Kendala', 'value' => $row->foto_kendala_url ?: '-'],
                        ],
                        'image_url' => $row->foto_kunjungan_url ?: $row->foto_kendala_url,
                        'spatial' => [
                            'lat' => $row->latitude,
                            'lng' => $row->longitude,
                            'label' => 'Kunjungan '.$row->nama_petani,
                        ],
                        'edit' => [
                            'url' => route('penyuluh.kunjungan.update', $row->id),
                            'method' => 'PUT',
                            'fields' => [
                                ['name' => 'tanggal_kunjungan', 'label' => 'Tanggal Kunjungan', 'type' => 'datetime-local', 'value' => $this->toDatetimeLocal($row->tanggal_kunjungan), 'required' => true],
                                ['name' => 'kondisi_tanaman', 'label' => 'Kondisi Tanaman', 'type' => 'textarea', 'value' => $row->kondisi_tanaman, 'required' => false],
                                ['name' => 'catatan_umum', 'label' => 'Catatan Umum', 'type' => 'textarea', 'value' => $row->catatan_umum, 'required' => false],
                                ['name' => 'rekomendasi', 'label' => 'Rekomendasi', 'type' => 'textarea', 'value' => $row->rekomendasi, 'required' => false],
                                [
                                    'name' => 'status_verifikasi',
                                    'label' => 'Status Verifikasi',
                                    'type' => 'select',
                                    'value' => $row->status_verifikasi,
                                    'required' => true,
                                    'options' => collect(['draft', 'menunggu', 'revisi'])->map(fn ($x): array => ['value' => $x, 'label' => ucfirst($x)])->toArray(),
                                ],
                            ],
                        ],
                        'delete' => [
                            'url' => route('penyuluh.kunjungan.destroy', $row->id),
                            'method' => 'DELETE',
                            'label' => 'Hapus data kunjungan ini?',
                        ],
                    ])),
                ];
            }
        );
    }

    private function penyuluhProduksi(Request $request): JsonResponse
    {
        $penyuluhId = $this->penyuluhId();
        if (! $penyuluhId) {
            return $this->empty($request);
        }

        $query = DB::table('produksi_panen')
            ->join('lahan_komoditas', 'lahan_komoditas.id', '=', 'produksi_panen.lahan_komoditas_id')
            ->join('lahan', 'lahan.id', '=', 'lahan_komoditas.lahan_id')
            ->join('petani', 'petani.id', '=', 'lahan.petani_id')
            ->join('komoditas', 'komoditas.id', '=', 'lahan_komoditas.komoditas_id')
            ->join('periode_laporan', 'periode_laporan.id', '=', 'produksi_panen.periode_id')
            ->leftJoin('kunjungan_monitoring', 'kunjungan_monitoring.id', '=', 'produksi_panen.kunjungan_id')
            ->leftJoin('penugasan_penyuluh', 'penugasan_penyuluh.id', '=', 'kunjungan_monitoring.penugasan_id')
            ->where(function ($q) use ($penyuluhId): void {
                $q->where('penugasan_penyuluh.penyuluh_id', $penyuluhId)
                    ->orWhereNull('produksi_panen.kunjungan_id');
            })
            ->select(
                'produksi_panen.id',
                'produksi_panen.tanggal_panen',
                'produksi_panen.jumlah_produksi',
                'produksi_panen.produktivitas_kg_ha',
                'produksi_panen.harga_jual',
                'produksi_panen.catatan',
                'petani.nama_petani',
                'komoditas.nama_komoditas',
                'periode_laporan.bulan',
                'periode_laporan.tahun'
            );

        return $this->respond(
            $request,
            $query,
            ['produksi_panen.tanggal_panen', 'petani.nama_petani', 'komoditas.nama_komoditas', 'produksi_panen.jumlah_produksi', 'periode_laporan.tahun'],
            ['petani.nama_petani', 'komoditas.nama_komoditas', 'produksi_panen.catatan'],
            'produksi_panen.tanggal_panen',
            function ($row): array {
                return [
                    'tanggal_panen' => (string) $row->tanggal_panen,
                    'nama_petani' => e($row->nama_petani),
                    'nama_komoditas' => e($row->nama_komoditas),
                    'jumlah_produksi' => (string) $row->jumlah_produksi,
                    'periode' => sprintf('%02d/%d', (int) $row->bulan, (int) $row->tahun),
                    'actions' => $this->actionsHtml($this->payload([
                        'title' => 'Detail Produksi Panen',
                        'fields' => [
                            ['label' => 'Tanggal Panen', 'value' => $row->tanggal_panen],
                            ['label' => 'Petani', 'value' => $row->nama_petani],
                            ['label' => 'Komoditas', 'value' => $row->nama_komoditas],
                            ['label' => 'Jumlah Produksi', 'value' => $row->jumlah_produksi],
                            ['label' => 'Produktivitas', 'value' => $row->produktivitas_kg_ha],
                            ['label' => 'Harga Jual', 'value' => $row->harga_jual],
                            ['label' => 'Catatan', 'value' => $row->catatan],
                        ],
                        'edit' => [
                            'url' => route('penyuluh.produksi.update', $row->id),
                            'method' => 'PUT',
                            'fields' => [
                                ['name' => 'tanggal_panen', 'label' => 'Tanggal Panen', 'type' => 'date', 'value' => $row->tanggal_panen, 'required' => true],
                                ['name' => 'jumlah_produksi', 'label' => 'Jumlah Produksi', 'type' => 'number', 'value' => $row->jumlah_produksi, 'required' => true, 'step' => '0.01'],
                                ['name' => 'produktivitas_kg_ha', 'label' => 'Produktivitas', 'type' => 'number', 'value' => $row->produktivitas_kg_ha, 'required' => false, 'step' => '0.01'],
                                ['name' => 'harga_jual', 'label' => 'Harga Jual', 'type' => 'number', 'value' => $row->harga_jual, 'required' => false, 'step' => '0.01'],
                                ['name' => 'catatan', 'label' => 'Catatan', 'type' => 'textarea', 'value' => $row->catatan, 'required' => false],
                            ],
                        ],
                        'delete' => [
                            'url' => route('penyuluh.produksi.destroy', $row->id),
                            'method' => 'DELETE',
                            'label' => 'Hapus data produksi ini?',
                        ],
                    ])),
                ];
            }
        );
    }

    private function penyuluhKendala(Request $request): JsonResponse
    {
        $penyuluhId = $this->penyuluhId();
        if (! $penyuluhId) {
            return $this->empty($request);
        }

        $query = DB::table('kendala_kunjungan')
            ->join('kunjungan_monitoring', 'kunjungan_monitoring.id', '=', 'kendala_kunjungan.kunjungan_id')
            ->join('penugasan_penyuluh', 'penugasan_penyuluh.id', '=', 'kunjungan_monitoring.penugasan_id')
            ->join('kategori_kendala', 'kategori_kendala.id', '=', 'kendala_kunjungan.kategori_kendala_id')
            ->join('lahan', 'lahan.id', '=', 'penugasan_penyuluh.lahan_id')
            ->join('petani', 'petani.id', '=', 'lahan.petani_id')
            ->where('penugasan_penyuluh.penyuluh_id', $penyuluhId)
            ->select(
                'kendala_kunjungan.id',
                'kendala_kunjungan.kunjungan_id',
                'kendala_kunjungan.kategori_kendala_id',
                'kendala_kunjungan.deskripsi_kendala',
                'kendala_kunjungan.tingkat_keparahan',
                'kendala_kunjungan.perlu_tindak_lanjut',
                'petani.nama_petani',
                'kategori_kendala.nama_kategori'
            );

        $kategoriOptions = DB::table('kategori_kendala')->orderBy('nama_kategori')->get(['id', 'nama_kategori'])
            ->map(fn ($x): array => ['value' => (string) $x->id, 'label' => $x->nama_kategori])
            ->toArray();
        $kunjunganOptions = $this->kunjunganOptionsForPenyuluh($penyuluhId);

        return $this->respond(
            $request,
            $query,
            ['petani.nama_petani', 'kategori_kendala.nama_kategori', 'kendala_kunjungan.tingkat_keparahan', 'kendala_kunjungan.perlu_tindak_lanjut'],
            ['petani.nama_petani', 'kategori_kendala.nama_kategori', 'kendala_kunjungan.deskripsi_kendala'],
            'kendala_kunjungan.id',
            function ($row) use ($kategoriOptions, $kunjunganOptions): array {
                return [
                    'nama_petani' => e($row->nama_petani),
                    'nama_kategori' => e($row->nama_kategori),
                    'tingkat_keparahan' => e($row->tingkat_keparahan),
                    'perlu_tindak_lanjut' => (bool) $row->perlu_tindak_lanjut ? 'Ya' : 'Tidak',
                    'actions' => $this->actionsHtml($this->payload([
                        'title' => 'Detail Kendala',
                        'fields' => [
                            ['label' => 'Petani', 'value' => $row->nama_petani],
                            ['label' => 'Kategori', 'value' => $row->nama_kategori],
                            ['label' => 'Deskripsi', 'value' => $row->deskripsi_kendala],
                            ['label' => 'Keparahan', 'value' => $row->tingkat_keparahan],
                            ['label' => 'Perlu Tindak Lanjut', 'value' => (bool) $row->perlu_tindak_lanjut ? 'Ya' : 'Tidak'],
                        ],
                        'edit' => [
                            'url' => route('penyuluh.kendala.kendala.update', $row->id),
                            'method' => 'PUT',
                            'fields' => [
                                ['name' => 'kunjungan_id', 'label' => 'Kunjungan', 'type' => 'select', 'value' => (string) $row->kunjungan_id, 'required' => true, 'options' => $kunjunganOptions],
                                ['name' => 'kategori_kendala_id', 'label' => 'Kategori Kendala', 'type' => 'select', 'value' => (string) $row->kategori_kendala_id, 'required' => true, 'options' => $kategoriOptions],
                                ['name' => 'deskripsi_kendala', 'label' => 'Deskripsi Kendala', 'type' => 'textarea', 'value' => $row->deskripsi_kendala, 'required' => true],
                                [
                                    'name' => 'tingkat_keparahan',
                                    'label' => 'Keparahan',
                                    'type' => 'select',
                                    'value' => $row->tingkat_keparahan,
                                    'required' => true,
                                    'options' => collect(['rendah', 'sedang', 'tinggi', 'kritis'])->map(fn ($x): array => ['value' => $x, 'label' => ucfirst($x)])->toArray(),
                                ],
                                [
                                    'name' => 'perlu_tindak_lanjut',
                                    'label' => 'Perlu Tindak Lanjut',
                                    'type' => 'select',
                                    'value' => (bool) $row->perlu_tindak_lanjut ? '1' : '0',
                                    'required' => true,
                                    'options' => [
                                        ['value' => '1', 'label' => 'Ya'],
                                        ['value' => '0', 'label' => 'Tidak'],
                                    ],
                                ],
                            ],
                        ],
                        'delete' => [
                            'url' => route('penyuluh.kendala.kendala.destroy', $row->id),
                            'method' => 'DELETE',
                            'label' => 'Hapus data kendala ini?',
                        ],
                    ])),
                ];
            }
        );
    }

    private function penyuluhKebutuhan(Request $request): JsonResponse
    {
        $penyuluhId = $this->penyuluhId();
        if (! $penyuluhId) {
            return $this->empty($request);
        }

        $query = DB::table('kebutuhan_kunjungan')
            ->join('kunjungan_monitoring', 'kunjungan_monitoring.id', '=', 'kebutuhan_kunjungan.kunjungan_id')
            ->join('penugasan_penyuluh', 'penugasan_penyuluh.id', '=', 'kunjungan_monitoring.penugasan_id')
            ->join('kategori_kebutuhan', 'kategori_kebutuhan.id', '=', 'kebutuhan_kunjungan.kategori_kebutuhan_id')
            ->join('lahan', 'lahan.id', '=', 'penugasan_penyuluh.lahan_id')
            ->join('petani', 'petani.id', '=', 'lahan.petani_id')
            ->where('penugasan_penyuluh.penyuluh_id', $penyuluhId)
            ->select(
                'kebutuhan_kunjungan.id',
                'kebutuhan_kunjungan.kunjungan_id',
                'kebutuhan_kunjungan.kategori_kebutuhan_id',
                'kebutuhan_kunjungan.deskripsi_kebutuhan',
                'kebutuhan_kunjungan.jumlah',
                'kebutuhan_kunjungan.satuan',
                'kebutuhan_kunjungan.prioritas',
                'petani.nama_petani',
                'kategori_kebutuhan.nama_kategori'
            );

        $kategoriOptions = DB::table('kategori_kebutuhan')->orderBy('nama_kategori')->get(['id', 'nama_kategori'])
            ->map(fn ($x): array => ['value' => (string) $x->id, 'label' => $x->nama_kategori])
            ->toArray();
        $kunjunganOptions = $this->kunjunganOptionsForPenyuluh($penyuluhId);

        return $this->respond(
            $request,
            $query,
            ['petani.nama_petani', 'kategori_kebutuhan.nama_kategori', 'kebutuhan_kunjungan.prioritas', 'kebutuhan_kunjungan.jumlah'],
            ['petani.nama_petani', 'kategori_kebutuhan.nama_kategori', 'kebutuhan_kunjungan.deskripsi_kebutuhan'],
            'kebutuhan_kunjungan.id',
            function ($row) use ($kategoriOptions, $kunjunganOptions): array {
                return [
                    'nama_petani' => e($row->nama_petani),
                    'nama_kategori' => e($row->nama_kategori),
                    'jumlah' => trim((string) ($row->jumlah ?? '').' '.($row->satuan ?? '')),
                    'prioritas' => e((string) $row->prioritas),
                    'actions' => $this->actionsHtml($this->payload([
                        'title' => 'Detail Kebutuhan',
                        'fields' => [
                            ['label' => 'Petani', 'value' => $row->nama_petani],
                            ['label' => 'Kategori', 'value' => $row->nama_kategori],
                            ['label' => 'Deskripsi', 'value' => $row->deskripsi_kebutuhan],
                            ['label' => 'Jumlah', 'value' => $row->jumlah],
                            ['label' => 'Satuan', 'value' => $row->satuan],
                            ['label' => 'Prioritas', 'value' => $row->prioritas],
                        ],
                        'edit' => [
                            'url' => route('penyuluh.kendala.kebutuhan.update', $row->id),
                            'method' => 'PUT',
                            'fields' => [
                                ['name' => 'kunjungan_id', 'label' => 'Kunjungan', 'type' => 'select', 'value' => (string) $row->kunjungan_id, 'required' => true, 'options' => $kunjunganOptions],
                                ['name' => 'kategori_kebutuhan_id', 'label' => 'Kategori Kebutuhan', 'type' => 'select', 'value' => (string) $row->kategori_kebutuhan_id, 'required' => true, 'options' => $kategoriOptions],
                                ['name' => 'deskripsi_kebutuhan', 'label' => 'Deskripsi Kebutuhan', 'type' => 'textarea', 'value' => $row->deskripsi_kebutuhan, 'required' => true],
                                ['name' => 'jumlah', 'label' => 'Jumlah', 'type' => 'number', 'value' => $row->jumlah, 'required' => false, 'step' => '0.01'],
                                ['name' => 'satuan', 'label' => 'Satuan', 'type' => 'text', 'value' => $row->satuan, 'required' => false],
                                [
                                    'name' => 'prioritas',
                                    'label' => 'Prioritas',
                                    'type' => 'select',
                                    'value' => $row->prioritas,
                                    'required' => true,
                                    'options' => collect(['rendah', 'sedang', 'tinggi'])->map(fn ($x): array => ['value' => $x, 'label' => ucfirst($x)])->toArray(),
                                ],
                            ],
                        ],
                        'delete' => [
                            'url' => route('penyuluh.kendala.kebutuhan.destroy', $row->id),
                            'method' => 'DELETE',
                            'label' => 'Hapus data kebutuhan ini?',
                        ],
                    ])),
                ];
            }
        );
    }

    private function kecamatanVerifikasi(Request $request): JsonResponse
    {
        $kecamatanIds = $this->managedKecamatanIds();

        $query = DB::table('kunjungan_monitoring')
            ->join('penugasan_penyuluh', 'penugasan_penyuluh.id', '=', 'kunjungan_monitoring.penugasan_id')
            ->join('lahan', 'lahan.id', '=', 'penugasan_penyuluh.lahan_id')
            ->join('petani', 'petani.id', '=', 'lahan.petani_id')
            ->join('desa', 'desa.id', '=', 'lahan.desa_id')
            ->join('kecamatan', 'kecamatan.id', '=', 'desa.kecamatan_id')
            ->join('penyuluh', 'penyuluh.id', '=', 'penugasan_penyuluh.penyuluh_id')
            ->join('users', 'users.id', '=', 'penyuluh.user_id')
            ->leftJoin('verifikasi_log as last_verifikasi', function ($join): void {
                $join->on('last_verifikasi.kunjungan_id', '=', 'kunjungan_monitoring.id')
                    ->whereRaw('last_verifikasi.id = (SELECT MAX(v2.id) FROM verifikasi_log v2 WHERE v2.kunjungan_id = kunjungan_monitoring.id)');
            })
            ->leftJoin('lampiran_media as foto_kunjungan', function ($join): void {
                $join->on('foto_kunjungan.kunjungan_id', '=', 'kunjungan_monitoring.id')
                    ->where('foto_kunjungan.file_type', '=', 'image')
                    ->whereRaw("foto_kunjungan.id = (SELECT MAX(fu.id) FROM lampiran_media fu WHERE fu.kunjungan_id = kunjungan_monitoring.id AND fu.file_url LIKE '%/kunjungan/utama/%')");
            })
            ->leftJoin('lampiran_media as foto_kendala', function ($join): void {
                $join->on('foto_kendala.kunjungan_id', '=', 'kunjungan_monitoring.id')
                    ->where('foto_kendala.file_type', '=', 'image')
                    ->whereRaw("foto_kendala.id = (SELECT MAX(fk.id) FROM lampiran_media fk WHERE fk.kunjungan_id = kunjungan_monitoring.id AND fk.file_url LIKE '%/kunjungan/kendala/%')");
            })
            ->whereIn('kecamatan.id', $kecamatanIds)
            ->select(
                'kunjungan_monitoring.id',
                'kunjungan_monitoring.tanggal_kunjungan',
                'kunjungan_monitoring.kondisi_tanaman',
                'kunjungan_monitoring.catatan_umum',
                'kunjungan_monitoring.rekomendasi',
                'kunjungan_monitoring.latitude',
                'kunjungan_monitoring.longitude',
                'kunjungan_monitoring.status_verifikasi',
                'users.name as nama_penyuluh',
                'petani.nama_petani',
                'kecamatan.nama_kecamatan',
                'foto_kunjungan.file_url as foto_kunjungan_url',
                'foto_kendala.file_url as foto_kendala_url',
                'last_verifikasi.catatan_verifikasi as catatan_verifikasi_terakhir',
                'last_verifikasi.diverifikasi_at as diverifikasi_terakhir',
                DB::raw("(SELECT GROUP_CONCAT(CONCAT(komoditas.nama_komoditas, ' | Tgl Panen: ', DATE_FORMAT(produksi_panen.tanggal_panen, '%Y-%m-%d'), ' | Produksi: ', produksi_panen.jumlah_produksi, IFNULL(CONCAT(' | Harga: ', produksi_panen.harga_jual), ''), IFNULL(CONCAT(' | Catatan: ', produksi_panen.catatan), '')) SEPARATOR ' || ')
                    FROM produksi_panen
                    JOIN lahan_komoditas lk2 ON lk2.id = produksi_panen.lahan_komoditas_id
                    JOIN komoditas ON komoditas.id = lk2.komoditas_id
                    WHERE produksi_panen.kunjungan_id = kunjungan_monitoring.id) as detail_produksi"),
                DB::raw("(SELECT GROUP_CONCAT(CONCAT(kategori_kendala.nama_kategori, ' | Keparahan: ', kendala_kunjungan.tingkat_keparahan, ' | Tindak Lanjut: ', IF(kendala_kunjungan.perlu_tindak_lanjut = 1, 'Ya', 'Tidak'), ' | Deskripsi: ', kendala_kunjungan.deskripsi_kendala) SEPARATOR ' || ')
                    FROM kendala_kunjungan
                    JOIN kategori_kendala ON kategori_kendala.id = kendala_kunjungan.kategori_kendala_id
                    WHERE kendala_kunjungan.kunjungan_id = kunjungan_monitoring.id) as detail_kendala"),
                DB::raw("(SELECT GROUP_CONCAT(CONCAT(kategori_kebutuhan.nama_kategori, ' | Prioritas: ', kebutuhan_kunjungan.prioritas, IFNULL(CONCAT(' | Jumlah: ', kebutuhan_kunjungan.jumlah, ' ', kebutuhan_kunjungan.satuan), ''), ' | Deskripsi: ', kebutuhan_kunjungan.deskripsi_kebutuhan) SEPARATOR ' || ')
                    FROM kebutuhan_kunjungan
                    JOIN kategori_kebutuhan ON kategori_kebutuhan.id = kebutuhan_kunjungan.kategori_kebutuhan_id
                    WHERE kebutuhan_kunjungan.kunjungan_id = kunjungan_monitoring.id) as detail_kebutuhan")
            );

        return $this->respond(
            $request,
            $query,
            ['kunjungan_monitoring.tanggal_kunjungan', 'kecamatan.nama_kecamatan', 'users.name', 'petani.nama_petani', 'kunjungan_monitoring.status_verifikasi'],
            ['users.name', 'petani.nama_petani', 'kecamatan.nama_kecamatan', 'kunjungan_monitoring.status_verifikasi'],
            'kunjungan_monitoring.tanggal_kunjungan',
            function ($row): array {
                $statusValue = in_array((string) $row->status_verifikasi, ['revisi', 'disetujui'], true)
                    ? (string) $row->status_verifikasi
                    : 'disetujui';

                return [
                    'tanggal_kunjungan' => (string) $row->tanggal_kunjungan,
                    'nama_kecamatan' => e($row->nama_kecamatan),
                    'nama_penyuluh' => e($row->nama_penyuluh),
                    'nama_petani' => e($row->nama_petani),
                    'status_verifikasi' => e((string) $row->status_verifikasi),
                    'actions' => $this->actionsHtml($this->payload([
                        'title' => 'Verifikasi Kunjungan',
                        'fields' => [
                            ['label' => 'Tanggal', 'value' => $row->tanggal_kunjungan],
                            ['label' => 'Kecamatan', 'value' => $row->nama_kecamatan],
                            ['label' => 'Penyuluh', 'value' => $row->nama_penyuluh],
                            ['label' => 'Petani', 'value' => $row->nama_petani],
                            ['label' => 'Kondisi Tanaman', 'value' => $row->kondisi_tanaman],
                            ['label' => 'Catatan Umum', 'value' => $row->catatan_umum],
                            ['label' => 'Rekomendasi', 'value' => $row->rekomendasi],
                            ['label' => 'Detail Produksi', 'value' => str_replace(' || ', ' ; ', (string) ($row->detail_produksi ?: '-'))],
                            ['label' => 'Detail Kendala', 'value' => str_replace(' || ', ' ; ', (string) ($row->detail_kendala ?: '-'))],
                            ['label' => 'Detail Kebutuhan', 'value' => str_replace(' || ', ' ; ', (string) ($row->detail_kebutuhan ?: '-'))],
                            ['label' => 'Status Verifikasi Saat Ini', 'value' => $row->status_verifikasi],
                            ['label' => 'Catatan Verifikasi Terakhir', 'value' => $row->catatan_verifikasi_terakhir ?: '-'],
                            ['label' => 'Waktu Verifikasi Terakhir', 'value' => $row->diverifikasi_terakhir ?: '-'],
                            ['label' => 'Foto Kendala', 'value' => $row->foto_kendala_url ?: '-'],
                        ],
                        'image_url' => $row->foto_kunjungan_url ?: $row->foto_kendala_url,
                        'spatial' => [
                            'lat' => $row->latitude,
                            'lng' => $row->longitude,
                            'label' => 'Kunjungan '.$row->nama_petani,
                        ],
                        'edit' => [
                            'url' => route('kecamatan.verifikasi.update', $row->id),
                            'method' => 'POST',
                            'fields' => [
                                [
                                    'name' => 'status_verifikasi',
                                    'label' => 'Respon Laporan',
                                    'type' => 'select',
                                    'value' => $statusValue,
                                    'required' => true,
                                    'options' => [
                                        ['value' => 'disetujui', 'label' => 'Diterima'],
                                        ['value' => 'revisi', 'label' => 'Revisi'],
                                    ],
                                ],
                                ['name' => 'catatan_verifikasi', 'label' => 'Keterangan Revisi / Catatan', 'type' => 'textarea', 'value' => $row->catatan_verifikasi_terakhir, 'required' => false],
                            ],
                        ],
                        'response_form' => [
                            'url' => route('kecamatan.verifikasi.update', $row->id),
                            'method' => 'POST',
                            'fields' => [
                                [
                                    'name' => 'status_verifikasi',
                                    'label' => 'Respon Laporan',
                                    'type' => 'select',
                                    'value' => $statusValue,
                                    'required' => true,
                                    'options' => [
                                        ['value' => 'disetujui', 'label' => 'Diterima'],
                                        ['value' => 'revisi', 'label' => 'Revisi'],
                                    ],
                                ],
                                ['name' => 'catatan_verifikasi', 'label' => 'Keterangan Revisi / Catatan', 'type' => 'textarea', 'value' => $row->catatan_verifikasi_terakhir, 'required' => false],
                            ],
                        ],
                    ])),
                ];
            }
        );
    }

    private function kecamatanUsulanPerubahan(Request $request): JsonResponse
    {
        $kecamatanIds = $this->managedKecamatanIds();

        $query = DB::table('usulan_perubahan_data')
            ->join('penugasan_penyuluh', 'penugasan_penyuluh.id', '=', 'usulan_perubahan_data.penugasan_id')
            ->join('lahan', 'lahan.id', '=', 'penugasan_penyuluh.lahan_id')
            ->join('petani', 'petani.id', '=', 'lahan.petani_id')
            ->join('desa', 'desa.id', '=', 'lahan.desa_id')
            ->join('users as pengaju', 'pengaju.id', '=', 'usulan_perubahan_data.diajukan_oleh_user_id')
            ->leftJoin('lahan_komoditas as lk', function ($join): void {
                $join->on('lk.id', '=', 'usulan_perubahan_data.target_id')
                    ->where('usulan_perubahan_data.target_tipe', '=', 'lahan_komoditas');
            })
            ->leftJoin('komoditas', 'komoditas.id', '=', 'lk.komoditas_id')
            ->whereIn('desa.kecamatan_id', $kecamatanIds)
            ->select(
                'usulan_perubahan_data.id',
                'usulan_perubahan_data.created_at',
                'usulan_perubahan_data.waktu_pengajuan',
                'usulan_perubahan_data.target_tipe',
                'usulan_perubahan_data.field_name',
                'usulan_perubahan_data.nilai_lama',
                'usulan_perubahan_data.nilai_usulan',
                'usulan_perubahan_data.alasan',
                'usulan_perubahan_data.status',
                'usulan_perubahan_data.catatan_admin',
                'petani.nama_petani',
                'pengaju.name as nama_pengaju',
                'komoditas.nama_komoditas'
            );

        return $this->respond(
            $request,
            $query,
            ['usulan_perubahan_data.created_at', 'petani.nama_petani', 'usulan_perubahan_data.target_tipe', 'usulan_perubahan_data.field_name', 'usulan_perubahan_data.status'],
            ['petani.nama_petani', 'pengaju.name', 'usulan_perubahan_data.target_tipe', 'usulan_perubahan_data.field_name', 'usulan_perubahan_data.status', 'usulan_perubahan_data.alasan'],
            'usulan_perubahan_data.created_at',
            function ($row): array {
                $targetLabel = ucfirst(str_replace('_', ' ', (string) $row->target_tipe));
                if ($row->nama_komoditas) {
                    $targetLabel .= ' - '.$row->nama_komoditas;
                }

                $statusLabel = ucfirst((string) $row->status);

                return [
                    'waktu_usulan' => (string) ($row->waktu_pengajuan ?: $row->created_at),
                    'nama_petani' => e($row->nama_petani),
                    'nama_pengaju' => e($row->nama_pengaju),
                    'target' => e($targetLabel),
                    'field_name' => e((string) $row->field_name),
                    'nilai_usulan' => e((string) $row->nilai_usulan),
                    'status' => e($statusLabel),
                    'actions' => $this->actionsHtml($this->payload([
                        'title' => 'Usulan Perubahan Data',
                        'fields' => [
                            ['label' => 'Waktu Usulan', 'value' => $row->waktu_pengajuan ?: $row->created_at],
                            ['label' => 'Petani', 'value' => $row->nama_petani],
                            ['label' => 'Penyuluh', 'value' => $row->nama_pengaju],
                            ['label' => 'Target', 'value' => $targetLabel],
                            ['label' => 'Field', 'value' => $row->field_name],
                            ['label' => 'Nilai Lama', 'value' => $row->nilai_lama],
                            ['label' => 'Nilai Usulan', 'value' => $row->nilai_usulan],
                            ['label' => 'Alasan Penyuluh', 'value' => $row->alasan],
                            ['label' => 'Status', 'value' => $statusLabel],
                            ['label' => 'Catatan Admin', 'value' => $row->catatan_admin],
                        ],
                        'edit' => [
                            'url' => route('kecamatan.verifikasi.usulan.update', $row->id),
                            'method' => 'POST',
                            'fields' => [
                                [
                                    'name' => 'status',
                                    'label' => 'Status Usulan',
                                    'type' => 'select',
                                    'value' => $row->status,
                                    'required' => true,
                                    'options' => collect(['disetujui', 'ditolak'])->map(fn ($x): array => ['value' => $x, 'label' => ucfirst($x)])->toArray(),
                                ],
                                ['name' => 'catatan_admin', 'label' => 'Catatan Admin Kecamatan', 'type' => 'textarea', 'value' => $row->catatan_admin, 'required' => false],
                            ],
                        ],
                    ])),
                ];
            }
        );
    }

    private function adminMonitoring(Request $request): JsonResponse
    {
        $query = DB::table('kunjungan_monitoring')
            ->join('penugasan_penyuluh', 'penugasan_penyuluh.id', '=', 'kunjungan_monitoring.penugasan_id')
            ->join('penyuluh', 'penyuluh.id', '=', 'penugasan_penyuluh.penyuluh_id')
            ->join('users', 'users.id', '=', 'penyuluh.user_id')
            ->join('lahan', 'lahan.id', '=', 'penugasan_penyuluh.lahan_id')
            ->join('petani', 'petani.id', '=', 'lahan.petani_id')
            ->leftJoin('lampiran_media as foto_kunjungan', function ($join): void {
                $join->on('foto_kunjungan.kunjungan_id', '=', 'kunjungan_monitoring.id')
                    ->where('foto_kunjungan.file_type', '=', 'image')
                    ->whereRaw("foto_kunjungan.id = (SELECT MAX(fu.id) FROM lampiran_media fu WHERE fu.kunjungan_id = kunjungan_monitoring.id AND fu.file_url LIKE '%/kunjungan/utama/%')");
            })
            ->leftJoin('lampiran_media as foto_kendala', function ($join): void {
                $join->on('foto_kendala.kunjungan_id', '=', 'kunjungan_monitoring.id')
                    ->where('foto_kendala.file_type', '=', 'image')
                    ->whereRaw("foto_kendala.id = (SELECT MAX(fk.id) FROM lampiran_media fk WHERE fk.kunjungan_id = kunjungan_monitoring.id AND fk.file_url LIKE '%/kunjungan/kendala/%')");
            })
            ->leftJoin('verifikasi_log', function ($join): void {
                $join->on('verifikasi_log.kunjungan_id', '=', 'kunjungan_monitoring.id')
                    ->whereRaw('verifikasi_log.id = (SELECT MAX(v2.id) FROM verifikasi_log v2 WHERE v2.kunjungan_id = kunjungan_monitoring.id)');
            })
            ->select(
                'kunjungan_monitoring.id',
                'kunjungan_monitoring.tanggal_kunjungan',
                'kunjungan_monitoring.status_verifikasi',
                'kunjungan_monitoring.kondisi_tanaman',
                'kunjungan_monitoring.catatan_umum',
                'kunjungan_monitoring.rekomendasi',
                'kunjungan_monitoring.latitude',
                'kunjungan_monitoring.longitude',
                'users.name as nama_penyuluh',
                'petani.nama_petani',
                'verifikasi_log.catatan_verifikasi',
                'foto_kunjungan.file_url as foto_kunjungan_url',
                'foto_kendala.file_url as foto_kendala_url'
            );

        return $this->respond(
            $request,
            $query,
            ['kunjungan_monitoring.tanggal_kunjungan', 'users.name', 'petani.nama_petani', 'kunjungan_monitoring.status_verifikasi'],
            ['users.name', 'petani.nama_petani', 'kunjungan_monitoring.status_verifikasi', 'kunjungan_monitoring.catatan_umum'],
            'kunjungan_monitoring.tanggal_kunjungan',
            function ($row): array {
                return [
                    'tanggal_kunjungan' => (string) $row->tanggal_kunjungan,
                    'nama_penyuluh' => e($row->nama_penyuluh),
                    'nama_petani' => e($row->nama_petani),
                    'status_verifikasi' => e((string) $row->status_verifikasi),
                    'actions' => $this->actionsHtml($this->payload([
                        'title' => 'Monitoring Kunjungan',
                        'fields' => [
                            ['label' => 'Tanggal', 'value' => $row->tanggal_kunjungan],
                            ['label' => 'Penyuluh', 'value' => $row->nama_penyuluh],
                            ['label' => 'Petani', 'value' => $row->nama_petani],
                            ['label' => 'Status', 'value' => $row->status_verifikasi],
                            ['label' => 'Kondisi Tanaman', 'value' => $row->kondisi_tanaman],
                            ['label' => 'Catatan Umum', 'value' => $row->catatan_umum],
                            ['label' => 'Rekomendasi', 'value' => $row->rekomendasi],
                            ['label' => 'Catatan Verifikasi', 'value' => $row->catatan_verifikasi],
                            ['label' => 'Foto Kendala', 'value' => $row->foto_kendala_url ?: '-'],
                        ],
                        'image_url' => $row->foto_kunjungan_url ?: $row->foto_kendala_url,
                        'spatial' => [
                            'lat' => $row->latitude,
                            'lng' => $row->longitude,
                            'label' => 'Kunjungan '.$row->nama_petani,
                        ],
                        'edit' => [
                            'url' => route('admin.monitoring.verify', $row->id),
                            'method' => 'POST',
                            'fields' => [
                                [
                                    'name' => 'status_verifikasi',
                                    'label' => 'Status Verifikasi',
                                    'type' => 'select',
                                    'value' => $row->status_verifikasi,
                                    'required' => true,
                                    'options' => collect(['menunggu', 'revisi', 'ditolak', 'disetujui'])->map(fn ($x): array => ['value' => $x, 'label' => ucfirst($x)])->toArray(),
                                ],
                                ['name' => 'catatan_verifikasi', 'label' => 'Catatan Verifikasi', 'type' => 'textarea', 'value' => $row->catatan_verifikasi, 'required' => false],
                            ],
                        ],
                    ])),
                ];
            }
        );
    }

    private function kecamatanRekapSummary(Request $request): JsonResponse
    {
        $kecamatanIds = $this->managedKecamatanIds();
        $query = DB::table('kecamatan')
            ->leftJoin('desa', 'desa.kecamatan_id', '=', 'kecamatan.id')
            ->leftJoin('petani', 'petani.desa_id', '=', 'desa.id')
            ->leftJoin('lahan', 'lahan.desa_id', '=', 'desa.id')
            ->whereIn('kecamatan.id', $kecamatanIds)
            ->groupBy('kecamatan.id', 'kecamatan.nama_kecamatan')
            ->selectRaw('kecamatan.id')
            ->selectRaw('kecamatan.nama_kecamatan')
            ->selectRaw('COUNT(DISTINCT petani.id) as total_petani')
            ->selectRaw('COUNT(DISTINCT lahan.id) as total_lahan')
            ->selectRaw('COALESCE(SUM(lahan.luas_ha),0) as total_luas');

        return $this->respond(
            $request,
            $query,
            ['kecamatan.nama_kecamatan', 'total_petani', 'total_lahan', 'total_luas'],
            ['kecamatan.nama_kecamatan'],
            'kecamatan.nama_kecamatan',
            function ($row): array {
                return [
                    'nama_kecamatan' => e($row->nama_kecamatan),
                    'total_petani' => (string) $row->total_petani,
                    'total_lahan' => (string) $row->total_lahan,
                    'total_luas' => number_format((float) $row->total_luas, 2, '.', ''),
                    'actions' => $this->actionsHtml($this->payload([
                        'title' => 'Ringkasan Kecamatan',
                        'fields' => [
                            ['label' => 'Kecamatan', 'value' => $row->nama_kecamatan],
                            ['label' => 'Total Petani', 'value' => $row->total_petani],
                            ['label' => 'Total Lahan', 'value' => $row->total_lahan],
                            ['label' => 'Total Luas', 'value' => $row->total_luas],
                        ],
                    ])),
                ];
            }
        );
    }

    private function kecamatanRekapKomoditas(Request $request): JsonResponse
    {
        $kecamatanIds = $this->managedKecamatanIds();
        $query = DB::table('lahan_komoditas')
            ->join('lahan', 'lahan.id', '=', 'lahan_komoditas.lahan_id')
            ->join('desa', 'desa.id', '=', 'lahan.desa_id')
            ->join('komoditas', 'komoditas.id', '=', 'lahan_komoditas.komoditas_id')
            ->whereIn('desa.kecamatan_id', $kecamatanIds)
            ->groupBy('komoditas.id', 'komoditas.nama_komoditas')
            ->selectRaw('komoditas.id')
            ->selectRaw('komoditas.nama_komoditas')
            ->selectRaw('COUNT(*) as total_lahan_komoditas')
            ->selectRaw('COALESCE(SUM(lahan_komoditas.luas_tanam_ha),0) as total_luas_tanam');

        return $this->respond(
            $request,
            $query,
            ['komoditas.nama_komoditas', 'total_lahan_komoditas', 'total_luas_tanam'],
            ['komoditas.nama_komoditas'],
            'total_lahan_komoditas',
            function ($row): array {
                return [
                    'nama_komoditas' => e($row->nama_komoditas),
                    'total_lahan_komoditas' => (string) $row->total_lahan_komoditas,
                    'total_luas_tanam' => number_format((float) $row->total_luas_tanam, 2, '.', ''),
                    'actions' => $this->actionsHtml($this->payload([
                        'title' => 'Ringkasan Komoditas',
                        'fields' => [
                            ['label' => 'Komoditas', 'value' => $row->nama_komoditas],
                            ['label' => 'Total Lahan Komoditas', 'value' => $row->total_lahan_komoditas],
                            ['label' => 'Total Luas Tanam', 'value' => $row->total_luas_tanam],
                        ],
                    ])),
                ];
            }
        );
    }

    private function adminLaporanPeriode(Request $request): JsonResponse
    {
        $query = DB::table('periode_laporan')
            ->select('id', 'bulan', 'tahun', 'tanggal_mulai', 'tanggal_selesai', 'status_periode');

        return $this->respond(
            $request,
            $query,
            ['tahun', 'tanggal_mulai', 'tanggal_selesai', 'status_periode'],
            ['tahun', 'bulan', 'status_periode'],
            'tahun',
            function ($row): array {
                return [
                    'periode' => sprintf('%02d/%d', (int) $row->bulan, (int) $row->tahun),
                    'tanggal_mulai' => (string) $row->tanggal_mulai,
                    'tanggal_selesai' => (string) $row->tanggal_selesai,
                    'status_periode' => e($row->status_periode),
                    'actions' => $this->actionsHtml($this->payload([
                        'title' => 'Periode Laporan',
                        'fields' => [
                            ['label' => 'Periode', 'value' => sprintf('%02d/%d', (int) $row->bulan, (int) $row->tahun)],
                            ['label' => 'Tanggal Mulai', 'value' => $row->tanggal_mulai],
                            ['label' => 'Tanggal Selesai', 'value' => $row->tanggal_selesai],
                            ['label' => 'Status', 'value' => $row->status_periode],
                        ],
                        'edit' => [
                            'url' => route('admin.laporan.periode.update', $row->id),
                            'method' => 'PATCH',
                            'fields' => [
                                [
                                    'name' => 'status_periode',
                                    'label' => 'Status Periode',
                                    'type' => 'select',
                                    'value' => $row->status_periode,
                                    'required' => true,
                                    'options' => collect(['terbuka', 'ditutup', 'arsip'])->map(fn ($x): array => ['value' => $x, 'label' => ucfirst($x)])->toArray(),
                                ],
                            ],
                        ],
                    ])),
                ];
            }
        );
    }

    private function adminLaporanList(Request $request): JsonResponse
    {
        $query = DB::table('laporan_pimpinan')
            ->join('periode_laporan', 'periode_laporan.id', '=', 'laporan_pimpinan.periode_id')
            ->join('users', 'users.id', '=', 'laporan_pimpinan.generated_by_user_id')
            ->select(
                'laporan_pimpinan.id',
                'laporan_pimpinan.jenis_laporan',
                'laporan_pimpinan.file_url',
                'laporan_pimpinan.generated_at',
                'periode_laporan.bulan',
                'periode_laporan.tahun',
                'users.name as generated_by'
            );

        return $this->respond(
            $request,
            $query,
            ['laporan_pimpinan.generated_at', 'jenis_laporan', 'periode_laporan.tahun', 'users.name', 'laporan_pimpinan.file_url'],
            ['laporan_pimpinan.jenis_laporan', 'users.name'],
            'laporan_pimpinan.generated_at',
            function ($row): array {
                return [
                    'generated_at' => (string) $row->generated_at,
                    'jenis_laporan' => e($row->jenis_laporan),
                    'periode' => sprintf('%02d/%d', (int) $row->bulan, (int) $row->tahun),
                    'generated_by' => e($row->generated_by),
                    'file_url' => e($row->file_url),
                    'actions' => $this->actionsHtml($this->payload([
                        'title' => 'Laporan Pimpinan',
                        'fields' => [
                            ['label' => 'Jenis Laporan', 'value' => $row->jenis_laporan],
                            ['label' => 'Periode', 'value' => sprintf('%02d/%d', (int) $row->bulan, (int) $row->tahun)],
                            ['label' => 'Generated By', 'value' => $row->generated_by],
                            ['label' => 'Generated At', 'value' => $row->generated_at],
                            ['label' => 'File URL', 'value' => $row->file_url],
                        ],
                    ])),
                ];
            }
        );
    }

    private function adminLaporanDetail(Request $request): JsonResponse
    {
        $query = DB::table('laporan_pimpinan_kecamatan')
            ->join('laporan_pimpinan', 'laporan_pimpinan.id', '=', 'laporan_pimpinan_kecamatan.laporan_id')
            ->join('kecamatan', 'kecamatan.id', '=', 'laporan_pimpinan_kecamatan.kecamatan_id')
            ->join('periode_laporan', 'periode_laporan.id', '=', 'laporan_pimpinan.periode_id')
            ->select(
                'laporan_pimpinan_kecamatan.id',
                'kecamatan.nama_kecamatan',
                'periode_laporan.bulan',
                'periode_laporan.tahun',
                'laporan_pimpinan_kecamatan.total_produksi',
                'laporan_pimpinan_kecamatan.total_luas',
                'laporan_pimpinan_kecamatan.total_petani',
                'laporan_pimpinan_kecamatan.total_lahan'
            );

        return $this->respond(
            $request,
            $query,
            ['periode_laporan.tahun', 'kecamatan.nama_kecamatan', 'laporan_pimpinan_kecamatan.total_produksi', 'laporan_pimpinan_kecamatan.total_luas', 'laporan_pimpinan_kecamatan.total_petani', 'laporan_pimpinan_kecamatan.total_lahan'],
            ['kecamatan.nama_kecamatan'],
            'laporan_pimpinan_kecamatan.id',
            function ($row): array {
                return [
                    'periode' => sprintf('%02d/%d', (int) $row->bulan, (int) $row->tahun),
                    'nama_kecamatan' => e($row->nama_kecamatan),
                    'total_produksi' => (string) $row->total_produksi,
                    'total_luas' => (string) $row->total_luas,
                    'total_petani' => (string) $row->total_petani,
                    'total_lahan' => (string) $row->total_lahan,
                    'actions' => $this->actionsHtml($this->payload([
                        'title' => 'Detail Laporan Kecamatan',
                        'fields' => [
                            ['label' => 'Periode', 'value' => sprintf('%02d/%d', (int) $row->bulan, (int) $row->tahun)],
                            ['label' => 'Kecamatan', 'value' => $row->nama_kecamatan],
                            ['label' => 'Total Produksi', 'value' => $row->total_produksi],
                            ['label' => 'Total Luas', 'value' => $row->total_luas],
                            ['label' => 'Total Petani', 'value' => $row->total_petani],
                            ['label' => 'Total Lahan', 'value' => $row->total_lahan],
                        ],
                    ])),
                ];
            }
        );
    }

    private function adminMapStyleKomoditas(Request $request): JsonResponse
    {
        MapStyleSupport::ensureDefaultRows();

        $query = DB::table('komoditas')
            ->leftJoin('map_marker_styles', function ($join): void {
                $join->on('map_marker_styles.komoditas_id', '=', 'komoditas.id')
                    ->where('map_marker_styles.scope', '=', 'komoditas');
            })
            ->select(
                'komoditas.id',
                'komoditas.nama_komoditas',
                DB::raw("COALESCE(map_marker_styles.icon_symbol, 'C') as icon_symbol"),
                DB::raw("COALESCE(map_marker_styles.icon_color, '#9a3412') as icon_color"),
                DB::raw("COALESCE(map_marker_styles.bg_color, '#ffedd5') as bg_color"),
                DB::raw('COALESCE(map_marker_styles.size, 28) as size')
            );

        return $this->respond(
            $request,
            $query,
            ['komoditas.nama_komoditas', 'icon_symbol', 'icon_color', 'bg_color', 'size'],
            ['komoditas.nama_komoditas'],
            'komoditas.nama_komoditas',
            function ($row): array {
                return [
                    'nama_komoditas' => e($row->nama_komoditas),
                    'icon_symbol' => e($row->icon_symbol),
                    'icon_color' => e($row->icon_color),
                    'bg_color' => e($row->bg_color),
                    'size' => (string) $row->size,
                    'actions' => $this->actionsHtml($this->payload([
                        'title' => 'Style Komoditas '.$row->nama_komoditas,
                        'fields' => [
                            ['label' => 'Komoditas', 'value' => $row->nama_komoditas],
                            ['label' => 'Simbol', 'value' => $row->icon_symbol],
                            ['label' => 'Warna Ikon', 'value' => $row->icon_color],
                            ['label' => 'Warna Latar', 'value' => $row->bg_color],
                            ['label' => 'Ukuran', 'value' => $row->size],
                        ],
                        'edit' => [
                            'url' => route('admin.map_style.komoditas.upsert', $row->id),
                            'method' => 'POST',
                            'fields' => [
                                ['name' => 'label', 'label' => 'Label', 'type' => 'text', 'value' => 'Komoditas '.$row->nama_komoditas, 'required' => true],
                                ['name' => 'icon_symbol', 'label' => 'Simbol', 'type' => 'text', 'value' => $row->icon_symbol, 'required' => true],
                                ['name' => 'icon_color', 'label' => 'Warna Ikon', 'type' => 'text', 'value' => $row->icon_color, 'required' => true],
                                ['name' => 'bg_color', 'label' => 'Warna Latar', 'type' => 'text', 'value' => $row->bg_color, 'required' => true],
                                ['name' => 'size', 'label' => 'Ukuran', 'type' => 'number', 'value' => $row->size, 'required' => true],
                            ],
                        ],
                    ])),
                ];
            }
        );
    }

    private function penyuluhPetaniBinaan(Request $request): JsonResponse
    {
        $penyuluhId = $this->penyuluhId();
        if (! $penyuluhId) {
            return $this->empty($request);
        }

        $query = DB::table('penugasan_penyuluh')
            ->join('lahan', 'lahan.id', '=', 'penugasan_penyuluh.lahan_id')
            ->join('petani', 'petani.id', '=', 'lahan.petani_id')
            ->join('desa', 'desa.id', '=', 'lahan.desa_id')
            ->leftJoin('lahan_komoditas', 'lahan_komoditas.lahan_id', '=', 'lahan.id')
            ->leftJoin('komoditas', 'komoditas.id', '=', 'lahan_komoditas.komoditas_id')
            ->where('penugasan_penyuluh.penyuluh_id', $penyuluhId)
            ->where('penugasan_penyuluh.status_penugasan', 'aktif')
            ->groupBy(
                'penugasan_penyuluh.id',
                'petani.nama_petani',
                'petani.no_hp',
                'desa.nama_desa',
                'lahan.luas_ha',
                'lahan.kondisi_lahan'
            )
            ->select(
                'penugasan_penyuluh.id as penugasan_id',
                'petani.nama_petani',
                'petani.no_hp',
                'desa.nama_desa',
                'lahan.luas_ha',
                'lahan.kondisi_lahan',
                DB::raw('GROUP_CONCAT(DISTINCT komoditas.nama_komoditas SEPARATOR ", ") as komoditas')
            );

        return $this->respond(
            $request,
            $query,
            ['petani.nama_petani', 'desa.nama_desa', 'petani.no_hp', 'lahan.luas_ha', 'lahan.kondisi_lahan', 'komoditas'],
            ['petani.nama_petani', 'desa.nama_desa', 'petani.no_hp', 'komoditas'],
            'petani.nama_petani',
            function ($row): array {
                return [
                    'nama_petani' => e($row->nama_petani),
                    'nama_desa' => e($row->nama_desa),
                    'no_hp' => e((string) $row->no_hp),
                    'luas_ha' => (string) $row->luas_ha,
                    'kondisi_lahan' => e((string) $row->kondisi_lahan),
                    'komoditas' => e((string) $row->komoditas),
                    'actions' => $this->actionsHtml($this->payload([
                        'title' => 'Petani Binaan',
                        'fields' => [
                            ['label' => 'Nama Petani', 'value' => $row->nama_petani],
                            ['label' => 'Desa', 'value' => $row->nama_desa],
                            ['label' => 'No HP', 'value' => $row->no_hp],
                            ['label' => 'Luas Lahan', 'value' => $row->luas_ha],
                            ['label' => 'Kondisi Lahan', 'value' => $row->kondisi_lahan],
                            ['label' => 'Komoditas', 'value' => $row->komoditas],
                        ],
                    ])),
                ];
            }
        );
    }

    private function penyuluhRiwayat(Request $request): JsonResponse
    {
        $penyuluhId = $this->penyuluhId();
        if (! $penyuluhId) {
            return $this->empty($request);
        }

        $query = DB::table('kunjungan_monitoring')
            ->join('penugasan_penyuluh', 'penugasan_penyuluh.id', '=', 'kunjungan_monitoring.penugasan_id')
            ->join('lahan', 'lahan.id', '=', 'penugasan_penyuluh.lahan_id')
            ->join('petani', 'petani.id', '=', 'lahan.petani_id')
            ->leftJoin('verifikasi_log', function ($join): void {
                $join->on('verifikasi_log.kunjungan_id', '=', 'kunjungan_monitoring.id')
                    ->whereRaw('verifikasi_log.id = (SELECT MAX(v2.id) FROM verifikasi_log v2 WHERE v2.kunjungan_id = kunjungan_monitoring.id)');
            })
            ->where('penugasan_penyuluh.penyuluh_id', $penyuluhId)
            ->select(
                'kunjungan_monitoring.id',
                'kunjungan_monitoring.tanggal_kunjungan',
                'kunjungan_monitoring.status_verifikasi',
                'petani.nama_petani',
                'verifikasi_log.catatan_verifikasi',
                'verifikasi_log.diverifikasi_at'
            );

        return $this->respond(
            $request,
            $query,
            ['kunjungan_monitoring.tanggal_kunjungan', 'petani.nama_petani', 'kunjungan_monitoring.status_verifikasi', 'verifikasi_log.catatan_verifikasi', 'verifikasi_log.diverifikasi_at'],
            ['petani.nama_petani', 'kunjungan_monitoring.status_verifikasi', 'verifikasi_log.catatan_verifikasi'],
            'kunjungan_monitoring.tanggal_kunjungan',
            function ($row): array {
                return [
                    'tanggal_kunjungan' => (string) $row->tanggal_kunjungan,
                    'nama_petani' => e($row->nama_petani),
                    'status_verifikasi' => e($row->status_verifikasi),
                    'catatan_verifikasi' => e((string) $row->catatan_verifikasi),
                    'diverifikasi_at' => (string) $row->diverifikasi_at,
                    'actions' => $this->actionsHtml($this->payload([
                        'title' => 'Riwayat Kunjungan',
                        'fields' => [
                            ['label' => 'Tanggal', 'value' => $row->tanggal_kunjungan],
                            ['label' => 'Petani', 'value' => $row->nama_petani],
                            ['label' => 'Status', 'value' => $row->status_verifikasi],
                            ['label' => 'Catatan Verifikasi', 'value' => $row->catatan_verifikasi],
                            ['label' => 'Waktu Verifikasi', 'value' => $row->diverifikasi_at],
                        ],
                    ])),
                ];
            }
        );
    }

    private function pimpinanLaporanList(Request $request): JsonResponse
    {
        $query = DB::table('laporan_pimpinan')
            ->join('periode_laporan', 'periode_laporan.id', '=', 'laporan_pimpinan.periode_id')
            ->join('users', 'users.id', '=', 'laporan_pimpinan.generated_by_user_id')
            ->select(
                'laporan_pimpinan.id',
                'laporan_pimpinan.jenis_laporan',
                'laporan_pimpinan.file_url',
                'laporan_pimpinan.generated_at',
                'periode_laporan.bulan',
                'periode_laporan.tahun',
                'users.name as generated_by'
            );

        return $this->respond(
            $request,
            $query,
            ['laporan_pimpinan.generated_at', 'laporan_pimpinan.jenis_laporan', 'periode_laporan.tahun', 'users.name', 'laporan_pimpinan.file_url'],
            ['laporan_pimpinan.jenis_laporan', 'users.name'],
            'laporan_pimpinan.generated_at',
            function ($row): array {
                return [
                    'generated_at' => (string) $row->generated_at,
                    'jenis_laporan' => e($row->jenis_laporan),
                    'periode' => sprintf('%02d/%d', (int) $row->bulan, (int) $row->tahun),
                    'generated_by' => e($row->generated_by),
                    'file_url' => e($row->file_url),
                    'actions' => $this->actionsHtml($this->payload([
                        'title' => 'Laporan Terverifikasi',
                        'fields' => [
                            ['label' => 'Jenis Laporan', 'value' => $row->jenis_laporan],
                            ['label' => 'Periode', 'value' => sprintf('%02d/%d', (int) $row->bulan, (int) $row->tahun)],
                            ['label' => 'Generated By', 'value' => $row->generated_by],
                            ['label' => 'File', 'value' => $row->file_url],
                        ],
                    ])),
                ];
            }
        );
    }

    private function pimpinanLaporanDetail(Request $request): JsonResponse
    {
        $query = DB::table('laporan_pimpinan_kecamatan')
            ->join('kecamatan', 'kecamatan.id', '=', 'laporan_pimpinan_kecamatan.kecamatan_id')
            ->join('laporan_pimpinan', 'laporan_pimpinan.id', '=', 'laporan_pimpinan_kecamatan.laporan_id')
            ->join('periode_laporan', 'periode_laporan.id', '=', 'laporan_pimpinan.periode_id')
            ->select(
                'laporan_pimpinan_kecamatan.id',
                'kecamatan.nama_kecamatan',
                'periode_laporan.bulan',
                'periode_laporan.tahun',
                'laporan_pimpinan_kecamatan.total_produksi',
                'laporan_pimpinan_kecamatan.total_luas',
                'laporan_pimpinan_kecamatan.total_petani',
                'laporan_pimpinan_kecamatan.total_lahan'
            );

        return $this->respond(
            $request,
            $query,
            ['periode_laporan.tahun', 'kecamatan.nama_kecamatan', 'laporan_pimpinan_kecamatan.total_produksi', 'laporan_pimpinan_kecamatan.total_luas', 'laporan_pimpinan_kecamatan.total_petani', 'laporan_pimpinan_kecamatan.total_lahan'],
            ['kecamatan.nama_kecamatan'],
            'laporan_pimpinan_kecamatan.id',
            function ($row): array {
                return [
                    'periode' => sprintf('%02d/%d', (int) $row->bulan, (int) $row->tahun),
                    'nama_kecamatan' => e($row->nama_kecamatan),
                    'total_produksi' => (string) $row->total_produksi,
                    'total_luas' => (string) $row->total_luas,
                    'total_petani' => (string) $row->total_petani,
                    'total_lahan' => (string) $row->total_lahan,
                    'actions' => $this->actionsHtml($this->payload([
                        'title' => 'Detail Laporan Kecamatan',
                        'fields' => [
                            ['label' => 'Periode', 'value' => sprintf('%02d/%d', (int) $row->bulan, (int) $row->tahun)],
                            ['label' => 'Kecamatan', 'value' => $row->nama_kecamatan],
                            ['label' => 'Total Produksi', 'value' => $row->total_produksi],
                            ['label' => 'Total Luas', 'value' => $row->total_luas],
                            ['label' => 'Total Petani', 'value' => $row->total_petani],
                            ['label' => 'Total Lahan', 'value' => $row->total_lahan],
                        ],
                    ])),
                ];
            }
        );
    }

    private function pimpinanAnalitikKomoditas(Request $request): JsonResponse
    {
        $query = DB::table('produksi_panen')
            ->join('lahan_komoditas', 'lahan_komoditas.id', '=', 'produksi_panen.lahan_komoditas_id')
            ->join('komoditas', 'komoditas.id', '=', 'lahan_komoditas.komoditas_id')
            ->groupBy('komoditas.id', 'komoditas.nama_komoditas')
            ->selectRaw('komoditas.id')
            ->selectRaw('komoditas.nama_komoditas')
            ->selectRaw('COALESCE(SUM(produksi_panen.jumlah_produksi),0) as total_produksi')
            ->selectRaw('COALESCE(AVG(produksi_panen.harga_jual),0) as rata_harga');

        return $this->respond(
            $request,
            $query,
            ['komoditas.nama_komoditas', 'total_produksi', 'rata_harga'],
            ['komoditas.nama_komoditas'],
            'total_produksi',
            function ($row): array {
                return [
                    'nama_komoditas' => e($row->nama_komoditas),
                    'total_produksi' => (string) $row->total_produksi,
                    'rata_harga' => number_format((float) $row->rata_harga, 2, '.', ''),
                    'actions' => $this->actionsHtml($this->payload([
                        'title' => 'Analitik Komoditas',
                        'fields' => [
                            ['label' => 'Komoditas', 'value' => $row->nama_komoditas],
                            ['label' => 'Total Produksi', 'value' => $row->total_produksi],
                            ['label' => 'Rata-rata Harga', 'value' => $row->rata_harga],
                        ],
                    ])),
                ];
            }
        );
    }

    private function pimpinanAnalitikPeriode(Request $request): JsonResponse
    {
        $query = DB::table('produksi_panen')
            ->join('periode_laporan', 'periode_laporan.id', '=', 'produksi_panen.periode_id')
            ->groupBy('periode_laporan.id', 'periode_laporan.bulan', 'periode_laporan.tahun')
            ->selectRaw('periode_laporan.id')
            ->selectRaw('periode_laporan.bulan, periode_laporan.tahun')
            ->selectRaw('COALESCE(SUM(produksi_panen.jumlah_produksi),0) as total_produksi');

        return $this->respond(
            $request,
            $query,
            ['periode_laporan.tahun', 'total_produksi'],
            ['periode_laporan.tahun', 'periode_laporan.bulan'],
            'periode_laporan.tahun',
            function ($row): array {
                return [
                    'periode' => sprintf('%02d/%d', (int) $row->bulan, (int) $row->tahun),
                    'total_produksi' => (string) $row->total_produksi,
                    'actions' => $this->actionsHtml($this->payload([
                        'title' => 'Tren Periode',
                        'fields' => [
                            ['label' => 'Periode', 'value' => sprintf('%02d/%d', (int) $row->bulan, (int) $row->tahun)],
                            ['label' => 'Total Produksi', 'value' => $row->total_produksi],
                        ],
                    ])),
                ];
            }
        );
    }

    private function pimpinanUnduhLaporan(Request $request): JsonResponse
    {
        $query = DB::table('laporan_pimpinan')
            ->join('periode_laporan', 'periode_laporan.id', '=', 'laporan_pimpinan.periode_id')
            ->select(
                'laporan_pimpinan.id',
                'laporan_pimpinan.jenis_laporan',
                'laporan_pimpinan.file_url',
                'periode_laporan.bulan',
                'periode_laporan.tahun'
            );

        return $this->respond(
            $request,
            $query,
            ['periode_laporan.tahun', 'laporan_pimpinan.jenis_laporan', 'laporan_pimpinan.file_url'],
            ['laporan_pimpinan.jenis_laporan'],
            'laporan_pimpinan.id',
            function ($row): array {
                return [
                    'periode' => sprintf('%02d/%d', (int) $row->bulan, (int) $row->tahun),
                    'jenis_laporan' => e($row->jenis_laporan),
                    'file_url' => e($row->file_url),
                    'actions' => $this->actionsHtml($this->payload([
                        'title' => 'Dokumen Laporan',
                        'fields' => [
                            ['label' => 'Periode', 'value' => sprintf('%02d/%d', (int) $row->bulan, (int) $row->tahun)],
                            ['label' => 'Jenis Laporan', 'value' => $row->jenis_laporan],
                            ['label' => 'File', 'value' => $row->file_url],
                        ],
                    ])),
                ];
            }
        );
    }

    private function pimpinanPetaKecamatan(Request $request): JsonResponse
    {
        $query = DB::table('kecamatan')
            ->leftJoin('desa', 'desa.kecamatan_id', '=', 'kecamatan.id')
            ->leftJoin('lahan', 'lahan.desa_id', '=', 'desa.id')
            ->leftJoin('lahan_komoditas', 'lahan_komoditas.lahan_id', '=', 'lahan.id')
            ->groupBy('kecamatan.id', 'kecamatan.nama_kecamatan', 'kecamatan.centroid_lat', 'kecamatan.centroid_lng')
            ->selectRaw('kecamatan.id')
            ->selectRaw('kecamatan.nama_kecamatan, kecamatan.centroid_lat, kecamatan.centroid_lng')
            ->selectRaw('COUNT(DISTINCT lahan.id) as total_lahan')
            ->selectRaw('COUNT(DISTINCT lahan_komoditas.komoditas_id) as total_komoditas');

        return $this->respond(
            $request,
            $query,
            ['kecamatan.nama_kecamatan', 'kecamatan.centroid_lat', 'kecamatan.centroid_lng', 'total_lahan', 'total_komoditas'],
            ['kecamatan.nama_kecamatan'],
            'kecamatan.nama_kecamatan',
            function ($row): array {
                return [
                    'nama_kecamatan' => e($row->nama_kecamatan),
                    'centroid_lat' => (string) $row->centroid_lat,
                    'centroid_lng' => (string) $row->centroid_lng,
                    'total_lahan' => (string) $row->total_lahan,
                    'total_komoditas' => (string) $row->total_komoditas,
                    'actions' => $this->actionsHtml($this->payload([
                        'title' => 'Detail Titik Kecamatan',
                        'fields' => [
                            ['label' => 'Kecamatan', 'value' => $row->nama_kecamatan],
                            ['label' => 'Total Lahan', 'value' => $row->total_lahan],
                            ['label' => 'Total Komoditas', 'value' => $row->total_komoditas],
                        ],
                        'spatial' => [
                            'lat' => $row->centroid_lat,
                            'lng' => $row->centroid_lng,
                            'label' => $row->nama_kecamatan,
                        ],
                    ])),
                ];
            }
        );
    }

    private function pimpinanPetaBalai(Request $request): JsonResponse
    {
        $query = DB::table('balai_penyuluh')
            ->join('kecamatan', 'kecamatan.id', '=', 'balai_penyuluh.kecamatan_id')
            ->select('balai_penyuluh.id', 'balai_penyuluh.nama_balai', 'balai_penyuluh.latitude', 'balai_penyuluh.longitude', 'kecamatan.nama_kecamatan');

        return $this->respond(
            $request,
            $query,
            ['balai_penyuluh.nama_balai', 'kecamatan.nama_kecamatan', 'balai_penyuluh.latitude'],
            ['balai_penyuluh.nama_balai', 'kecamatan.nama_kecamatan'],
            'balai_penyuluh.nama_balai',
            function ($row): array {
                return [
                    'nama_balai' => e($row->nama_balai),
                    'nama_kecamatan' => e($row->nama_kecamatan),
                    'koordinat' => trim(($row->latitude ?? '-').', '.($row->longitude ?? '-')),
                    'actions' => $this->actionsHtml($this->payload([
                        'title' => 'Detail Titik Balai',
                        'fields' => [
                            ['label' => 'Balai', 'value' => $row->nama_balai],
                            ['label' => 'Kecamatan', 'value' => $row->nama_kecamatan],
                        ],
                        'spatial' => [
                            'lat' => $row->latitude,
                            'lng' => $row->longitude,
                            'label' => $row->nama_balai,
                        ],
                    ])),
                ];
            }
        );
    }

    private function respond(
        Request $request,
        Builder $query,
        array $columns,
        array $searchable,
        string $defaultOrderColumn,
        callable $transformer
    ): JsonResponse {
        $draw = (int) $request->integer('draw', 1);
        $start = max(0, (int) $request->integer('start', 0));
        $length = (int) $request->integer('length', 10);
        $length = $length > 0 ? min($length, 100) : 10;

        $total = $this->countFromQuery($query);

        $searchTerm = trim((string) $request->input('search.value', ''));
        if ($searchTerm !== '') {
            $query->where(function ($where) use ($searchable, $searchTerm): void {
                foreach ($searchable as $column) {
                    $where->orWhere($column, 'like', '%'.$searchTerm.'%');
                }
            });
        }

        $filtered = $this->countFromQuery($query);

        $orderIndex = (int) $request->input('order.0.column', 0);
        $orderDir = strtolower((string) $request->input('order.0.dir', 'asc')) === 'desc' ? 'desc' : 'asc';
        $orderColumn = $columns[$orderIndex] ?? $defaultOrderColumn;
        if ($orderColumn !== 'actions') {
            $query->orderBy($orderColumn, $orderDir);
        }

        $rows = $query->offset($start)->limit($length)->get();
        $data = $rows->map(fn ($row): array => $transformer($row))->toArray();

        return response()->json([
            'draw' => $draw,
            'recordsTotal' => $total,
            'recordsFiltered' => $filtered,
            'data' => $data,
        ]);
    }

    private function countFromQuery(Builder $query): int
    {
        $base = $query
            ->cloneWithout(['columns', 'orders', 'limit', 'offset'])
            ->cloneWithoutBindings(['select', 'order']);

        return (int) DB::query()
            ->fromSub($base->selectRaw('1 as row_exists'), 'x')
            ->count();
    }

    private function payload(array $payload): string
    {
        return base64_encode((string) json_encode($payload, JSON_UNESCAPED_UNICODE));
    }

    private function actionsHtml(string $payload): string
    {
        $escaped = e($payload);

        return (string) new HtmlString(
            '<div class="d-flex gap-1">'.
            '<button type="button" class="btn btn-outline-info btn-sm js-row-view" data-row="'.$escaped.'">View</button>'.
            '<button type="button" class="btn btn-outline-primary btn-sm js-row-edit" data-row="'.$escaped.'">Edit</button>'.
            '<button type="button" class="btn btn-outline-danger btn-sm js-row-delete" data-row="'.$escaped.'">Delete</button>'.
            '</div>'
        );
    }

    private function empty(Request $request): JsonResponse
    {
        return response()->json([
            'draw' => (int) $request->integer('draw', 1),
            'recordsTotal' => 0,
            'recordsFiltered' => 0,
            'data' => [],
        ]);
    }

    private function managedKecamatanIds(): array
    {
        return DB::table('user_wilayah')
            ->where('user_id', auth()->id())
            ->pluck('kecamatan_id')
            ->map(fn ($x) => (int) $x)
            ->toArray();
    }

    private function penyuluhId(): ?int
    {
        return DB::table('penyuluh')->where('user_id', auth()->id())->value('id');
    }

    private function kunjunganOptionsForPenyuluh(int $penyuluhId): array
    {
        return DB::table('kunjungan_monitoring')
            ->join('penugasan_penyuluh', 'penugasan_penyuluh.id', '=', 'kunjungan_monitoring.penugasan_id')
            ->join('lahan', 'lahan.id', '=', 'penugasan_penyuluh.lahan_id')
            ->join('petani', 'petani.id', '=', 'lahan.petani_id')
            ->where('penugasan_penyuluh.penyuluh_id', $penyuluhId)
            ->orderByDesc('kunjungan_monitoring.tanggal_kunjungan')
            ->select('kunjungan_monitoring.id', 'petani.nama_petani', 'kunjungan_monitoring.tanggal_kunjungan')
            ->get()
            ->map(fn ($x): array => [
                'value' => (string) $x->id,
                'label' => $x->nama_petani.' - '.$x->tanggal_kunjungan,
            ])
            ->toArray();
    }

    private function toDatetimeLocal(?string $datetime): ?string
    {
        if (! $datetime) {
            return null;
        }

        return str_replace(' ', 'T', substr($datetime, 0, 16));
    }
}
