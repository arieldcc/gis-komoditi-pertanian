<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Support\MapStyleSupport;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\View\View;

class MapStyleController extends Controller
{
    private const ENTITY_STYLE_CODES = [
        'kecamatan' => 'entity:kecamatan',
        'desa' => 'entity:desa',
        'balai' => 'entity:balai',
        'komoditas_default' => 'entity:komoditas_default',
    ];

    public function index(): View
    {
        MapStyleSupport::ensureDefaultRows();

        $komoditas = DB::table('komoditas')
            ->select('id', 'nama_komoditas')
            ->orderBy('nama_komoditas')
            ->get();

        $styleMap = MapStyleSupport::styleMap();
        $entityStyles = [];

        foreach (self::ENTITY_STYLE_CODES as $entityKey => $styleCode) {
            $style = MapStyleSupport::iconForStyleCode($styleCode, $styleMap);
            $entityStyles[$entityKey] = [
                'style_code' => $styleCode,
                'label' => $style['label'],
                'icon_symbol' => $style['icon_symbol'],
                'icon_color' => $style['icon_color'],
                'bg_color' => $style['bg_color'],
                'size' => $style['size'],
            ];
        }

        $komoditasStyles = $komoditas->map(function ($item) use ($styleMap) {
            $styleCode = 'komoditas:'.$item->id;
            $style = $styleMap[$styleCode] ?? MapStyleSupport::defaultKomoditasStyle($item->nama_komoditas);

            return [
                'komoditas_id' => $item->id,
                'nama_komoditas' => $item->nama_komoditas,
                'style_code' => $styleCode,
                'icon_symbol' => $style['icon_symbol'],
                'icon_color' => $style['icon_color'],
                'bg_color' => $style['bg_color'],
                'size' => $style['size'],
            ];
        });

        return view('admin.map_style.index', compact('entityStyles', 'komoditasStyles'));
    }

    public function upsertEntity(Request $request, string $entityKey): RedirectResponse
    {
        if (! array_key_exists($entityKey, self::ENTITY_STYLE_CODES)) {
            return back()->with('error', 'Jenis entitas tidak valid.');
        }

        $payload = $this->validateStylePayload($request);
        $styleCode = self::ENTITY_STYLE_CODES[$entityKey];
        $default = MapStyleSupport::entityDefaults()[$styleCode];

        DB::table('map_marker_styles')->updateOrInsert(
            ['style_code' => $styleCode],
            [
                'scope' => 'entity',
                'komoditas_id' => null,
                'label' => $payload['label'] ?? $default['label'],
                'icon_symbol' => $payload['icon_symbol'],
                'icon_color' => $payload['icon_color'],
                'bg_color' => $payload['bg_color'],
                'size' => $payload['size'],
                'updated_at' => now(),
                'created_at' => now(),
            ]
        );

        return back()->with('success', 'Style ikon entitas berhasil diperbarui.');
    }

    public function upsertKomoditas(Request $request, int $komoditasId): RedirectResponse
    {
        $komoditas = DB::table('komoditas')->where('id', $komoditasId)->first();
        if (! $komoditas) {
            return back()->with('error', 'Data komoditas tidak ditemukan.');
        }

        $payload = $this->validateStylePayload($request);
        $default = MapStyleSupport::defaultKomoditasStyle($komoditas->nama_komoditas);

        DB::table('map_marker_styles')->updateOrInsert(
            ['style_code' => 'komoditas:'.$komoditasId],
            [
                'scope' => 'komoditas',
                'komoditas_id' => $komoditasId,
                'label' => $payload['label'] ?? 'Komoditas '.$komoditas->nama_komoditas,
                'icon_symbol' => $payload['icon_symbol'] ?? $default['icon_symbol'],
                'icon_color' => $payload['icon_color'] ?? $default['icon_color'],
                'bg_color' => $payload['bg_color'] ?? $default['bg_color'],
                'size' => $payload['size'] ?? $default['size'],
                'updated_at' => now(),
                'created_at' => now(),
            ]
        );

        return back()->with('success', 'Style ikon komoditas berhasil diperbarui.');
    }

    private function validateStylePayload(Request $request): array
    {
        return $request->validate([
            'label' => ['nullable', 'string', 'max:120'],
            'icon_symbol' => ['required', 'string', 'max:12'],
            'icon_color' => ['required', 'regex:/^#[0-9A-Fa-f]{6}$/'],
            'bg_color' => ['required', 'regex:/^#[0-9A-Fa-f]{6}$/'],
            'size' => ['required', 'integer', 'between:20,48'],
        ]);
    }

}
