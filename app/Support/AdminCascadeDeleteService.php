<?php

namespace App\Support;

use App\Models\User;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;
use RuntimeException;

class AdminCascadeDeleteService
{
    public function supports(string $entity): bool
    {
        return array_key_exists($entity, self::supportedEntities());
    }

    public static function supportedEntities(): array
    {
        return [
            'kecamatan' => [
                'label' => 'Kecamatan',
                'module_label' => 'Master Wilayah',
                'route' => 'admin.wilayah',
                'description' => 'Menghapus kecamatan beserta desa, balai, akun admin kecamatan, petani, lahan, penugasan, kunjungan, laporan kecamatan, dan data turunannya.',
            ],
            'desa' => [
                'label' => 'Desa',
                'module_label' => 'Master Wilayah',
                'route' => 'admin.wilayah',
                'description' => 'Menghapus desa beserta petani, lahan, komoditas lahan, penugasan, kunjungan, dan data turunannya.',
            ],
            'komoditas' => [
                'label' => 'Komoditas',
                'module_label' => 'Master Komoditas',
                'route' => 'admin.komoditas',
                'description' => 'Menghapus komoditas beserta titik komoditas lahan, data produksi, style ikon komoditas, dan usulan perubahan terkait.',
            ],
            'kelompok_tani' => [
                'label' => 'Master Kelompok Tani',
                'module_label' => 'Master Kelompok Tani',
                'route' => 'admin.kelompok_tani',
                'description' => 'Menghapus master kelompok tani dan membersihkan referensinya dari data petani serta usulan perubahan data yang terkait.',
            ],
            'balai' => [
                'label' => 'Balai Penyuluh',
                'module_label' => 'Data Balai & Penyuluh',
                'route' => 'admin.balai',
                'description' => 'Menghapus balai beserta akun admin kecamatan, data penyuluh di balai tersebut, penugasan, kunjungan, media, dan data turunannya.',
            ],
            'penyuluh' => [
                'label' => 'Penyuluh',
                'module_label' => 'Data Balai & Penyuluh',
                'route' => 'admin.balai',
                'description' => 'Menghapus penyuluh beserta akun login, penugasan, kunjungan, media, notifikasi, dan data laporan lapangan yang diturunkan dari penyuluh tersebut.',
            ],
        ];
    }

    public static function entityLabel(string $entity): string
    {
        return self::supportedEntities()[$entity]['label'] ?? 'Data';
    }

    public static function rowConfirmationKey(string $entity, array|object $row): string
    {
        $payload = is_object($row) ? (array) $row : $row;

        $token = match ($entity) {
            'kecamatan' => (string) ($payload['kode_kecamatan'] ?? $payload['id'] ?? ''),
            'desa' => (string) ($payload['kode_desa'] ?? $payload['id'] ?? ''),
            'komoditas' => (string) ($payload['kode_komoditas'] ?? $payload['id'] ?? ''),
            'kelompok_tani' => (string) ($payload['kode_kelompok'] ?? $payload['id'] ?? ''),
            'balai' => 'BALAI-'.$payload['id'],
            'penyuluh' => 'PENYULUH-'.$payload['id'],
            default => (string) ($payload['id'] ?? ''),
        };

        return 'HAPUS '.self::normalizeToken($token);
    }

    public function confirmationKey(string $entity, int $id): ?string
    {
        $record = $this->recordForEntity($entity, $id);

        if (! $record) {
            return null;
        }

        return self::rowConfirmationKey($entity, $record);
    }

    public function delete(string $entity, int $id): array
    {
        if (! $this->supports($entity)) {
            throw new RuntimeException('Tipe data tidak mendukung penghapusan menyeluruh.');
        }

        $meta = $this->recordForEntity($entity, $id);
        if (! $meta) {
            throw new RuntimeException(self::entityLabel($entity).' tidak ditemukan.');
        }

        $filesToDelete = [];

        DB::transaction(function () use ($entity, $id, &$filesToDelete): void {
            match ($entity) {
                'kecamatan' => $this->deleteKecamatan($id, $filesToDelete),
                'desa' => $this->deleteDesa($id, $filesToDelete),
                'komoditas' => $this->deleteKomoditas($id),
                'kelompok_tani' => $this->deleteKelompokTani($id),
                'balai' => $this->deleteBalai($id, $filesToDelete),
                'penyuluh' => $this->deletePenyuluh($id, $filesToDelete),
                default => throw new RuntimeException('Tipe data tidak mendukung penghapusan menyeluruh.'),
            };
        });

        $this->deleteQueuedFiles($filesToDelete);

        return [
            'message' => self::entityLabel($entity).' "'.$meta['display_name'].'" berhasil dihapus menyeluruh beserta seluruh data turunannya.',
        ];
    }

    private function recordForEntity(string $entity, int $id): ?array
    {
        $row = match ($entity) {
            'kecamatan' => DB::table('kecamatan')->where('id', $id)->first(['id', 'kode_kecamatan', 'nama_kecamatan']),
            'desa' => DB::table('desa')->where('id', $id)->first(['id', 'kode_desa', 'nama_desa']),
            'komoditas' => DB::table('komoditas')->where('id', $id)->first(['id', 'kode_komoditas', 'nama_komoditas']),
            'kelompok_tani' => DB::table('master_kelompok_tani')->where('id', $id)->first(['id', 'kode_kelompok', 'nama_kelompok']),
            'balai' => DB::table('balai_penyuluh')->where('id', $id)->first(['id', 'nama_balai']),
            'penyuluh' => DB::table('penyuluh')
                ->join('users', 'users.id', '=', 'penyuluh.user_id')
                ->where('penyuluh.id', $id)
                ->first(['penyuluh.id', 'users.name as nama_penyuluh']),
            default => null,
        };

        if (! $row) {
            return null;
        }

        $payload = (array) $row;

        $displayName = match ($entity) {
            'kecamatan' => (string) $payload['nama_kecamatan'],
            'desa' => (string) $payload['nama_desa'],
            'komoditas' => (string) $payload['nama_komoditas'],
            'kelompok_tani' => (string) $payload['nama_kelompok'],
            'balai' => (string) $payload['nama_balai'],
            'penyuluh' => (string) $payload['nama_penyuluh'],
            default => (string) ($payload['id'] ?? ''),
        };

        return [
            ...$payload,
            'display_name' => $displayName,
        ];
    }

    private static function normalizeToken(string $token): string
    {
        $ascii = Str::upper(Str::ascii($token));
        $clean = preg_replace('/[^A-Z0-9]+/', '-', $ascii) ?: '';

        return trim((string) $clean, '-') !== '' ? trim((string) $clean, '-') : 'ID';
    }

    private function deleteKecamatan(int $kecamatanId, array &$filesToDelete): void
    {
        $desaIds = $this->pluckIds('desa', 'kecamatan_id', $kecamatanId);
        foreach ($desaIds as $desaId) {
            $this->deleteDesa($desaId, $filesToDelete);
        }

        $balaiIds = $this->pluckIds('balai_penyuluh', 'kecamatan_id', $kecamatanId);
        foreach ($balaiIds as $balaiId) {
            $this->deleteBalai($balaiId, $filesToDelete);
        }

        DB::table('laporan_pimpinan_kecamatan')->where('kecamatan_id', $kecamatanId)->delete();

        $adminIds = DB::table('user_wilayah')
            ->join('users', 'users.id', '=', 'user_wilayah.user_id')
            ->where('user_wilayah.kecamatan_id', $kecamatanId)
            ->where('users.role', User::ROLE_ADMIN_KECAMATAN)
            ->pluck('users.id')
            ->map(fn ($value): int => (int) $value)
            ->toArray();

        foreach (array_unique($adminIds) as $adminId) {
            $this->deleteUserAccount($adminId);
        }

        DB::table('user_wilayah')->where('kecamatan_id', $kecamatanId)->delete();
        DB::table('kecamatan')->where('id', $kecamatanId)->delete();
    }

    private function deleteDesa(int $desaId, array &$filesToDelete): void
    {
        $petaniRows = DB::table('petani')
            ->where('desa_id', $desaId)
            ->get(['id', 'foto_petani_url']);

        $lahanIds = DB::table('lahan')
            ->where('desa_id', $desaId)
            ->pluck('id')
            ->map(fn ($value): int => (int) $value)
            ->toArray();

        foreach ($lahanIds as $lahanId) {
            $this->deleteLahan($lahanId, $filesToDelete);
        }

        $petaniIds = $petaniRows->pluck('id')->map(fn ($value): int => (int) $value)->toArray();
        $this->deleteUsulanForTarget('petani', $petaniIds);

        foreach ($petaniRows as $petani) {
            $this->queueStorageFile($petani->foto_petani_url, $filesToDelete);
        }

        if ($petaniIds !== []) {
            DB::table('petani')->whereIn('id', $petaniIds)->delete();
        }

        DB::table('desa')->where('id', $desaId)->delete();
    }

    private function deleteKomoditas(int $komoditasId): void
    {
        $lahanKomoditasIds = $this->pluckIds('lahan_komoditas', 'komoditas_id', $komoditasId);
        foreach ($lahanKomoditasIds as $lahanKomoditasId) {
            $this->deleteLahanKomoditas($lahanKomoditasId);
        }

        DB::table('map_marker_styles')->where('komoditas_id', $komoditasId)->delete();
        DB::table('map_marker_styles')->where('style_code', 'komoditas:'.$komoditasId)->delete();
        DB::table('komoditas')->where('id', $komoditasId)->delete();
    }

    private function deleteKelompokTani(int $kelompokId): void
    {
        $kelompok = DB::table('master_kelompok_tani')
            ->where('id', $kelompokId)
            ->first(['id', 'nama_kelompok']);

        if (! $kelompok) {
            return;
        }

        DB::table('petani')
            ->where('kelompok_tani', $kelompok->nama_kelompok)
            ->update([
                'kelompok_tani' => null,
                'updated_at' => now(),
            ]);

        $usulanIds = DB::table('usulan_perubahan_data')
            ->where('target_tipe', 'petani')
            ->where('field_name', 'kelompok_tani')
            ->where(function ($query) use ($kelompok): void {
                $query->where('nilai_lama', $kelompok->nama_kelompok)
                    ->orWhere('nilai_usulan', $kelompok->nama_kelompok);
            })
            ->pluck('id')
            ->map(fn ($value): int => (int) $value)
            ->toArray();

        $this->deleteUsulanByIds($usulanIds);
        DB::table('master_kelompok_tani')->where('id', $kelompokId)->delete();
    }

    private function deleteBalai(int $balaiId, array &$filesToDelete): void
    {
        $balai = DB::table('balai_penyuluh')
            ->where('id', $balaiId)
            ->first(['id', 'kecamatan_id', 'foto_balai_url']);

        if (! $balai) {
            return;
        }

        $penyuluhIds = $this->pluckIds('penyuluh', 'balai_id', $balaiId);
        foreach ($penyuluhIds as $penyuluhId) {
            $this->deletePenyuluh($penyuluhId, $filesToDelete);
        }

        $balaiMedia = DB::table('lampiran_media')
            ->where('balai_id', $balaiId)
            ->get(['id', 'file_url']);

        foreach ($balaiMedia as $media) {
            $this->queueStorageFile($media->file_url, $filesToDelete);
        }

        $mediaIds = $balaiMedia->pluck('id')->map(fn ($value): int => (int) $value)->toArray();
        if ($mediaIds !== []) {
            DB::table('lampiran_media')->whereIn('id', $mediaIds)->delete();
        }

        $this->queueStorageFile($balai->foto_balai_url, $filesToDelete);

        DB::table('balai_penyuluh')->where('id', $balaiId)->delete();

        $remainingBalai = DB::table('balai_penyuluh')
            ->where('kecamatan_id', $balai->kecamatan_id)
            ->exists();

        if (! $remainingBalai) {
            $adminIds = DB::table('user_wilayah')
                ->join('users', 'users.id', '=', 'user_wilayah.user_id')
                ->where('user_wilayah.kecamatan_id', $balai->kecamatan_id)
                ->where('users.role', User::ROLE_ADMIN_KECAMATAN)
                ->pluck('users.id')
                ->map(fn ($value): int => (int) $value)
                ->toArray();

            foreach (array_unique($adminIds) as $adminId) {
                $this->deleteUserAccount($adminId);
            }
        }
    }

    private function deletePenyuluh(int $penyuluhId, array &$filesToDelete): void
    {
        $penyuluh = DB::table('penyuluh')
            ->where('id', $penyuluhId)
            ->first(['id', 'user_id', 'foto_penyuluh_url']);

        if (! $penyuluh) {
            return;
        }

        $penugasanIds = $this->pluckIds('penugasan_penyuluh', 'penyuluh_id', $penyuluhId);
        foreach ($penugasanIds as $penugasanId) {
            $this->deletePenugasan($penugasanId, $filesToDelete);
        }

        $this->queueStorageFile($penyuluh->foto_penyuluh_url, $filesToDelete);

        DB::table('penyuluh')->where('id', $penyuluhId)->delete();
        $this->deleteUserAccount((int) $penyuluh->user_id);
    }

    private function deleteLahan(int $lahanId, array &$filesToDelete): void
    {
        $lahan = DB::table('lahan')
            ->where('id', $lahanId)
            ->first(['id', 'foto_lahan_url']);

        if (! $lahan) {
            return;
        }

        $penugasanIds = $this->pluckIds('penugasan_penyuluh', 'lahan_id', $lahanId);
        foreach ($penugasanIds as $penugasanId) {
            $this->deletePenugasan($penugasanId, $filesToDelete);
        }

        $lahanKomoditasIds = $this->pluckIds('lahan_komoditas', 'lahan_id', $lahanId);
        foreach ($lahanKomoditasIds as $lahanKomoditasId) {
            $this->deleteLahanKomoditas($lahanKomoditasId);
        }

        $this->deleteUsulanForTarget('lahan', [$lahanId]);

        $lahanMedia = DB::table('lampiran_media')
            ->where('lahan_id', $lahanId)
            ->get(['id', 'file_url']);

        foreach ($lahanMedia as $media) {
            $this->queueStorageFile($media->file_url, $filesToDelete);
        }

        $mediaIds = $lahanMedia->pluck('id')->map(fn ($value): int => (int) $value)->toArray();
        if ($mediaIds !== []) {
            DB::table('lampiran_media')->whereIn('id', $mediaIds)->delete();
        }

        $this->queueStorageFile($lahan->foto_lahan_url, $filesToDelete);
        DB::table('lahan')->where('id', $lahanId)->delete();
    }

    private function deleteLahanKomoditas(int $lahanKomoditasId): void
    {
        DB::table('produksi_panen')->where('lahan_komoditas_id', $lahanKomoditasId)->delete();
        $this->deleteUsulanForTarget('lahan_komoditas', [$lahanKomoditasId]);
        DB::table('lahan_komoditas')->where('id', $lahanKomoditasId)->delete();
    }

    private function deletePenugasan(int $penugasanId, array &$filesToDelete): void
    {
        $kunjunganIds = $this->pluckIds('kunjungan_monitoring', 'penugasan_id', $penugasanId);
        foreach ($kunjunganIds as $kunjunganId) {
            $this->deleteKunjungan($kunjunganId, $filesToDelete);
        }

        $this->deleteUsulanByIds(
            DB::table('usulan_perubahan_data')
                ->where('penugasan_id', $penugasanId)
                ->pluck('id')
                ->map(fn ($value): int => (int) $value)
                ->toArray()
        );

        DB::table('penugasan_penyuluh')->where('id', $penugasanId)->delete();
    }

    private function deleteKunjungan(int $kunjunganId, array &$filesToDelete): void
    {
        DB::table('produksi_panen')->where('kunjungan_id', $kunjunganId)->delete();

        $this->deleteUsulanByIds(
            DB::table('usulan_perubahan_data')
                ->where('kunjungan_id', $kunjunganId)
                ->pluck('id')
                ->map(fn ($value): int => (int) $value)
                ->toArray()
        );

        $mediaRows = DB::table('lampiran_media')
            ->where('kunjungan_id', $kunjunganId)
            ->get(['id', 'file_url']);

        foreach ($mediaRows as $media) {
            $this->queueStorageFile($media->file_url, $filesToDelete);
        }

        $mediaIds = $mediaRows->pluck('id')->map(fn ($value): int => (int) $value)->toArray();
        if ($mediaIds !== []) {
            DB::table('lampiran_media')->whereIn('id', $mediaIds)->delete();
        }

        DB::table('notifikasi')
            ->where('ref_tipe', 'kunjungan_monitoring')
            ->where('ref_id', $kunjunganId)
            ->delete();

        DB::table('verifikasi_log')->where('kunjungan_id', $kunjunganId)->delete();
        DB::table('kendala_kunjungan')->where('kunjungan_id', $kunjunganId)->delete();
        DB::table('kebutuhan_kunjungan')->where('kunjungan_id', $kunjunganId)->delete();
        DB::table('kunjungan_monitoring')->where('id', $kunjunganId)->delete();
    }

    private function deleteUsulanForTarget(string $targetType, array $targetIds): void
    {
        if ($targetIds === []) {
            return;
        }

        $this->deleteUsulanByIds(
            DB::table('usulan_perubahan_data')
                ->where('target_tipe', $targetType)
                ->whereIn('target_id', $targetIds)
                ->pluck('id')
                ->map(fn ($value): int => (int) $value)
                ->toArray()
        );
    }

    private function deleteUsulanByIds(array $usulanIds): void
    {
        $usulanIds = array_values(array_unique(array_filter(array_map('intval', $usulanIds))));
        if ($usulanIds === []) {
            return;
        }

        DB::table('notifikasi')
            ->where('ref_tipe', 'usulan_perubahan_data')
            ->whereIn('ref_id', $usulanIds)
            ->delete();

        DB::table('usulan_perubahan_data')->whereIn('id', $usulanIds)->delete();
    }

    private function deleteUserAccount(int $userId): void
    {
        $user = DB::table('users')->where('id', $userId)->first(['id', 'email']);
        if (! $user) {
            return;
        }

        if (Schema::hasTable('password_reset_tokens') && $user->email) {
            DB::table('password_reset_tokens')->where('email', $user->email)->delete();
        }

        DB::table('users')->where('id', $userId)->delete();
    }

    private function pluckIds(string $table, string $column, int $value): array
    {
        return DB::table($table)
            ->where($column, $value)
            ->pluck('id')
            ->map(fn ($id): int => (int) $id)
            ->toArray();
    }

    private function queueStorageFile(?string $fileUrl, array &$filesToDelete): void
    {
        if (! is_string($fileUrl) || trim($fileUrl) === '') {
            return;
        }

        $normalized = trim($fileUrl);
        $storagePath = null;

        if (str_starts_with($normalized, '/storage/')) {
            $storagePath = substr($normalized, 9);
        } elseif (str_starts_with($normalized, 'storage/')) {
            $storagePath = substr($normalized, 8);
        }

        if (! $storagePath) {
            return;
        }

        $storagePath = ltrim($storagePath, '/');
        if ($storagePath !== '') {
            $filesToDelete[$storagePath] = true;
        }
    }

    private function deleteQueuedFiles(array $filesToDelete): void
    {
        foreach (array_keys($filesToDelete) as $path) {
            if (Storage::disk('public')->exists($path)) {
                Storage::disk('public')->delete($path);
            }
        }
    }
}
