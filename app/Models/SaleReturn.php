<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class SaleReturn extends Model
{
    protected $fillable = [
        'return_number', 'sale_id', 'user_id', 'return_date',
        'refund_amount', 'refund_method', 'restock', 'notes',
    ];

    protected function casts(): array
    {
        return [
            'return_date' => 'datetime',
            'refund_amount' => 'decimal:2',
            'restock' => 'boolean',
        ];
    }

    public function sale(): BelongsTo { return $this->belongsTo(Sale::class); }
    public function user(): BelongsTo { return $this->belongsTo(User::class); }
    public function items(): HasMany { return $this->hasMany(SaleReturnItem::class); }
}