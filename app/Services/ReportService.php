<?php

namespace App\Services;

use App\Models\Expense;
use App\Models\Product;
use App\Models\Purchase;
use App\Models\Sale;
use App\Models\StockBatch;
use App\Models\Customer;
use App\Models\Supplier;
use Carbon\Carbon;
use Illuminate\Support\Facades\DB;

class ReportService
{
    public function salesSummary(?string $from = null, ?string $to = null): array
    {
        [$from, $to] = $this->range($from, $to);

        $sales = Sale::query()
            ->where('status', 'completed')
            ->whereBetween('sale_date', [$from, $to]);

        return [
            'from' => $from,
            'to' => $to,
            'count' => (clone $sales)->count(),
            'revenue' => (float) (clone $sales)->sum('grand_total'),
            'cash_received' => (float) (clone $sales)->sum('amount_paid'),
            'receivables' => (float) (clone $sales)->sum('remaining_balance'),
            'cogs' => (float) (clone $sales)->sum('cogs'),
            'discounts' => (float) (clone $sales)->sum('discount'),
            'profit' => (float) (clone $sales)->sum(DB::raw('grand_total - cogs')),
        ];
    }

    public function profitAndLoss(?string $from = null, ?string $to = null): array
    {
        $sales = $this->salesSummary($from, $to);
        [$from, $to] = $this->range($from, $to);

        $expenses = (float) Expense::query()
            ->whereBetween('expense_date', [$from->toDateString(), $to->toDateString()])
            ->sum('amount');

        return [
            ...$sales,
            'expenses' => $expenses,
            'net_profit' => round($sales['profit'] - $expenses, 2),
        ];
    }

    public function dailySalesChart(int $days = 14): array
    {
        $start = now()->subDays($days - 1)->startOfDay();

        $rows = Sale::query()
            ->selectRaw('DATE(sale_date) as day, SUM(grand_total) as total')
            ->where('status', 'completed')
            ->where('sale_date', '>=', $start)
            ->groupBy('day')
            ->pluck('total', 'day');

        $labels = [];
        $data = [];
        for ($i = 0; $i < $days; $i++) {
            $day = $start->copy()->addDays($i)->toDateString();
            $labels[] = $day;
            $data[] = (float) ($rows[$day] ?? 0);
        }

        return compact('labels', 'data');
    }

    public function monthlySalesChart(int $months = 12): array
    {
        $start = now()->subMonths($months - 1)->startOfMonth();

        $rows = Sale::query()
            ->selectRaw("DATE_FORMAT(sale_date, '%Y-%m') as month, SUM(grand_total) as total")
            ->where('status', 'completed')
            ->where('sale_date', '>=', $start)
            ->groupBy('month')
            ->pluck('total', 'month');

        $labels = [];
        $data = [];
        for ($i = 0; $i < $months; $i++) {
            $month = $start->copy()->addMonths($i)->format('Y-m');
            $labels[] = $month;
            $data[] = (float) ($rows[$month] ?? 0);
        }

        return compact('labels', 'data');
    }

    public function inventoryValuation(): float
    {
        return (float) StockBatch::query()
            ->where('quantity_remaining', '>', 0)
            ->sum(DB::raw('quantity_remaining * purchase_price'));
    }

    public function dashboardStats(): array
    {
        $today = now()->toDateString();

        $todaySales = Sale::query()->where('status', 'completed')->whereDate('sale_date', $today);
        $todayPurchases = Purchase::query()->where('status', 'confirmed')->whereDate('purchase_date', $today);
        $todayExpenses = Expense::query()->whereDate('expense_date', $today);

        $expiringDays = (int) (setting('expiration_alert_days') ?? 7);

        return [
            'today_sales' => (float) (clone $todaySales)->sum('grand_total'),
            'today_purchases' => (float) (clone $todayPurchases)->sum('grand_total'),
            'today_expenses' => (float) (clone $todayExpenses)->sum('amount'),
            'today_profit' => (float) (clone $todaySales)->sum(DB::raw('grand_total - cogs')) - (float) (clone $todayExpenses)->sum('amount'),
            'products' => Product::count(),
            'categories' => \App\Models\Category::count(),
            'customers' => Customer::count(),
            'suppliers' => Supplier::count(),
            'stock_value' => $this->inventoryValuation(),
            'low_stock' => Product::lowStock()->count(),
            'expiring' => StockBatch::query()
                ->where('quantity_remaining', '>', 0)
                ->whereNotNull('expiration_date')
                ->whereDate('expiration_date', '>=', now()->toDateString())
                ->whereDate('expiration_date', '<=', now()->addDays($expiringDays)->toDateString())
                ->count(),
            'expired' => StockBatch::query()
                ->where('quantity_remaining', '>', 0)
                ->whereNotNull('expiration_date')
                ->whereDate('expiration_date', '<', now()->toDateString())
                ->count(),
            'customer_debts' => (float) Sale::query()->where('status', 'completed')->sum('remaining_balance')
                + (float) Customer::sum('opening_balance'),
            'supplier_debts' => Supplier::all()->sum(fn ($s) => $s->outstandingBalance()),
        ];
    }

    private function range(?string $from, ?string $to): array
    {
        $fromDate = $from ? Carbon::parse($from)->startOfDay() : now()->startOfDay();
        $toDate = $to ? Carbon::parse($to)->endOfDay() : now()->endOfDay();

        return [$fromDate, $toDate];
    }
}
