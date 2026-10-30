<?php

namespace App\Imports;

use App\Models\Category;
use App\Models\Product;
use Illuminate\Support\Str;
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

        $categoryName = $row['category'] ?? 'General';
        $category = Category::query()->firstOrCreate(
            ['name' => $categoryName],
            ['slug' => Str::slug($categoryName).'-'.Str::random(4), 'is_active' => true]
        );

        $barcode = $row['barcode'] ?? null;
        if (! $barcode) {
            $barcode = '2'.str_pad((string) random_int(0, 999999999999), 12, '0', STR_PAD_LEFT);
        }

        return Product::query()->updateOrCreate(
            ['barcode' => $barcode],
            [
                'category_id' => $category->id,
                'name' => $name,
                'sku' => $row['sku'] ?? null,
                'purchase_price' => $row['purchase_price'] ?? 0,
                'selling_price' => $row['selling_price'] ?? 0,
                'unit' => $row['unit'] ?? 'piece',
                'min_stock' => $row['min_stock'] ?? 5,
                'is_active' => true,
            ]
        );
    }
}
