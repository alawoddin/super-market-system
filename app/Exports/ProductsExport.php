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
            ->get()
            ->map(fn (Product $p) => [
                $p->name,
                $p->barcode,
                $p->purchase_price,
                $p->selling_price,
                $p->stock_quantity,
                $p->is_active ? 'active' : 'inactive',
            ]);
    }

    public function headings(): array
    {
        return ['Name', 'Barcode', 'Purchase Price', 'Selling Price', 'Stock', 'Status'];
    }
}
