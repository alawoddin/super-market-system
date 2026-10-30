<?php

namespace App\Http\Controllers;

use App\Models\Customer;
use App\Models\Product;
use App\Models\Setting;
use App\Services\SalesService;
use Illuminate\Http\Request;

class PosController extends Controller
{
    public function __construct(private SalesService $sales)
    {
    }

    public function index()
    {
        $customers = Customer::query()->where('is_active', true)->orderBy('name')->get(['id', 'name', 'phone']);
        $settings = Setting::current();

        return view('admin.pos.index', compact('customers', 'settings'));
    }

    public function lookup(Request $request)
    {
        $code = trim((string) $request->get('barcode', $request->get('q', '')));

        if ($code === '') {
            return response()->json(['message' => 'Empty query'], 422);
        }

        $product = Product::query()
            ->active()
            ->where(function ($q) use ($code) {
                $q->where('barcode', $code)->orWhere('sku', $code);
            })
            ->first();

        if (! $product) {
            $product = Product::query()->active()->where('name', 'like', "%{$code}%")->first();
        }

        if (! $product) {
            return response()->json(['message' => 'Product not found'], 404);
        }

        $available = $product->availableQuantity();

        if ($available <= 0) {
            return response()->json(['message' => 'Out of stock or expired'], 422);
        }

        return response()->json([
            'id' => $product->id,
            'name' => $product->name,
            'barcode' => $product->barcode,
            'unit' => $product->unit,
            'selling_price' => (float) $product->selling_price,
            'stock' => $available,
        ]);
    }

    public function search(Request $request)
    {
        $term = $request->get('q');

        $products = Product::query()
            ->active()
            ->search($term)
            ->limit(20)
            ->get()
            ->map(fn (Product $p) => [
                'id' => $p->id,
                'name' => $p->name,
                'barcode' => $p->barcode,
                'unit' => $p->unit,
                'selling_price' => (float) $p->selling_price,
                'stock' => $p->availableQuantity(),
            ])
            ->filter(fn ($p) => $p['stock'] > 0)
            ->values();

        return response()->json($products);
    }

    public function checkout(Request $request)
    {
        $data = $request->validate([
            'customer_id' => ['nullable', 'exists:customers,id'],
            'discount' => ['nullable', 'numeric', 'min:0'],
            'amount_paid' => ['required', 'numeric', 'min:0'],
            'payment_method' => ['required', 'in:cash,card,other'],
            'notes' => ['nullable', 'string'],
            'items' => ['required', 'array', 'min:1'],
            'items.*.product_id' => ['required', 'exists:products,id'],
            'items.*.quantity' => ['required', 'numeric', 'gt:0'],
            'items.*.unit_price' => ['required', 'numeric', 'min:0'],
            'items.*.discount' => ['nullable', 'numeric', 'min:0'],
        ]);

        try {
            $sale = $this->sales->create($data, $data['items']);
        } catch (\Throwable $e) {
            return response()->json(['message' => $e->getMessage()], 422);
        }

        return response()->json([
            'message' => 'Sale completed',
            'sale_id' => $sale->id,
            'invoice_number' => $sale->invoice_number,
            'print_url' => route('sales.receipt', $sale),
        ]);
    }
}
