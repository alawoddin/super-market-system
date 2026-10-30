<?php

namespace App\Support;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class DataTable
{
    public static function params(Request $request, int $defaultLength = 25): array
    {
        $length = (int) $request->input('length', $defaultLength);
        if ($length < 1 || $length > 200) {
            $length = $defaultLength;
        }

        return [
            'draw' => (int) $request->input('draw', 1),
            'start' => max(0, (int) $request->input('start', 0)),
            'length' => $length,
            'search' => trim((string) data_get($request->input('search'), 'value', '')),
            'order_column' => (int) data_get($request->input('order'), '0.column', 0),
            'order_dir' => strtolower((string) data_get($request->input('order'), '0.dir', 'desc')) === 'asc' ? 'asc' : 'desc',
        ];
    }

    public static function response(Request $request, Builder $base, Builder $filtered, array $orderMap, callable $mapRow, string $defaultOrder = 'id'): JsonResponse
    {
        $p = self::params($request);
        $recordsTotal = (clone $base)->count();
        $recordsFiltered = (clone $filtered)->count();
        $orderColumn = $orderMap[$p['order_column']] ?? $defaultOrder;

        $rows = $filtered
            ->orderBy($orderColumn, $p['order_dir'])
            ->orderByDesc('id')
            ->skip($p['start'])
            ->take($p['length'])
            ->get();

        $data = $rows->values()->map(fn ($row, $index) => $mapRow($row, $p['start'] + $index + 1));

        return response()->json([
            'draw' => $p['draw'],
            'recordsTotal' => $recordsTotal,
            'recordsFiltered' => $recordsFiltered,
            'data' => $data,
        ]);
    }
}
