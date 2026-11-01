<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class StockBatch extends Model
{
    protected $fillable = [
        'product_id', 'batch_number', 'expiration_date',
        'quantity_received', 'quantity_remaining', 'purchase_price',
    ];

    protected function casts(): array
    {
        return [
            'expiration_date' => 'date',
            'quantity_received' => 'decimal:3',
            'quantity_remaining' => 'decimal:3',
            'purchase_price' => 'decimal:2',
        ];
    }

    public function product(): BelongsTo { return $this->belongsTo(Product::class); }

    public function scopeAvailable(Builder $query): Builder
    {
        return $query->where('quantity_remaining', '>', 0)
            ->where(function ($q) {
                $q->whereNull('expiration_date')
                    ->orWhereDate('expiration_date', '>=', now()->toDateString());
            });
    }

    public function scopeFefo(Builder $query): Builder
    {
        return $query->orderByRaw('CASE WHEN expiration_date IS NULL THEN 1 ELSE 0 END')
            ->orderBy('expiration_date')
            ->orderBy('id');
    }

    public function getIsExpiredAttribute(): bool
    {
        return $this->expiration_date && $this->expiration_date->lt(now()->startOfDay());
    }

    public function getIsExpiringSoonAttribute(): bool
    {
        if (!$this->expiration_date || $this->is_expired) {
            return false;
        }

        $days = (int) (setting('expiration_alert_days') ?? 7);

        return $this->expiration_date->lte(now()->addDays($days));
    }
}