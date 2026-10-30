<?php

namespace App\Http\Controllers;

use App\Models\Sale;
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
        $sale = null;
        if ($request->sale_id) {
            $sale = Sale::query()->with('items.product')->findOrFail($request->sale_id);
        }

        return view('admin.returns.sales.create', compact('sale'));
    }

    public function store(Request $request)
    {
        $data = $request->validate([
            'sale_id' => ['required', 'exists:sales,id'],
            'restock' => ['nullable', 'boolean'],
            'notes' => ['nullable', 'string'],
            'items' => ['required', 'array', 'min:1'],
            'items.*.sale_item_id' => ['required', 'exists:sale_items,id'],
            'items.*.quantity' => ['required', 'numeric', 'gt:0'],
        ]);

        $sale = Sale::findOrFail($data['sale_id']);

        try {
            $return = $this->returns->createSaleReturn($sale, $data['items'], [
                'refund_method' => 'cash',
                'restock' => $request->boolean('restock', true),
                'notes' => $data['notes'] ?? null,
            ]);
        } catch (\Throwable $e) {
            return back()->withInput()->with(['message' => $e->getMessage(), 'alert-type' => 'error']);
        }

        return redirect()->route('sale-returns.index')->with(['message' => 'Return '.$return->return_number.' saved', 'alert-type' => 'success']);
    }

    public function show(SaleReturn $saleReturn)
    {
        $saleReturn->load(['sale', 'items.product', 'user']);

        return view('admin.returns.sales.show', ['return' => $saleReturn]);
    }
}
