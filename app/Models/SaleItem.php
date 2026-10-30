<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class SaleItem extends Model
{
    protected $fillable = [
        'sale_id', 'product_id', 'quantity', 'unit_price', 'discount',
        'subtotal', 'cost_total', 'returned_quantity',
    ];

    protected function casts(): array
    {
        return [
            'quantity' => 'decimal:3',
            'unit_price' => 'decimal:2',
            'discount' => 'decimal:2',
            'subtotal' => 'decimal:2',
            'cost_total' => 'decimal:2',
            'returned_quantity' => 'decimal:3',
        ];
    }

    public function sale(): BelongsTo { return $this->belongsTo(Sale::class); }
    public function product(): BelongsTo { return $this->belongsTo(Product::class); }
    public function batchAllocations(): HasMany { return $this->hasMany(SaleItemBatch::class); }

    public function returnableQuantity(): float
    {
        return max(0, (float) $this->quantity - (float) $this->returned_quantity);
    }
}