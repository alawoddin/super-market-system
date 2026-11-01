<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class CashRegister extends Model
{
    protected $fillable = [
        'user_id', 'opened_at', 'closed_at', 'opening_balance', 'cash_sales',
        'cash_expenses', 'cash_refunds',
        'expected_closing_balance', 'actual_closing_balance', 'cash_difference', 'status', 'notes',
    ];

    protected function casts(): array
    {
        return [
            'opened_at' => 'datetime',
            'closed_at' => 'datetime',
            'opening_balance' => 'decimal:2',
            'cash_sales' => 'decimal:2',
            'cash_expenses' => 'decimal:2',
            'cash_refunds' => 'decimal:2',
            'expected_closing_balance' => 'decimal:2',
            'actual_closing_balance' => 'decimal:2',
            'cash_difference' => 'decimal:2',
        ];
    }

    public function user(): BelongsTo { return $this->belongsTo(User::class); }

    public function recalculateExpected(): float
    {
        $expected = (float) $this->opening_balance
            + (float) $this->cash_sales
            - (float) $this->cash_expenses
            - (float) $this->cash_refunds;

        $this->expected_closing_balance = round($expected, 2);

        return (float) $this->expected_closing_balance;
    }

    public static function currentOpen(): ?self
    {
        return static::query()->where('status', 'open')->latest('opened_at')->first();
    }
}