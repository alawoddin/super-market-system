<?php

namespace App\Services;

use App\Models\CashRegister;
use App\Models\Expense;
use App\Models\Sale;
use App\Models\SaleItem;
use App\Models\SaleReturn;
use App\Models\SaleReturnItem;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use RuntimeException;

class CashRegisterService
{
    public function open(float $openingBalance, ?string $notes = null): CashRegister
    {
        if (CashRegister::currentOpen()) {
            throw new RuntimeException('A cash register is already open.');
        }

        return CashRegister::query()->create([
            'user_id' => Auth::id(),
            'opened_at' => now(),
            'opening_balance' => round($openingBalance, 2),
            'expected_closing_balance' => round($openingBalance, 2),
            'status' => 'open',
            'notes' => $notes,
        ]);
    }

    public function close(float $actualBalance, ?string $notes = null): CashRegister
    {
        return DB::transaction(function () use ($actualBalance, $notes) {
            $register = CashRegister::currentOpen();
            if (! $register) {
                throw new RuntimeException('No open cash register.');
            }

            $register = CashRegister::query()->lockForUpdate()->findOrFail($register->id);
            $expected = $register->recalculateExpected();
            $register->actual_closing_balance = round($actualBalance, 2);
            $register->cash_difference = round($actualBalance - $expected, 2);
            $register->closed_at = now();
            $register->status = 'closed';
            if ($notes) {
                $register->notes = trim(($register->notes ? $register->notes."\n" : '').$notes);
            }
            $register->save();

            return $register;
        });
    }

    public function addCashSale(float $amount): void
    {
        $this->mutateOpen(fn (CashRegister $r) => $r->cash_sales = (float) $r->cash_sales + $amount);
    }

    public function addCashExpense(float $amount, string $method = 'cash'): void
    {
        $this->mutateOpen(fn (CashRegister $r) => $r->cash_expenses = (float) $r->cash_expenses + $amount);
    }

    public function addCashRefund(float $amount): void
    {
        $this->mutateOpen(fn (CashRegister $r) => $r->cash_refunds = (float) $r->cash_refunds + $amount);
    }

    /**
     * Everything that happened while the register was open (opened_at until closed_at, or now).
     */
    public function summary(CashRegister $register): array
    {
        $from = $register->opened_at;
        $to = $register->closed_at ?? now();

        $sales = Sale::query()
            ->whereIn('status', ['completed', 'returned'])
            ->whereBetween('sale_date', [$from, $to]);
        $saleIds = (clone $sales)->select('id');

        $returns = SaleReturn::query()->whereBetween('return_date', [$from, $to]);
        $refunds = (float) (clone $returns)->sum('refund_amount');
        $costBack = (float) SaleReturnItem::query()->whereIn('sale_return_id', (clone $returns)->select('id'))->sum('cost_total');

        $salesTotal = (float) (clone $sales)->sum('grand_total');
        $cogs = (float) (clone $sales)->sum('cogs') - $costBack;
        $grossProfit = $salesTotal - $refunds - $cogs;

        $expenses = Expense::query()->whereBetween('created_at', [$from, $to])->latest()->get();
        $expensesTotal = (float) $expenses->sum('amount');

        $products = SaleItem::query()
            ->whereIn('sale_id', $saleIds)
            ->with(['product' => fn ($q) => $q->withTrashed()])
            ->selectRaw('product_id, SUM(quantity) as qty, SUM(subtotal) as total, SUM(cost_total) as cost')
            ->groupBy('product_id')
            ->orderByDesc('total')
            ->get();

        return [
            'from' => $from,
            'to' => $to,
            'sales_count' => (clone $sales)->count(),
            'items_sold' => (float) $products->sum('qty'),
            'sales_total' => $salesTotal,
            'discounts' => (float) (clone $sales)->sum('discount'),
            'refunds' => $refunds,
            'net_sales' => $salesTotal - $refunds,
            'cogs' => $cogs,
            'gross_profit' => round($grossProfit, 2),
            'expenses_total' => $expensesTotal,
            'net_profit' => round($grossProfit - $expensesTotal, 2),
            'expenses' => $expenses,
            'products' => $products,
        ];
    }

    private function mutateOpen(callable $callback): void
    {
        $register = CashRegister::currentOpen();
        if (! $register) {
            return; // allow operations without open register
        }

        DB::transaction(function () use ($register, $callback) {
            $register = CashRegister::query()->lockForUpdate()->findOrFail($register->id);
            $callback($register);
            $register->recalculateExpected();
            $register->save();
        });
    }
}
