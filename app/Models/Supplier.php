<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\SoftDeletes;

class Supplier extends Model
{
    use SoftDeletes;

    protected $fillable = ['name', 'phone', 'address', 'opening_balance', 'notes', 'is_active'];

    protected function casts(): array
    {
        return [
            'opening_balance' => 'decimal:2',
            'is_active' => 'boolean',
        ];
    }

    public function purchases(): HasMany
    {
        return $this->hasMany(Purchase::class);
    }

    public function payments(): HasMany
    {
        return $this->hasMany(PurchasePayment::class);
    }

    public function purchaseReturns(): HasMany
    {
        return $this->hasMany(PurchaseReturn::class);
    }

    public function outstandingBalance(): float
    {
        $purchases = (float) $this->purchases()->where('status', 'confirmed')->sum('grand_total');
        $payments = (float) $this->payments()->sum('amount');
        $returns = (float) $this->purchaseReturns()->sum('total_amount');

        return round((float) $this->opening_balance + $purchases - $payments - $returns, 2);
    }
}