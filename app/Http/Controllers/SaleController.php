<?php

namespace App\Http\Controllers;

use App\Models\Sale;
use App\Models\Setting;
use App\Services\SalesService;
use Illuminate\Http\Request;

class SaleController extends Controller
{
    public function __construct(private SalesService $sales)
    {
    }

    public function index(Request $request)
    {
        $sales = Sale::query()
            ->with(['customer', 'user'])
            ->when($request->search, fn ($q, $s) => $q->where('invoice_number', 'like', "%{$s}%"))
            ->when($request->from, fn ($q, $d) => $q->whereDate('sale_date', '>=', $d))
            ->when($request->to, fn ($q, $d) => $q->whereDate('sale_date', '<=', $d))
            ->when($request->status, fn ($q, $s) => $q->where('status', $s))
            ->latest('sale_date')
            ->paginate(20)
            ->withQueryString();

        return view('admin.sales.index', compact('sales'));
    }

    public function show(Sale $sale)
    {
        $sale->load(['customer', 'user', 'items.product', 'payments', 'returns']);

        return view('admin.sales.show', compact('sale'));
    }

    public function receipt(Sale $sale)
    {
        $sale->load(['customer', 'user', 'items.product', 'payments']);

        return view('prints.receipt', [
            'sale' => $sale,
            'settings' => Setting::current(),
        ]);
    }

    public function cancel(Sale $sale)
    {
        try {
            $this->sales->cancel($sale);
        } catch (\Throwable $e) {
            return back()->with(['message' => $e->getMessage(), 'alert-type' => 'error']);
        }

        return back()->with(['message' => 'Sale cancelled and stock restored', 'alert-type' => 'success']);
    }

    public function create()
    {
        return redirect()->route('pos.index');
    }

    public function store(Request $request)
    {
        return redirect()->route('pos.index');
    }

    public function edit(Sale $sale)
    {
        return redirect()->route('sales.show', $sale);
    }

    public function update(Request $request, Sale $sale)
    {
        return redirect()->route('sales.show', $sale);
    }

    public function destroy(Sale $sale)
    {
        return back()->with(['message' => 'Sales cannot be deleted permanently.', 'alert-type' => 'error']);
    }
}
