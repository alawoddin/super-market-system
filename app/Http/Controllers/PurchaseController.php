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

    public function index()
    {
        $suppliers = Supplier::query()->orderBy('name')->get(['id', 'name']);

        return view('admin.purchases.index', compact('suppliers'));
    }

    public function datatable(Request $request)
    {
        $draw = (int) $request->input('draw', 1);
        $start = max(0, (int) $request->input('start', 0));
        $length = (int) $request->input('length', 25);
        if ($length < 1 || $length > 200) {
            $length = 25;
        }

        $search = trim((string) data_get($request->input('search'), 'value', ''));
        $supplierId = $request->input('supplier_id');
        $paymentStatus = $request->input('payment_status');
        $from = $request->input('from');
        $to = $request->input('to');

        $base = Purchase::query()->with(['supplier'])->withCount('items');

        $recordsTotal = (clone $base)->count();

        $filtered = (clone $base)
            ->when($search !== '', function ($q) use ($search) {
                $q->where(function ($inner) use ($search) {
                    $inner->where('invoice_number', 'like', "%{$search}%")
                        ->orWhere('notes', 'like', "%{$search}%")
                        ->orWhereHas('supplier', fn ($sq) => $sq->where('name', 'like', "%{$search}%"));
                });
            })
            ->when($supplierId, fn ($q, $id) => $q->where('supplier_id', $id))
            ->when($paymentStatus, fn ($q, $status) => $q->where('payment_status', $status))
            ->when($from, fn ($q, $d) => $q->whereDate('purchase_date', '>=', $d))
            ->when($to, fn ($q, $d) => $q->whereDate('purchase_date', '<=', $d));

        $recordsFiltered = (clone $filtered)->count();

        $orderColumnIndex = (int) data_get($request->input('order'), '0.column', 3);
        $orderDir = strtolower((string) data_get($request->input('order'), '0.dir', 'desc')) === 'asc' ? 'asc' : 'desc';
        $orderMap = [
            0 => 'id',
            1 => 'invoice_number',
            2 => 'supplier_id',
            3 => 'purchase_date',
            4 => 'items_count',
            5 => 'grand_total',
            6 => 'amount_paid',
            7 => 'remaining_balance',
            8 => 'payment_status',
        ];
        $orderColumn = $orderMap[$orderColumnIndex] ?? 'purchase_date';

        $rows = $filtered
            ->orderBy($orderColumn, $orderDir)
            ->orderByDesc('id')
            ->skip($start)
            ->take($length)
            ->get();

        $data = $rows->values()->map(function (Purchase $p, int $index) use ($start) {
            $badge = match ($p->payment_status) {
                'paid' => 'success',
                'partial' => 'warning',
                default => 'danger',
            };

            return [
                'DT_RowIndex' => $start + $index + 1,
                'invoice' => '<a href="'.route('purchases.show', $p).'" class="fw-semibold text-decoration-none">'.e($p->invoice_number).'</a>',
                'supplier' => e($p->supplier?->name ?? '—'),
                'date' => $p->purchase_date?->format('Y-m-d'),
                'items' => (int) $p->items_count,
                'total' => money($p->grand_total),
                'paid' => money($p->amount_paid),
                'due' => '<span class="'.($p->remaining_balance > 0 ? 'text-danger fw-semibold' : '').'">'.money($p->remaining_balance).'</span>',
                'status' => '<span class="badge bg-'.$badge.'">'.e(ucfirst((string) $p->payment_status)).'</span>',
                'actions' => '<div class="text-nowrap text-end">'
                    .'<a href="'.route('purchases.show', $p).'" class="btn btn-sm btn-outline-secondary me-1">View</a>'
                    .'<a href="'.route('purchases.print', $p).'" target="_blank" class="btn btn-sm btn-outline-dark">Print</a>'
                    .'</div>',
            ];
        });

        return response()->json([
            'draw' => $draw,
            'recordsTotal' => $recordsTotal,
            'recordsFiltered' => $recordsFiltered,
            'data' => $data,
        ]);
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
        if (is_string($rawJson) && $rawJson !== '' && ! $request->has('items')) {
            $decoded = json_decode($rawJson, true);
            if (is_array($decoded)) {
                $request->merge(['items' => $decoded]);
            }
        }

        if ($request->has('items') && is_array($request->input('items'))) {
            $cleaned = [];
            foreach ($request->input('items') as $item) {
                if (! is_array($item)) {
                    continue;
                }
                foreach (['expiration_date', 'batch_number', 'selling_price'] as $key) {
                    if (array_key_exists($key, $item) && $item[$key] === '') {
                        $item[$key] = null;
                    }
                }
                $cleaned[] = $item;
            }
            $request->merge(['items' => $cleaned]);
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
