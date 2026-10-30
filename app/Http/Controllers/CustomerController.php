<?php

namespace App\Http\Controllers;

use App\Models\Customer;
use App\Support\DataTable;
use Illuminate\Http\Request;

class CustomerController extends Controller
{
    public function index()
    {
        return view('admin.customers.index');
    }

    public function datatable(Request $request)
    {
        $search = trim((string) data_get($request->input('search'), 'value', ''));
        $status = $request->input('status');

        $base = Customer::query()
            ->withSum(['sales as sales_due' => fn ($q) => $q->where('status', 'completed')], 'remaining_balance');

        $filtered = (clone $base)
            ->when($search !== '', function ($q) use ($search) {
                $q->where(function ($inner) use ($search) {
                    $inner->where('name', 'like', "%{$search}%")
                        ->orWhere('phone', 'like', "%{$search}%")
                        ->orWhere('address', 'like', "%{$search}%");
                });
            })
            ->when($status !== null && $status !== '', fn ($q) => $q->where('is_active', (int) $status));

        return DataTable::response(
            $request,
            $base,
            $filtered,
            [0 => 'id', 1 => 'name', 2 => 'phone', 3 => 'opening_balance', 4 => 'is_active'],
            function (Customer $c, int $row) {
                $debt = round((float) $c->opening_balance + (float) ($c->sales_due ?? 0), 2);

                return [
                    'DT_RowIndex' => $row,
                    'name' => e($c->name).($c->address ? '<div class="small text-muted">'.e($c->address).'</div>' : ''),
                    'phone' => e($c->phone ?: '—'),
                    'debt' => '<span class="'.($debt > 0 ? 'text-danger fw-semibold' : '').'">'.money($debt).'</span>',
                    'status' => '<span class="badge bg-'.($c->is_active ? 'success' : 'secondary').'">'.($c->is_active ? 'Active' : 'Off').'</span>',
                    'actions' => '<div class="text-nowrap text-end">'
                        .'<a href="'.route('customers.show', $c).'" class="btn btn-sm btn-outline-secondary me-1">View</a>'
                        .'<a href="'.route('customers.statement', $c).'" class="btn btn-sm btn-outline-info me-1">Statement</a>'
                        .'<a href="'.route('customers.edit', $c).'" class="btn btn-sm btn-outline-primary">Edit</a></div>',
                ];
            },
            'name'
        );
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
