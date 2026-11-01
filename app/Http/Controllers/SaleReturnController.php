<?php

namespace App\Http\Controllers;

use App\Models\Product;
use App\Models\SaleItem;
use App\Models\SaleReturn;
use App\Services\ReturnService;
use Illuminate\Http\Request;

class SaleReturnController extends Controller
{
    public function __construct(private ReturnService $returns)
    {
    }

    public function index()
    {
        $returns = SaleReturn::query()->with(['sale', 'user'])->latest()->paginate(20);

        return view('admin.returns.sales.index', compact('returns'));
    }

    public function create(Request $request)
    {
        $q = trim((string) $request->get('q', ''));
        $saleId = $request->get('sale_id');

        $lines = collect();

        if ($saleId || $q !== '') {
            $productIds = $q !== ''
                ? Product::withTrashed()->search($q)->limit(50)->pluck('id')
                : collect();

            // Sold lines of the scanned product (or of the typed invoice) that can still be returned
            $lines = SaleItem::query()
                ->with(['sale', 'product' => fn ($p) => $p->withTrashed()])
                ->whereColumn('returned_quantity', '<', 'quantity')
                ->whereHas('sale', fn ($s) => $s->where('status', 'completed'))
                ->where(function ($w) use ($saleId, $q, $productIds) {
                    if ($saleId) {
                        $w->where('sale_id', $saleId);

                        return;
                    }
                    $w->whereIn('product_id', $productIds)
                        ->orWhereHas('sale', fn ($s) => $s->where('invoice_number', $q));
                })
                ->latest('id')
                ->limit(30)
                ->get();
        }

        return view('admin.returns.sales.create', compact('q', 'lines'));
    }

    public function store(Request $request)
    {
        $data = $request->validate([
            'sale_item_id' => ['required', 'exists:sale_items,id'],
            'quantity' => ['required', 'numeric', 'gt:0'],
            'restock' => ['nullable', 'boolean'],
            'notes' => ['nullable', 'string'],
        ]);

        $item = SaleItem::query()->with(['sale', 'product'])->findOrFail($data['sale_item_id']);
        $restock = $request->boolean('restock');

        try {
            $return = $this->returns->createSaleReturn($item->sale, [
                ['sale_item_id' => $item->id, 'quantity' => $data['quantity']],
            ], [
                'refund_method' => 'cash',
                'restock' => $restock,
                'notes' => $data['notes'] ?? null,
            ]);
        } catch (\Throwable $e) {
            return back()->withInput()->with(['message' => $e->getMessage(), 'alert-type' => 'error']);
        }

        $message = 'Returned '.qty_format($data['quantity']).' × '.$item->product?->name
            .'. Give back '.money($return->refund_amount).' to the customer.'
            .($restock ? ' Added back to stock.' : ' Not added to stock.');

        return redirect()->route('sale-returns.create')->with(['message' => $message, 'alert-type' => 'success']);
    }

    public function show(SaleReturn $saleReturn)
    {
        $saleReturn->load(['sale', 'items.product', 'user']);

        return view('admin.returns.sales.show', ['return' => $saleReturn]);
    }
}
