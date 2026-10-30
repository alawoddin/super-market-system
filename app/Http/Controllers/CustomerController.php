<?php

namespace App\Http\Controllers;

use App\Models\Customer;
use Illuminate\Http\Request;

class CustomerController extends Controller
{
    public function index(Request $request)
    {
        $customers = Customer::query()
            ->when($request->search, fn ($q, $s) => $q->where('name', 'like', "%{$s}%")->orWhere('phone', 'like', "%{$s}%"))
            ->latest()
            ->paginate(15)
            ->withQueryString();

        return view('admin.customers.index', compact('customers'));
    }

    public function create()
    {
        return view('admin.customers.create');
    }

    public function store(Request $request)
    {
        $data = $request->validate([
            'name' => ['required', 'string', 'max:255'],
            'phone' => ['nullable', 'string', 'max:50'],
            'address' => ['nullable', 'string'],
            'opening_balance' => ['nullable', 'numeric'],
            'notes' => ['nullable', 'string'],
        ]);

        $data['opening_balance'] = $data['opening_balance'] ?? 0;
        $data['is_active'] = true;

        Customer::query()->create($data);

        return redirect()->route('customers.index')->with(['message' => 'Customer created', 'alert-type' => 'success']);
    }

    public function show(Customer $customer)
    {
        $customer->load([
            'sales' => fn ($q) => $q->latest()->limit(20),
            'payments' => fn ($q) => $q->latest()->limit(20),
        ]);
        $balance = $customer->outstandingBalance();

        return view('admin.customers.show', compact('customer', 'balance'));
    }

    public function edit(Customer $customer)
    {
        return view('admin.customers.edit', compact('customer'));
    }

    public function update(Request $request, Customer $customer)
    {
        $data = $request->validate([
            'name' => ['required', 'string', 'max:255'],
            'phone' => ['nullable', 'string', 'max:50'],
            'address' => ['nullable', 'string'],
            'opening_balance' => ['nullable', 'numeric'],
            'notes' => ['nullable', 'string'],
            'is_active' => ['nullable', 'boolean'],
        ]);

        $customer->update([
            ...$data,
            'is_active' => $request->boolean('is_active', true),
        ]);

        return redirect()->route('customers.index')->with(['message' => 'Customer updated', 'alert-type' => 'success']);
    }

    public function destroy(Customer $customer)
    {
        if ($customer->sales()->exists()) {
            return back()->with(['message' => 'Cannot delete customer with sales history.', 'alert-type' => 'error']);
        }

        $customer->delete();

        return redirect()->route('customers.index')->with(['message' => 'Customer deleted', 'alert-type' => 'success']);
    }

    public function statement(Customer $customer)
    {
        $sales = $customer->sales()->where('status', 'completed')->latest()->get();
        $payments = $customer->payments()->latest()->get();
        $balance = $customer->outstandingBalance();

        return view('admin.customers.statement', compact('customer', 'sales', 'payments', 'balance'));
    }
}
