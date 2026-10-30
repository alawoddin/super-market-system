<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\MorphTo;

class StockMovement extends Model
{
    protected $fillable = [
        'product_id', 'stock_batch_id', 'user_id', 'type', 'quantity',
        'quantity_before', 'quantity_after', 'reference_type', 'reference_id', 'notes',
    ];

    protected function casts(): array
    {
        return [
            'quantity' => 'decimal:3',
            'quantity_before' => 'decimal:3',
            'quantity_after' => 'decimal:3',
        ];
    }

    public function product(): BelongsTo { return $this->belongsTo(Product::class); }
    public function stockBatch(): BelongsTo { return $this->belongsTo(StockBatch::class); }
    public function user(): BelongsTo { return $this->belongsTo(User::class); }

    public function reference(): MorphTo
    {
        return $this->morphTo();
    }
}