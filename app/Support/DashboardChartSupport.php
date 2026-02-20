<?php

namespace App\Support;

use Illuminate\Support\Collection;

class DashboardChartSupport
{
    public static function candlestickFromRows(Collection $rows, string $dateKey = 'tanggal_panen', string $priceKey = 'harga_jual', string $sortKey = 'id'): array
    {
        if ($rows->isEmpty()) {
            return [];
        }

        return $rows
            ->filter(fn ($row) => ! is_null($row->{$priceKey} ?? null) && ! is_null($row->{$dateKey} ?? null))
            ->groupBy(fn ($row) => (string) $row->{$dateKey})
            ->sortKeys()
            ->map(function (Collection $group) use ($dateKey, $priceKey, $sortKey): ?array {
                $ordered = $group->sortBy(fn ($row) => (int) ($row->{$sortKey} ?? 0))->values();
                if ($ordered->isEmpty()) {
                    return null;
                }

                $open = (float) ($ordered->first()->{$priceKey} ?? 0);
                $close = (float) ($ordered->last()->{$priceKey} ?? 0);
                $high = (float) $ordered->max($priceKey);
                $low = (float) $ordered->min($priceKey);
                $date = (string) $ordered->first()->{$dateKey};

                return [
                    'x' => $date,
                    'y' => [
                        round($open, 2),
                        round($high, 2),
                        round($low, 2),
                        round($close, 2),
                    ],
                ];
            })
            ->filter()
            ->values()
            ->all();
    }

    public static function card(string $key, string $title, int|float|string $value, string $suffix = ''): array
    {
        return [
            'key' => $key,
            'title' => $title,
            'value' => $value,
            'suffix' => $suffix,
        ];
    }
}
