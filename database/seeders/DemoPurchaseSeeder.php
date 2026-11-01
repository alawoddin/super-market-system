<?php

namespace Database\Seeders;

use App\Models\Product;
use App\Models\Supplier;
use App\Models\User;
use App\Services\PurchaseService;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Auth;

class DemoPurchaseSeeder extends Seeder
{
    public function run(): void
    {
        $user = User::query()->first();
        if ($user) {
            Auth::login($user);
        }

        $supplier = Supplier::query()->first();
        if (! $supplier) {
            $supplier = Supplier::query()->create([
                'name' => 'Alawoddin Khan',
                'phone' => '0700000000',
                'is_active' => true,
            ]);
        }

        $product = Product::query()->where('name', 'like', '%Hit%')->first()
            ?? Product::query()->first();

        if (! $product) {
            $product = Product::query()->create([
                'name' => 'Hit Energy Drink',
                'sku' => 'HIT-001',
                'barcode' => '1001',
                'unit' => 'can',
                'purchase_price' => 33.33,
                'selling_price' => 50,
                'stock_quantity' => 0,
                'min_stock' => 24,
                'is_active' => true,
            ]);
        } else {
            $product->selling_price = 50;
            $product->save();
        }

        $service = app(PurchaseService::class);

        $service->create([
            'supplier_id' => $supplier->id,
            'invoice_number' => $service->nextInvoiceNumber(),
            'purchase_date' => now()->toDateString(),
            'discount' => 0,
            'amount_paid' => 9999.99,
            'notes' => 'Sample purchase (10 cartons × 30 cans)',
        ], [[
            'product_id' => $product->id,
            'quantity' => 300,
            'unit_cost' => 33.3333,
            'selling_price' => 50,
            'cartons' => 10,
            'units_per_carton' => 30,
            'carton_price' => 1000,
            'discount' => 0,
            'expiration_date' => now()->addMonths(8)->toDateString(),
        ]]);
    }
}
