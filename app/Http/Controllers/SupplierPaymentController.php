<?php

namespace App\Http\Controllers;

use App\Models\Purchase;
use App\Models\PurchasePayment;
use App\Models\Setting;
use App\Models\Supplier;
use App\Services\PurchaseService;
use Illuminate\Http\Request;

class SupplierPaymentController extends Controller
{
    public function __construct(private PurchaseService $purchases)
    {
    }

    public function index()
    {
        $payments = PurchasePayment::query()->with(['supplier', 'purchase'])->latest()->paginate(20);

        return view('admin.payments.suppliers.index', compact('payments'));
    }

    public function create(Request $request)
    {
        $suppliers = Supplier::query()->where('is_active', true)->orderBy('name')->get();
        $purchase = $request->purchase_id ? Purchase::find($request->purchase_id) : null;

        return view('admin.payments.suppliers.create', compact('suppliers', 'purchase'));
    }

    public function store(Request $request)
    {
        $data = $request->validate([
            'purchase_id' => ['required', 'exists:purchases,id'],
            'amount' => ['required', 'numeric', 'gt:0'],
            'notes' => ['nullable', 'string'],
        ]);

        $purchase = Purchase::findOrFail($data['purchase_id']);

        try {
            $payment = $this->purchases->recordPayment($purchase, (float) $data['amount'], 'cash', $data['notes'] ?? null);
        } catch (\Throwable $e) {
            return back()->withInput()->with(['message' => $e->getMessage(), 'alert-type' => 'error']);
        }

        return redirect()->route('supplier-payments.receipt', $payment)->with(['message' => 'Payment recorded', 'alert-type' => 'success']);
    }

    public function receipt(PurchasePayment $supplierPayment)
    {
        $supplierPayment->load(['supplier', 'purchase', 'user']);

        return view('prints.payment-receipt', [
            'payment' => $supplierPayment,
            'settings' => Setting::current(),
            'type' => 'supplier',
        ]);
    }
}
