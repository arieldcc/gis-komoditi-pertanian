<?php

namespace App\Support;

use Illuminate\Support\Facades\DB;
use RuntimeException;

class AutoCodeService
{
    public static function nextKecamatanCode(): string
    {
        return self::nextSequentialCode(
            table: 'kecamatan',
            column: 'kode_kecamatan',
            prefix: 'KEC',
            padding: 4,
            maxLength: 20
        );
    }

    public static function nextKomoditasCode(): string
    {
        return self::nextSequentialCode(
            table: 'komoditas',
            column: 'kode_komoditas',
            prefix: 'KMD',
            padding: 4,
            maxLength: 30
        );
    }

    public static function nextDesaCode(int $kecamatanId): string
    {
        $prefix = 'DSA-'.$kecamatanId.'-';

        return self::nextSequentialCode(
            table: 'desa',
            column: 'kode_desa',
            prefix: $prefix,
            padding: 4,
            maxLength: 20,
            filters: ['kecamatan_id' => $kecamatanId]
        );
    }

    public static function nextKelompokTaniCode(): string
    {
        return self::nextSequentialCode(
            table: 'master_kelompok_tani',
            column: 'kode_kelompok',
            prefix: 'KTP',
            padding: 4,
            maxLength: 30
        );
    }

    private static function nextSequentialCode(
        string $table,
        string $column,
        string $prefix,
        int $padding,
        int $maxLength,
        array $filters = []
    ): string {
        $prefixedCodes = self::baseQuery($table, $column, $filters)
            ->where($column, 'like', $prefix.'%')
            ->lockForUpdate()
            ->pluck($column);

        $maxNumber = 0;
        $pattern = '/^'.preg_quote($prefix, '/').'(\d+)$/';

        foreach ($prefixedCodes as $code) {
            if (preg_match($pattern, (string) $code, $matches) === 1) {
                $maxNumber = max($maxNumber, (int) $matches[1]);
            }
        }

        $nextNumber = $maxNumber + 1;

        while (true) {
            $candidate = $prefix.str_pad((string) $nextNumber, $padding, '0', STR_PAD_LEFT);

            if (strlen($candidate) > $maxLength) {
                throw new RuntimeException("Panjang kode melebihi batas untuk {$table}.{$column}.");
            }

            $exists = self::baseQuery($table, $column, $filters)
                ->where($column, $candidate)
                ->lockForUpdate()
                ->exists();

            if (! $exists) {
                return $candidate;
            }

            $nextNumber++;
        }
    }

    private static function baseQuery(string $table, string $column, array $filters)
    {
        $query = DB::table($table)->whereNotNull($column);

        foreach ($filters as $filterColumn => $value) {
            $query->where($filterColumn, $value);
        }

        return $query;
    }
}
