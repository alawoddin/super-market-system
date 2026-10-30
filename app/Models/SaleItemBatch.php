<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class SaleItemBatch extends Model
{
    protected $fillable = ['sale_item_id', 'stock_batch_id', 'quantity', 'purchase_price'];

    protected function casts(): array
    {
        return [
            'quantity' => 'decimal:3',
            'purchase_price' => 'decimal:2',
        ];
    }

    public function saleItem(): BelongsTo { return $this->belongsTo(SaleItem::class); }
    public function stockBatch(): BelongsTo { return $this->belongsTo(StockBatch::class); }
}