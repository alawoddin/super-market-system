<?php

namespace App\Http\Controllers;

use App\Models\Customer;
use App\Models\SalePayment;
use App\Models\Setting;
use App\Services\SalesService;
use Illuminate\Http\Request;

class CustomerPaymentController extends Controller
{
    public function __construct(private SalesService $sales)
    {
    }

    public function index()
    {
        $payments = SalePayment::query()->with(['customer', 'sale'])->latest()->paginate(20);

        return view('admin.payments.customers.index', compact('payments'));
    }

    public function create(Request $request)
    {
        $customers = Customer::query()->where('is_active', true)->orderBy('name')->get();
        $customer = $request->customer_id ? Customer::find($request->customer_id) : null;

        return view('admin.payments.customers.create', compact('customers', 'customer'));
    }

    public function store(Request $request)
    {
        $data = $request->validate([
            'customer_id' => ['required', 'exists:customers,id'],
            'sale_id' => ['nullable', 'exists:sales,id'],
            'amount' => ['required', 'numeric', 'gt:0'],
            'payment_date' => ['required', 'date'],
            'notes' => ['nullable', 'string'],
        ]);

        $data['payment_method'] = 'cash';

        try {
            $payment = $this->sales->recordCustomerPayment($data);
        } catch (\Throwable $e) {
            return back()->withInput()->withErrors(['amount' => $e->getMessage()]);
        }

        // After debt payment, return to that customer's page.
        return redirect()
            ->route('customers.show', $payment->customer_id)
            ->with('last_payment_id', $payment->id);
    }

    public function receipt(SalePayment $customerPayment)
    {
        $customerPayment->load(['customer', 'sale', 'user']);

        return view('prints.payment-receipt', [
            'payment' => $customerPayment,
            'settings' => Setting::current(),
            'type' => 'customer',
            'backUrl' => $customerPayment->customer_id
                ? route('customers.show', $customerPayment->customer_id)
                : route('customers.index'),
        ]);
    }
}
