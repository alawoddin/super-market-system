<?php

namespace App\Imports;

use App\Models\Product;
use Maatwebsite\Excel\Concerns\ToModel;
use Maatwebsite\Excel\Concerns\WithHeadingRow;

class ProductsImport implements ToModel, WithHeadingRow
{
    public function model(array $row)
    {
        $name = $row['name'] ?? null;
        if (! $name) {
            return null;
        }

        $barcode = $row['barcode'] ?? null;
        if (! $barcode) {
            $barcode = '2'.str_pad((string) random_int(0, 999999999999), 12, '0', STR_PAD_LEFT);
        }

        return Product::query()->updateOrCreate(
            ['barcode' => $barcode],
            [
                'name' => $name,
                'purchase_price' => $row['purchase_price'] ?? 0,
                'selling_price' => $row['selling_price'] ?? 0,
                'is_active' => true,
            ]
        );
    }
}
