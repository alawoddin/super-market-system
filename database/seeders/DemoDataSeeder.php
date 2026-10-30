<?php

namespace Database\Seeders;

use App\Models\Category;
use App\Models\Customer;
use App\Models\ExpenseCategory;
use App\Models\Product;
use App\Models\Setting;
use App\Models\Supplier;
use Illuminate\Database\Seeder;
use Illuminate\Support\Str;

class DemoDataSeeder extends Seeder
{
    public function run(): void
    {
        Setting::current();

        foreach (['Rent', 'Electricity', 'Water', 'Internet', 'Salaries', 'Transportation', 'Maintenance', 'Other'] as $name) {
            ExpenseCategory::query()->firstOrCreate(['name' => $name], ['is_active' => true]);
        }

        $categories = collect([
            'Beverages', 'Dairy', 'Bakery', 'Snacks', 'Cleaning', 'Grocery',
        ])->map(fn ($name) => Category::query()->firstOrCreate(
            ['name' => $name],
            ['slug' => Str::slug($name), 'description' => $name.' products', 'is_active' => true]
        ));

        $supplier = Supplier::query()->firstOrCreate(
            ['name' => 'Kabul Wholesale'],
            ['phone' => '0700000001', 'address' => 'Kabul', 'opening_balance' => 0, 'is_active' => true]
        );

        Customer::query()->firstOrCreate(
            ['name' => 'Walk-in Regular'],
            ['phone' => '0700000002', 'address' => 'Kabul', 'opening_balance' => 0, 'is_active' => true]
        );

        $samples = [
            ['Coca Cola 1.5L', 'piece', 45, 55],
            ['Naan Bread', 'piece', 5, 8],
            ['Milk 1L', 'l', 50, 65],
            ['Rice 1kg', 'kg', 80, 95],
            ['Dish Soap', 'piece', 30, 40],
            ['Chips Pack', 'pack', 15, 25],
            ['Sugar 1kg', 'kg', 60, 75],
            ['Cooking Oil 1L', 'l', 120, 145],
        ];

        foreach ($samples as $i => [$name, $unit, $buy, $sell]) {
            Product::query()->firstOrCreate(
                ['barcode' => '89010000000'.($i + 1)],
                [
                    'category_id' => $categories[$i % $categories->count()]->id,
                    'name' => $name,
                    'sku' => 'SKU-'.str_pad((string) ($i + 1), 4, '0', STR_PAD_LEFT),
                    'purchase_price' => $buy,
                    'selling_price' => $sell,
                    'unit' => $unit,
                    'min_stock' => 5,
                    'stock_quantity' => 0,
                    'is_active' => true,
                    'track_expiry' => true,
                    'description' => $name,
                ]
            );
        }

        unset($supplier);
    }
}
