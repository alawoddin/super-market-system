<?php

namespace App\Http\Controllers;

use App\Models\Supplier;
use Illuminate\Http\Request;

class SupplierController extends Controller
{
    public function index(Request $request)
    {
        $suppliers = Supplier::query()
            ->when($request->search, fn ($q, $s) => $q->where('name', 'like', "%{$s}%")->orWhere('phone', 'like', "%{$s}%"))
            ->latest()
            ->paginate(15)
            ->withQueryString();

        return view('admin.suppliers.index', compact('suppliers'));
    }

    public function create()
    {
        return view('admin.suppliers.create');
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

        Supplier::query()->create($data);

        return redirect()->route('suppliers.index')->with(['message' => 'Supplier created', 'alert-type' => 'success']);
    }

    public function show(Supplier $supplier)
    {
        $supplier->load(['purchases' => fn ($q) => $q->latest()->limit(20), 'payments' => fn ($q) => $q->latest()->limit(20)]);
        $balance = $supplier->outstandingBalance();

        return view('admin.suppliers.show', compact('supplier', 'balance'));
    }

    public function edit(Supplier $supplier)
    {
        return view('admin.suppliers.edit', compact('supplier'));
    }

    public function update(Request $request, Supplier $supplier)
    {
        $data = $request->validate([
            'name' => ['required', 'string', 'max:255'],
            'phone' => ['nullable', 'string', 'max:50'],
            'address' => ['nullable', 'string'],
            'opening_balance' => ['nullable', 'numeric'],
            'notes' => ['nullable', 'string'],
            'is_active' => ['nullable', 'boolean'],
        ]);

        $supplier->update([
            ...$data,
            'is_active' => $request->boolean('is_active', true),
        ]);

        return redirect()->route('suppliers.index')->with(['message' => 'Supplier updated', 'alert-type' => 'success']);
    }

    public function destroy(Supplier $supplier)
    {
        if ($supplier->purchases()->exists()) {
            return back()->with(['message' => 'Cannot delete supplier with purchase history.', 'alert-type' => 'error']);
        }

        $supplier->delete();

        return redirect()->route('suppliers.index')->with(['message' => 'Supplier deleted', 'alert-type' => 'success']);
    }
}
