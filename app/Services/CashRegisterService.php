<?php

namespace App\Services;

use App\Models\CashRegister;
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

    public function addCustomerDebtPayment(float $amount): void
    {
        $this->mutateOpen(fn (CashRegister $r) => $r->customer_debt_payments = (float) $r->customer_debt_payments + $amount);
    }

    public function addSupplierPayment(float $amount, string $method = 'cash'): void
    {
        $this->mutateOpen(fn (CashRegister $r) => $r->supplier_payments = (float) $r->supplier_payments + $amount);
    }

    public function addCashExpense(float $amount, string $method = 'cash'): void
    {
        $this->mutateOpen(fn (CashRegister $r) => $r->cash_expenses = (float) $r->cash_expenses + $amount);
    }

    public function addCashRefund(float $amount): void
    {
        $this->mutateOpen(fn (CashRegister $r) => $r->cash_refunds = (float) $r->cash_refunds + $amount);
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
