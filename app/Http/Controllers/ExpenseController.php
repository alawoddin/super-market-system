<?php

namespace App\Http\Controllers;

use App\Models\Expense;
use App\Models\ExpenseCategory;
use App\Services\CashRegisterService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

class ExpenseController extends Controller
{
    public function index(Request $request)
    {
        $expenses = Expense::query()
            ->with('category')
            ->when($request->from, fn ($q, $d) => $q->whereDate('expense_date', '>=', $d))
            ->when($request->to, fn ($q, $d) => $q->whereDate('expense_date', '<=', $d))
            ->when($request->category_id, fn ($q, $id) => $q->where('expense_category_id', $id))
            ->latest('expense_date')
            ->paginate(20)
            ->withQueryString();

        $categories = ExpenseCategory::query()->where('is_active', true)->orderBy('name')->get();

        return view('admin.expenses.index', compact('expenses', 'categories'));
    }

    public function create()
    {
        $categories = ExpenseCategory::query()->where('is_active', true)->orderBy('name')->get();

        return view('admin.expenses.create', compact('categories'));
    }

    public function store(Request $request, CashRegisterService $cash)
    {
        $data = $request->validate([
            'expense_category_id' => ['required', 'exists:expense_categories,id'],
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
        $categories = ExpenseCategory::query()->where('is_active', true)->orderBy('name')->get();

        return view('admin.expenses.edit', compact('expense', 'categories'));
    }

    public function update(Request $request, Expense $expense)
    {
        $data = $request->validate([
            'expense_category_id' => ['required', 'exists:expense_categories,id'],
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
