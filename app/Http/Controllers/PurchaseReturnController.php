<?php

namespace App\Http\Controllers;

use App\Models\Purchase;
use App\Models\PurchaseReturn;
use App\Services\ReturnService;
use Illuminate\Http\Request;

class PurchaseReturnController extends Controller
{
    public function __construct(private ReturnService $returns)
    {
    }

    public function index()
    {
        $returns = PurchaseReturn::query()->with(['purchase', 'supplier'])->latest()->paginate(20);

        return view('admin.returns.purchases.index', compact('returns'));
    }

    public function create(Request $request)
    {
        $purchase = null;
        if ($request->purchase_id) {
            $purchase = Purchase::query()->with('items.product', 'items.stockBatch')->findOrFail($request->purchase_id);
        }

        return view('admin.returns.purchases.create', compact('purchase'));
    }

    public function store(Request $request)
    {
        $data = $request->validate([
            'purchase_id' => ['required', 'exists:purchases,id'],
            'notes' => ['nullable', 'string'],
            'items' => ['required', 'array', 'min:1'],
            'items.*.purchase_item_id' => ['required', 'exists:purchase_items,id'],
            'items.*.quantity' => ['required', 'numeric', 'gt:0'],
        ]);

        $purchase = Purchase::findOrFail($data['purchase_id']);

        try {
            $return = $this->returns->createPurchaseReturn($purchase, $data['items'], $data['notes'] ?? null);
        } catch (\Throwable $e) {
            return back()->withInput()->with(['message' => $e->getMessage(), 'alert-type' => 'error']);
        }

        return redirect()->route('purchase-returns.index')->with(['message' => 'Return '.$return->return_number.' saved', 'alert-type' => 'success']);
    }
}
