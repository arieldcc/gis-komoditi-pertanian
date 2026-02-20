<?php

namespace App\Support;

use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

class MapStyleSupport
{
    public static function entityDefaults(): array
    {
        return [
            'entity:kecamatan' => [
                'scope' => 'entity',
                'label' => 'Kecamatan',
                'icon_symbol' => 'K',
                'icon_color' => '#14532d',
                'bg_color' => '#dcfce7',
                'size' => 30,
                'komoditas_id' => null,
            ],
            'entity:desa' => [
                'scope' => 'entity',
                'label' => 'Desa',
                'icon_symbol' => 'D',
                'icon_color' => '#1d4ed8',
                'bg_color' => '#dbeafe',
                'size' => 28,
                'komoditas_id' => null,
            ],
            'entity:balai' => [
                'scope' => 'entity',
                'label' => 'Balai Penyuluh',
                'icon_symbol' => 'B',
                'icon_color' => '#7c2d12',
                'bg_color' => '#ffedd5',
                'size' => 30,
                'komoditas_id' => null,
            ],
            'entity:komoditas_default' => [
                'scope' => 'entity',
                'label' => 'Komoditas Default',
                'icon_symbol' => 'C',
                'icon_color' => '#854d0e',
                'bg_color' => '#fef9c3',
                'size' => 28,
                'komoditas_id' => null,
            ],
        ];
    }

    public static function defaultKomoditasStyle(string $namaKomoditas = 'Komoditas'): array
    {
        return [
            'scope' => 'komoditas',
            'label' => 'Komoditas '.$namaKomoditas,
            'icon_symbol' => 'C',
            'icon_color' => '#9a3412',
            'bg_color' => '#ffedd5',
            'size' => 28,
        ];
    }

    public static function styleMap(): array
    {
        $styles = self::entityDefaults();

        if (! Schema::hasTable('map_marker_styles')) {
            return $styles;
        }

        $rows = DB::table('map_marker_styles')->get();
        foreach ($rows as $row) {
            $styles[$row->style_code] = [
                'scope' => $row->scope,
                'label' => $row->label,
                'icon_symbol' => $row->icon_symbol,
                'icon_color' => $row->icon_color,
                'bg_color' => $row->bg_color,
                'size' => (int) $row->size,
                'komoditas_id' => $row->komoditas_id,
            ];
        }

        return $styles;
    }

    public static function iconForStyleCode(string $styleCode, array $styleMap): array
    {
        return $styleMap[$styleCode]
            ?? $styleMap['entity:komoditas_default']
            ?? self::entityDefaults()['entity:komoditas_default'];
    }

    public static function ensureDefaultRows(): void
    {
        if (! Schema::hasTable('map_marker_styles')) {
            return;
        }

        foreach (self::entityDefaults() as $styleCode => $style) {
            $exists = DB::table('map_marker_styles')->where('style_code', $styleCode)->exists();
            if (! $exists) {
                DB::table('map_marker_styles')->insert([
                    'style_code' => $styleCode,
                    ...$style,
                    'created_at' => now(),
                    'updated_at' => now(),
                ]);
            }
        }

        if (! Schema::hasTable('komoditas')) {
            return;
        }

        $komoditas = DB::table('komoditas')->select('id', 'nama_komoditas')->get();
        foreach ($komoditas as $item) {
            $styleCode = 'komoditas:'.$item->id;
            $exists = DB::table('map_marker_styles')->where('style_code', $styleCode)->exists();
            if (! $exists) {
                DB::table('map_marker_styles')->insert([
                    'style_code' => $styleCode,
                    'komoditas_id' => $item->id,
                    ...self::defaultKomoditasStyle($item->nama_komoditas),
                    'created_at' => now(),
                    'updated_at' => now(),
                ]);
            }
        }
    }
}
