<?php

namespace App\Services;

use App\Models\Expense;
use App\Models\Product;
use App\Models\Sale;
use App\Models\SaleReturn;
use App\Models\SaleReturnItem;
use App\Models\StockBatch;
use Carbon\Carbon;
use Illuminate\Support\Facades\DB;

class ReportService
{
    public function salesSummary(?string $from = null, ?string $to = null): array
    {
        [$from, $to] = $this->range($from, $to);

        $sales = $this->soldSales()->whereBetween('sale_date', [$from, $to]);
        $returns = $this->returnTotals($from, $to);

        $grossRevenue = (float) (clone $sales)->sum('grand_total');
        $grossCogs = (float) (clone $sales)->sum('cogs');
        $revenue = $grossRevenue - $returns['refunds'];
        $cogs = $grossCogs - $returns['cost_back'];

        return [
            'from' => $from,
            'to' => $to,
            'count' => (clone $sales)->count(),
            'gross_revenue' => $grossRevenue,
            'returns' => $returns['refunds'],
            'revenue' => round($revenue, 2),
            'cash_received' => round((float) (clone $sales)->sum('amount_paid') - $returns['refunds'], 2),
            'cogs' => round($cogs, 2),
            'discounts' => (float) (clone $sales)->sum('discount'),
            'profit' => round($revenue - $cogs, 2),
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

        $rows = $this->soldSales()
            ->selectRaw('DATE(sale_date) as day, SUM(grand_total) as total')
            ->where('sale_date', '>=', $start)
            ->groupBy('day')
            ->pluck('total', 'day');

        $refunds = SaleReturn::query()
            ->selectRaw('DATE(return_date) as day, SUM(refund_amount) as total')
            ->where('return_date', '>=', $start)
            ->groupBy('day')
            ->pluck('total', 'day');

        $labels = [];
        $data = [];
        for ($i = 0; $i < $days; $i++) {
            $day = $start->copy()->addDays($i)->toDateString();
            $labels[] = $day;
            $data[] = round((float) ($rows[$day] ?? 0) - (float) ($refunds[$day] ?? 0), 2);
        }

        return compact('labels', 'data');
    }

    public function monthlySalesChart(int $months = 12): array
    {
        $start = now()->subMonths($months - 1)->startOfMonth();

        $rows = $this->soldSales()
            ->selectRaw("DATE_FORMAT(sale_date, '%Y-%m') as month, SUM(grand_total) as total")
            ->where('sale_date', '>=', $start)
            ->groupBy('month')
            ->pluck('total', 'month');

        $refunds = SaleReturn::query()
            ->selectRaw("DATE_FORMAT(return_date, '%Y-%m') as month, SUM(refund_amount) as total")
            ->where('return_date', '>=', $start)
            ->groupBy('month')
            ->pluck('total', 'month');

        $labels = [];
        $data = [];
        for ($i = 0; $i < $months; $i++) {
            $month = $start->copy()->addMonths($i)->format('Y-m');
            $labels[] = $month;
            $data[] = round((float) ($rows[$month] ?? 0) - (float) ($refunds[$month] ?? 0), 2);
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

        $allSales = $this->soldSales();
        // Stock bought = value of stock added from the product form (sales returns excluded)
        $allPurchases = StockBatch::query()->where('batch_number', 'not like', 'RET-%');
        $allExpenses = Expense::query();

        $todaySales = (clone $allSales)->whereDate('sale_date', $today);
        $todayPurchases = (clone $allPurchases)->whereDate('created_at', $today);
        $todayExpenses = (clone $allExpenses)->whereDate('expense_date', $today);

        $todayReturns = $this->returnTotals(now()->startOfDay(), now()->endOfDay());
        $allReturns = $this->returnTotals();

        $todaySalesTotal = (float) (clone $todaySales)->sum('grand_total') - $todayReturns['refunds'];
        $todayPurchasesTotal = (float) (clone $todayPurchases)->sum(DB::raw('quantity_received * purchase_price'));
        $todayExpensesTotal = (float) (clone $todayExpenses)->sum('amount');
        $todayGrossProfit = (float) (clone $todaySales)->sum(DB::raw('grand_total - cogs'))
            - ($todayReturns['refunds'] - $todayReturns['cost_back']);

        $totalSales = (float) (clone $allSales)->sum('grand_total') - $allReturns['refunds'];
        $totalPurchases = (float) (clone $allPurchases)->sum(DB::raw('quantity_received * purchase_price'));
        $totalExpenses = (float) (clone $allExpenses)->sum('amount');
        $totalGrossProfit = (float) (clone $allSales)->sum(DB::raw('grand_total - cogs'))
            - ($allReturns['refunds'] - $allReturns['cost_back']);

        $expiringDays = (int) (\setting('expiration_alert_days') ?? 7);

        return [
            'today_sales' => $todaySalesTotal,
            'today_purchases' => $todayPurchasesTotal,
            'today_expenses' => $todayExpensesTotal,
            'today_profit' => $todayGrossProfit - $todayExpensesTotal,
            'total_sales' => $totalSales,
            'total_purchases' => $totalPurchases,
            'total_expenses' => $totalExpenses,
            'month_expenses' => (float) (clone $allExpenses)->whereBetween('expense_date', [now()->startOfMonth()->toDateString(), $today])->sum('amount'),
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

    /** Sales that count toward revenue. Fully returned sales stay in; their refunds are subtracted separately. */
    private function soldSales()
    {
        return Sale::query()->whereIn('status', ['completed', 'returned']);
    }

    /**
     * Money refunded and cost taken back out of COGS for returns in a period (all time when no dates).
     *
     * @return array{refunds: float, cost_back: float}
     */
    private function returnTotals(?Carbon $from = null, ?Carbon $to = null): array
    {
        $returns = SaleReturn::query()->when($from && $to, fn ($q) => $q->whereBetween('return_date', [$from, $to]));

        return [
            'refunds' => (float) (clone $returns)->sum('refund_amount'),
            'cost_back' => (float) SaleReturnItem::query()->whereIn('sale_return_id', (clone $returns)->select('id'))->sum('cost_total'),
        ];
    }

    private function range(?string $from, ?string $to): array
    {
        $fromDate = $from ? Carbon::parse($from)->startOfDay() : now()->startOfDay();
        $toDate = $to ? Carbon::parse($to)->endOfDay() : now()->endOfDay();

        return [$fromDate, $toDate];
    }
}
