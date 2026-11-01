<?php

namespace App\Services;

use App\Models\Expense;
use App\Models\Product;
use App\Models\Sale;
use App\Models\StockBatch;
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

        $allSales = Sale::query()->where('status', 'completed');
        // Stock bought = value of stock added from the product form (sales returns excluded)
        $allPurchases = StockBatch::query()->where('batch_number', 'not like', 'RET-%');
        $allExpenses = Expense::query();

        $todaySales = (clone $allSales)->whereDate('sale_date', $today);
        $todayPurchases = (clone $allPurchases)->whereDate('created_at', $today);
        $todayExpenses = (clone $allExpenses)->whereDate('expense_date', $today);

        $todaySalesTotal = (float) (clone $todaySales)->sum('grand_total');
        $todayPurchasesTotal = (float) (clone $todayPurchases)->sum(DB::raw('quantity_received * purchase_price'));
        $todayExpensesTotal = (float) (clone $todayExpenses)->sum('amount');
        $todayGrossProfit = (float) (clone $todaySales)->sum(DB::raw('grand_total - cogs'));

        $totalSales = (float) (clone $allSales)->sum('grand_total');
        $totalPurchases = (float) (clone $allPurchases)->sum(DB::raw('quantity_received * purchase_price'));
        $totalExpenses = (float) (clone $allExpenses)->sum('amount');
        $totalGrossProfit = (float) (clone $allSales)->sum(DB::raw('grand_total - cogs'));

        $expiringDays = (int) (\setting('expiration_alert_days') ?? 7);

        return [
            'today_sales' => $todaySalesTotal,
            'today_purchases' => $todayPurchasesTotal,
            'today_expenses' => $todayExpensesTotal,
            'today_profit' => $todayGrossProfit - $todayExpensesTotal,
            'total_sales' => $totalSales,
            'total_purchases' => $totalPurchases,
            'total_expenses' => $totalExpenses,
            'total_profit' => $totalGrossProfit - $totalExpenses,
            'products' => Product::count(),
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
        ];
    }

    private function range(?string $from, ?string $to): array
    {
        $fromDate = $from ? Carbon::parse($from)->startOfDay() : now()->startOfDay();
        $toDate = $to ? Carbon::parse($to)->endOfDay() : now()->endOfDay();

        return [$fromDate, $toDate];
    }
}
