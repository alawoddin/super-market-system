<?php

namespace App\Http\Controllers;

use App\Models\Expense;
use App\Models\Product;
use App\Models\Purchase;
use App\Models\Sale;
use App\Models\StockBatch;
use App\Models\Customer;
use App\Models\Supplier;
use App\Models\CashRegister;
use App\Services\ReportService;
use Illuminate\Http\Request;
use Barryvdh\DomPDF\Facade\Pdf;

class ReportController extends Controller
{
    public function __construct(private ReportService $reports)
    {
    }

    public function index()
    {
        return view('admin.reports.index');
    }

    public function sales(Request $request)
    {
        $summary = $this->reports->salesSummary($request->from, $request->to);
        $rows = Sale::query()
            ->with('customer')
            ->where('status', 'completed')
            ->when($request->from, fn ($q, $d) => $q->whereDate('sale_date', '>=', $d))
            ->when($request->to, fn ($q, $d) => $q->whereDate('sale_date', '<=', $d))
            ->latest('sale_date')
            ->paginate(30)
            ->withQueryString();

        return view('admin.reports.sales', compact('summary', 'rows'));
    }

    public function purchases(Request $request)
    {
        $rows = Purchase::query()
            ->with('supplier')
            ->when($request->from, fn ($q, $d) => $q->whereDate('purchase_date', '>=', $d))
            ->when($request->to, fn ($q, $d) => $q->whereDate('purchase_date', '<=', $d))
            ->latest('purchase_date')
            ->paginate(30)
            ->withQueryString();

        $total = Purchase::query()
            ->when($request->from, fn ($q, $d) => $q->whereDate('purchase_date', '>=', $d))
            ->when($request->to, fn ($q, $d) => $q->whereDate('purchase_date', '<=', $d))
            ->sum('grand_total');

        return view('admin.reports.purchases', compact('rows', 'total'));
    }

    public function profitLoss(Request $request)
    {
        $report = $this->reports->profitAndLoss($request->from, $request->to);

        return view('admin.reports.profit-loss', compact('report'));
    }

    public function expenses(Request $request)
    {
        $rows = Expense::query()
            ->with('category')
            ->when($request->from, fn ($q, $d) => $q->whereDate('expense_date', '>=', $d))
            ->when($request->to, fn ($q, $d) => $q->whereDate('expense_date', '<=', $d))
            ->latest('expense_date')
            ->paginate(30)
            ->withQueryString();

        $total = Expense::query()
            ->when($request->from, fn ($q, $d) => $q->whereDate('expense_date', '>=', $d))
            ->when($request->to, fn ($q, $d) => $q->whereDate('expense_date', '<=', $d))
            ->sum('amount');

        return view('admin.reports.expenses', compact('rows', 'total'));
    }

    public function inventory()
    {
        $value = $this->reports->inventoryValuation();
        $products = Product::query()->with('category')->orderBy('name')->paginate(30);
        $lowStock = Product::lowStock()->count();
        $expired = StockBatch::query()->where('quantity_remaining', '>', 0)->whereNotNull('expiration_date')->whereDate('expiration_date', '<', now())->count();

        return view('admin.reports.inventory', compact('value', 'products', 'lowStock', 'expired'));
    }

    public function debts()
    {
        $customers = Customer::all()->map(fn ($c) => [
            'name' => $c->name,
            'phone' => $c->phone,
            'balance' => $c->outstandingBalance(),
        ])->filter(fn ($c) => $c['balance'] > 0)->sortByDesc('balance');

        $suppliers = Supplier::all()->map(fn ($s) => [
            'name' => $s->name,
            'phone' => $s->phone,
            'balance' => $s->outstandingBalance(),
        ])->filter(fn ($s) => $s['balance'] > 0)->sortByDesc('balance');

        return view('admin.reports.debts', compact('customers', 'suppliers'));
    }

    public function cashRegister()
    {
        $rows = CashRegister::query()->with('user')->latest()->paginate(30);

        return view('admin.reports.cash-register', compact('rows'));
    }

    public function exportPdf(Request $request)
    {
        $type = $request->get('type', 'sales');
        $report = $this->reports->profitAndLoss($request->from, $request->to);
        $pdf = Pdf::loadView('prints.report-pdf', compact('report', 'type'));

        return $pdf->download('report-'.$type.'-'.now()->format('Ymd').'.pdf');
    }
}
