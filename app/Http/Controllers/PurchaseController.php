<?php

namespace App\Http\Controllers;

use App\Models\Product;
use App\Models\Purchase;
use App\Models\Supplier;
use App\Services\PurchaseService;
use Illuminate\Http\Request;

class PurchaseController extends Controller
{
    public function __construct(private PurchaseService $purchases)
    {
    }

    public function index(Request $request)
    {
        $purchases = Purchase::query()
            ->with('supplier')
            ->when($request->search, function ($q, $s) {
                $q->where(function ($inner) use ($s) {
                    $inner->where('invoice_number', 'like', "%{$s}%")
                        ->orWhereHas('supplier', fn ($sq) => $sq->where('name', 'like', "%{$s}%"));
                });
            })
            ->when($request->supplier_id, fn ($q, $id) => $q->where('supplier_id', $id))
            ->when($request->payment_status, fn ($q, $status) => $q->where('payment_status', $status))
            ->when($request->from, fn ($q, $d) => $q->whereDate('purchase_date', '>=', $d))
            ->when($request->to, fn ($q, $d) => $q->whereDate('purchase_date', '<=', $d))
            ->latest('purchase_date')
            ->latest('id')
            ->paginate(15)
            ->withQueryString();

        $suppliers = Supplier::query()->orderBy('name')->get(['id', 'name']);

        return view('admin.purchases.index', compact('purchases', 'suppliers'));
    }

    public function create()
    {
        $suppliers = Supplier::query()->where('is_active', true)->orderBy('name')->get();
        $products = Product::query()->active()->orderBy('name')->get([
            'id', 'name', 'barcode', 'purchase_price', 'selling_price', 'unit',
        ]);
        $invoice = $this->purchases->nextInvoiceNumber();

        return view('admin.purchases.create', compact('suppliers', 'products', 'invoice'));
    }

    public function lookup(Request $request)
    {
        $code = trim((string) $request->get('barcode', $request->get('q', '')));
        if ($code === '') {
            return response()->json(['message' => 'Empty barcode'], 422);
        }

        $product = Product::query()
            ->active()
            ->where(function ($q) use ($code) {
                $q->where('barcode', $code)
                    ->orWhere('sku', $code)
                    ->orWhere('name', 'like', "%{$code}%");
            })
            ->first();

        if (! $product) {
            return response()->json(['message' => 'Product not found. Create the product first.'], 404);
        }

        return response()->json([
            'id' => $product->id,
            'name' => $product->name,
            'barcode' => $product->barcode,
            'unit' => $product->unit,
            'purchase_price' => (float) $product->purchase_price,
            'selling_price' => (float) $product->selling_price,
        ]);
    }

    public function store(Request $request)
    {
        $rawJson = $request->input('items_json');
        if (is_string($rawJson) && $rawJson !== '' && ! $request->filled('items')) {
            $decoded = json_decode($rawJson, true);
            if (is_array($decoded)) {
                $decoded = array_map(function (array $item) {
                    foreach (['expiration_date', 'batch_number', 'selling_price'] as $key) {
                        if (array_key_exists($key, $item) && $item[$key] === '') {
                            $item[$key] = null;
                        }
                    }

                    return $item;
                }, $decoded);
                $request->merge(['items' => $decoded]);
            }
        }

        $data = $request->validate([
            'supplier_id' => ['required', 'exists:suppliers,id'],
            'invoice_number' => ['nullable', 'string', 'max:50'],
            'purchase_date' => ['required', 'date'],
            'discount' => ['nullable', 'numeric', 'min:0'],
            'amount_paid' => ['nullable', 'numeric', 'min:0'],
            'payment_method' => ['nullable', 'in:cash'],
            'notes' => ['nullable', 'string'],
            'items' => ['required', 'array', 'min:1'],
            'items.*.product_id' => ['required', 'exists:products,id'],
            'items.*.quantity' => ['required', 'numeric', 'gt:0'],
            'items.*.unit_cost' => ['required', 'numeric', 'min:0'],
            'items.*.selling_price' => ['nullable', 'numeric', 'min:0'],
            'items.*.cartons' => ['nullable', 'numeric', 'min:0'],
            'items.*.units_per_carton' => ['nullable', 'numeric', 'min:0'],
            'items.*.carton_price' => ['nullable', 'numeric', 'min:0'],
            'items.*.discount' => ['nullable', 'numeric', 'min:0'],
            'items.*.expiration_date' => ['nullable', 'date'],
            'items.*.batch_number' => ['nullable', 'string'],
        ], [
            'items.required' => 'Add at least one product to the purchase list before saving.',
            'items.min' => 'Add at least one product to the purchase list before saving.',
            'supplier_id.required' => 'Please select a supplier.',
        ]);

        $invoice = trim((string) ($data['invoice_number'] ?? ''));
        if ($invoice === '' || Purchase::withTrashed()->where('invoice_number', $invoice)->exists()) {
            $data['invoice_number'] = $this->purchases->nextInvoiceNumber();
        }

        try {
            $purchase = $this->purchases->create($data, $data['items']);
        } catch (\Throwable $e) {
            return back()->withInput()->with(['message' => $e->getMessage(), 'alert-type' => 'error']);
        }

        return redirect()->route('purchases.show', $purchase)->with(['message' => 'Purchase saved', 'alert-type' => 'success']);
    }

    public function show(Purchase $purchase)
    {
        $purchase->load(['supplier', 'items.product', 'payments', 'user']);

        return view('admin.purchases.show', compact('purchase'));
    }

    public function print(Purchase $purchase)
    {
        $purchase->load(['supplier', 'items.product', 'user']);

        return view('prints.purchase', [
            'purchase' => $purchase,
            'settings' => \App\Models\Setting::current(),
        ]);
    }

    public function addPayment(Request $request, Purchase $purchase)
    {
        $data = $request->validate([
            'amount' => ['required', 'numeric', 'gt:0'],
            'notes' => ['nullable', 'string'],
        ]);

        try {
            $this->purchases->recordPayment($purchase, (float) $data['amount'], 'cash', $data['notes'] ?? null);
        } catch (\Throwable $e) {
            return back()->with(['message' => $e->getMessage(), 'alert-type' => 'error']);
        }

        return back()->with(['message' => 'Payment recorded', 'alert-type' => 'success']);
    }

    public function edit(Purchase $purchase)
    {
        return redirect()->route('purchases.show', $purchase)->with([
            'message' => 'Confirmed purchases cannot be edited. Use returns or payments instead.',
            'alert-type' => 'warning',
        ]);
    }

    public function update(Request $request, Purchase $purchase)
    {
        return $this->edit($purchase);
    }

    public function destroy(Purchase $purchase)
    {
        return back()->with(['message' => 'Purchases cannot be deleted. Use purchase returns.', 'alert-type' => 'error']);
    }
}
