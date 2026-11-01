<?php

namespace App\Http\Controllers;

use App\Models\Expense;
use App\Services\CashRegisterService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

class ExpenseController extends Controller
{
    public function index(Request $request)
    {
        $expenses = Expense::query()
            ->when($request->from, fn ($q, $d) => $q->whereDate('expense_date', '>=', $d))
            ->when($request->to, fn ($q, $d) => $q->whereDate('expense_date', '<=', $d))
            ->latest('expense_date')
            ->paginate(20)
            ->withQueryString();

        return view('admin.expenses.index', compact('expenses'));
    }

    public function create()
    {
        return view('admin.expenses.create');
    }

    public function store(Request $request, CashRegisterService $cash)
    {
        $data = $request->validate([
            'title' => ['required', 'string', 'max:255'],
            'amount' => ['required', 'numeric', 'gt:0'],
            'expense_date' => ['required', 'date'],
            'description' => ['nullable', 'string'],
        ]);

        $data['payment_method'] = 'cash';
        $data['user_id'] = Auth::id();
        $expense = Expense::query()->create($data);
        $cash->addCashExpense((float) $expense->amount, 'cash');

        return redirect()->route('expenses.index')->with(['message' => 'Expense recorded', 'alert-type' => 'success']);
    }

    public function edit(Expense $expense)
    {
        return view('admin.expenses.edit', compact('expense'));
    }

    public function update(Request $request, Expense $expense)
    {
        $data = $request->validate([
            'title' => ['required', 'string', 'max:255'],
            'amount' => ['required', 'numeric', 'gt:0'],
            'expense_date' => ['required', 'date'],
            'description' => ['nullable', 'string'],
        ]);

        $data['payment_method'] = 'cash';
        $expense->update($data);

        return redirect()->route('expenses.index')->with(['message' => 'Expense updated', 'alert-type' => 'success']);
    }

    public function destroy(Expense $expense)
    {
        $expense->delete();

        return redirect()->route('expenses.index')->with(['message' => 'Expense deleted', 'alert-type' => 'success']);
    }

    public function show(Expense $expense)
    {
        return redirect()->route('expenses.edit', $expense);
    }
}
