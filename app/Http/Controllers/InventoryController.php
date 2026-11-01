<?php

namespace App\Http\Controllers;

use App\Models\Product;
use App\Models\StockBatch;
use App\Models\StockMovement;
use App\Services\InventoryService;
use Illuminate\Http\Request;

class InventoryController extends Controller
{
    public function __construct(private InventoryService $inventory)
    {
    }

    public function index(Request $request)
    {
        $products = Product::query()
            ->search($request->search)
            ->when($request->stock === 'low', fn ($q) => $q->lowStock())
            ->orderBy('name')
            ->paginate(20)
            ->withQueryString();

        return view('admin.inventory.index', compact('products'));
    }

    public function batches(Request $request)
    {
        $batches = StockBatch::query()
            ->with('product')
            ->when($request->filter === 'expired', fn ($q) => $q->whereNotNull('expiration_date')->whereDate('expiration_date', '<', now()))
            ->when($request->filter === 'expiring', function ($q) {
                $days = (int) (setting('expiration_alert_days') ?? 7);
                $q->whereNotNull('expiration_date')
                    ->whereDate('expiration_date', '>=', now())
                    ->whereDate('expiration_date', '<=', now()->addDays($days));
            })
            ->where('quantity_remaining', '>', 0)
            ->fefo()
            ->paginate(20)
            ->withQueryString();

        return view('admin.inventory.batches', compact('batches'));
    }

    public function movements(Request $request)
    {
        $movements = StockMovement::query()
            ->with(['product', 'user'])
            ->when($request->product_id, fn ($q, $id) => $q->where('product_id', $id))
            ->latest()
            ->paginate(30)
            ->withQueryString();

        return view('admin.inventory.movements', compact('movements'));
    }

    public function adjust(Request $request, Product $product)
    {
        $data = $request->validate([
            'stock_quantity' => ['required', 'numeric', 'min:0'],
            'notes' => ['nullable', 'string'],
        ]);

        try {
            $this->inventory->adjust($product, (float) $data['stock_quantity'], $data['notes'] ?? 'Manual adjustment');
        } catch (\Throwable $e) {
            return back()->with(['message' => $e->getMessage(), 'alert-type' => 'error']);
        }

        return back()->with(['message' => 'Stock adjusted', 'alert-type' => 'success']);
    }
}
