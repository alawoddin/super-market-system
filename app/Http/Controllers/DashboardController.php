<?php

namespace App\Http\Controllers;

use App\Models\Expense;
use App\Models\Product;
use App\Models\Purchase;
use App\Models\Sale;
use App\Models\StockBatch;
use App\Services\ReportService;

class DashboardController extends Controller
{
    public function __invoke(ReportService $reports)
    {
        $stats = $reports->dashboardStats();
        $daily = $reports->dailySalesChart(14);
        $monthly = $reports->monthlySalesChart(12);

        $recentSales = Sale::query()->with('customer')->latest('sale_date')->limit(8)->get();
        $recentPurchases = Purchase::query()->with('supplier')->latest('purchase_date')->limit(8)->get();
        $recentExpenses = Expense::query()->with('category')->latest('expense_date')->limit(8)->get();
        $lowStock = Product::query()->lowStock()->orderBy('stock_quantity')->limit(8)->get();
        $expiring = StockBatch::query()
            ->with('product')
            ->where('quantity_remaining', '>', 0)
            ->whereNotNull('expiration_date')
            ->whereDate('expiration_date', '>=', now())
            ->whereDate('expiration_date', '<=', now()->addDays((int) (\setting('expiration_alert_days') ?? 7)))
            ->orderBy('expiration_date')
            ->limit(8)
            ->get();

        return view('admin.dashboard', compact(
            'stats', 'daily', 'monthly', 'recentSales', 'recentPurchases',
            'recentExpenses', 'lowStock', 'expiring'
        ));
    }
}
