<?php

namespace App\Exports;

use App\Models\Product;
use Maatwebsite\Excel\Concerns\FromCollection;
use Maatwebsite\Excel\Concerns\WithHeadings;

class ProductsExport implements FromCollection, WithHeadings
{
    public function collection()
    {
        return Product::query()
            ->with('category')
            ->get()
            ->map(fn (Product $p) => [
                $p->name,
                $p->barcode,
                $p->sku,
                $p->category?->name,
                $p->purchase_price,
                $p->selling_price,
                $p->unit,
                $p->min_stock,
                $p->stock_quantity,
                $p->is_active ? 'active' : 'inactive',
            ]);
    }

    public function headings(): array
    {
        return ['Name', 'Barcode', 'SKU', 'Category', 'Purchase Price', 'Selling Price', 'Unit', 'Min Stock', 'Stock', 'Status'];
    }
}
