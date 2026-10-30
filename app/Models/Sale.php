<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\SoftDeletes;

class Sale extends Model
{
    use SoftDeletes;

    protected $fillable = [
        'invoice_number', 'customer_id', 'user_id', 'sale_date', 'subtotal', 'discount',
        'grand_total', 'amount_paid', 'remaining_balance', 'change_amount',
        'payment_status', 'payment_method', 'status', 'cogs', 'notes',
    ];

    protected function casts(): array
    {
        return [
            'sale_date' => 'datetime',
            'subtotal' => 'decimal:2',
            'discount' => 'decimal:2',
            'grand_total' => 'decimal:2',
            'amount_paid' => 'decimal:2',
            'remaining_balance' => 'decimal:2',
            'change_amount' => 'decimal:2',
            'cogs' => 'decimal:2',
        ];
    }

    public function customer(): BelongsTo { return $this->belongsTo(Customer::class); }
    public function user(): BelongsTo { return $this->belongsTo(User::class); }
    public function items(): HasMany { return $this->hasMany(SaleItem::class); }
    public function payments(): HasMany { return $this->hasMany(SalePayment::class); }
    public function returns(): HasMany { return $this->hasMany(SaleReturn::class); }

    public function getProfitAttribute(): float
    {
        return round((float) $this->grand_total - (float) $this->cogs, 2);
    }
}