<?php
$base = __DIR__ . '/app/Models';
function w($file, $content) {
    global $base;
    file_put_contents($base . '/' . $file, $content);
    echo "Wrote $file\n";
}

w('Purchase.php', <<<'PHP'
<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\SoftDeletes;

class Purchase extends Model
{
    use SoftDeletes;

    protected $fillable = [
        'invoice_number', 'supplier_id', 'user_id', 'purchase_date', 'subtotal', 'discount',
        'grand_total', 'amount_paid', 'remaining_balance', 'payment_status', 'status', 'notes',
    ];

    protected function casts(): array
    {
        return [
            'purchase_date' => 'date',
            'subtotal' => 'decimal:2',
            'discount' => 'decimal:2',
            'grand_total' => 'decimal:2',
            'amount_paid' => 'decimal:2',
            'remaining_balance' => 'decimal:2',
        ];
    }

    public function supplier(): BelongsTo { return $this->belongsTo(Supplier::class); }
    public function user(): BelongsTo { return $this->belongsTo(User::class); }
    public function items(): HasMany { return $this->hasMany(PurchaseItem::class); }
    public function payments(): HasMany { return $this->hasMany(PurchasePayment::class); }
    public function returns(): HasMany { return $this->hasMany(PurchaseReturn::class); }
}
PHP);

w('PurchaseItem.php', <<<'PHP'
<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasOne;

class PurchaseItem extends Model
{
    protected $fillable = [
        'purchase_id', 'product_id', 'quantity', 'unit_cost', 'discount',
        'subtotal', 'expiration_date', 'batch_number',
    ];

    protected function casts(): array
    {
        return [
            'quantity' => 'decimal:3',
            'unit_cost' => 'decimal:2',
            'discount' => 'decimal:2',
            'subtotal' => 'decimal:2',
            'expiration_date' => 'date',
        ];
    }

    public function purchase(): BelongsTo { return $this->belongsTo(Purchase::class); }
    public function product(): BelongsTo { return $this->belongsTo(Product::class); }
    public function stockBatch(): HasOne { return $this->hasOne(StockBatch::class); }
}
PHP);

w('StockBatch.php', <<<'PHP'
<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class StockBatch extends Model
{
    protected $fillable = [
        'product_id', 'purchase_item_id', 'batch_number', 'expiration_date',
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
    public function purchaseItem(): BelongsTo { return $this->belongsTo(PurchaseItem::class); }

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
PHP);

w('StockMovement.php', <<<'PHP'
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
PHP);

w('PurchasePayment.php', <<<'PHP'
<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class PurchasePayment extends Model
{
    protected $fillable = [
        'purchase_id', 'supplier_id', 'user_id', 'payment_number',
        'payment_date', 'amount', 'payment_method', 'notes',
    ];

    protected function casts(): array
    {
        return [
            'payment_date' => 'date',
            'amount' => 'decimal:2',
        ];
    }

    public function purchase(): BelongsTo { return $this->belongsTo(Purchase::class); }
    public function supplier(): BelongsTo { return $this->belongsTo(Supplier::class); }
    public function user(): BelongsTo { return $this->belongsTo(User::class); }
}
PHP);

w('Sale.php', <<<'PHP'
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
PHP);

w('SaleItem.php', <<<'PHP'
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
PHP);

w('SaleItemBatch.php', <<<'PHP'
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
PHP);

w('SalePayment.php', <<<'PHP'
<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class SalePayment extends Model
{
    protected $fillable = [
        'sale_id', 'customer_id', 'user_id', 'payment_number',
        'payment_date', 'amount', 'payment_method', 'notes',
    ];

    protected function casts(): array
    {
        return [
            'payment_date' => 'date',
            'amount' => 'decimal:2',
        ];
    }

    public function sale(): BelongsTo { return $this->belongsTo(Sale::class); }
    public function customer(): BelongsTo { return $this->belongsTo(Customer::class); }
    public function user(): BelongsTo { return $this->belongsTo(User::class); }
}
PHP);

w('ExpenseCategory.php', <<<'PHP'
<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

class ExpenseCategory extends Model
{
    protected $fillable = ['name', 'is_active'];

    protected function casts(): array
    {
        return ['is_active' => 'boolean'];
    }

    public function expenses(): HasMany
    {
        return $this->hasMany(Expense::class);
    }
}
PHP);

w('Expense.php', <<<'PHP'
<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\SoftDeletes;

class Expense extends Model
{
    use SoftDeletes;

    protected $fillable = [
        'expense_category_id', 'user_id', 'title', 'amount',
        'expense_date', 'payment_method', 'description',
    ];

    protected function casts(): array
    {
        return [
            'amount' => 'decimal:2',
            'expense_date' => 'date',
        ];
    }

    public function category(): BelongsTo { return $this->belongsTo(ExpenseCategory::class, 'expense_category_id'); }
    public function user(): BelongsTo { return $this->belongsTo(User::class); }
}
PHP);

w('CashRegister.php', <<<'PHP'
<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class CashRegister extends Model
{
    protected $fillable = [
        'user_id', 'opened_at', 'closed_at', 'opening_balance', 'cash_sales',
        'customer_debt_payments', 'supplier_payments', 'cash_expenses', 'cash_refunds',
        'expected_closing_balance', 'actual_closing_balance', 'cash_difference', 'status', 'notes',
    ];

    protected function casts(): array
    {
        return [
            'opened_at' => 'datetime',
            'closed_at' => 'datetime',
            'opening_balance' => 'decimal:2',
            'cash_sales' => 'decimal:2',
            'customer_debt_payments' => 'decimal:2',
            'supplier_payments' => 'decimal:2',
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
            + (float) $this->customer_debt_payments
            - (float) $this->supplier_payments
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
PHP);

w('SaleReturn.php', <<<'PHP'
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
PHP);

w('SaleReturnItem.php', <<<'PHP'
<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class SaleReturnItem extends Model
{
    protected $fillable = [
        'sale_return_id', 'sale_item_id', 'product_id', 'quantity', 'unit_price', 'subtotal',
    ];

    protected function casts(): array
    {
        return [
            'quantity' => 'decimal:3',
            'unit_price' => 'decimal:2',
            'subtotal' => 'decimal:2',
        ];
    }

    public function saleReturn(): BelongsTo { return $this->belongsTo(SaleReturn::class); }
    public function saleItem(): BelongsTo { return $this->belongsTo(SaleItem::class); }
    public function product(): BelongsTo { return $this->belongsTo(Product::class); }
}
PHP);

w('PurchaseReturn.php', <<<'PHP'
<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class PurchaseReturn extends Model
{
    protected $fillable = [
        'return_number', 'purchase_id', 'supplier_id', 'user_id',
        'return_date', 'total_amount', 'notes',
    ];

    protected function casts(): array
    {
        return [
            'return_date' => 'datetime',
            'total_amount' => 'decimal:2',
        ];
    }

    public function purchase(): BelongsTo { return $this->belongsTo(Purchase::class); }
    public function supplier(): BelongsTo { return $this->belongsTo(Supplier::class); }
    public function user(): BelongsTo { return $this->belongsTo(User::class); }
    public function items(): HasMany { return $this->hasMany(PurchaseReturnItem::class); }
}
PHP);

w('PurchaseReturnItem.php', <<<'PHP'
<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class PurchaseReturnItem extends Model
{
    protected $fillable = [
        'purchase_return_id', 'purchase_item_id', 'product_id', 'stock_batch_id',
        'quantity', 'unit_cost', 'subtotal',
    ];

    protected function casts(): array
    {
        return [
            'quantity' => 'decimal:3',
            'unit_cost' => 'decimal:2',
            'subtotal' => 'decimal:2',
        ];
    }

    public function purchaseReturn(): BelongsTo { return $this->belongsTo(PurchaseReturn::class); }
    public function purchaseItem(): BelongsTo { return $this->belongsTo(PurchaseItem::class); }
    public function product(): BelongsTo { return $this->belongsTo(Product::class); }
    public function stockBatch(): BelongsTo { return $this->belongsTo(StockBatch::class); }
}
PHP);

echo "All models written\n";
