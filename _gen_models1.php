<?php
$base = __DIR__ . '/app/Models';

function w($file, $content) {
    global $base;
    file_put_contents($base . '/' . $file, $content);
    echo "Wrote $file\n";
}

w('Setting.php', <<<'PHP'
<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Setting extends Model
{
    protected $fillable = [
        'shop_name', 'logo', 'phone', 'address', 'currency',
        'receipt_header', 'receipt_footer', 'low_stock_alert',
        'expiration_alert_days', 'receipt_width',
    ];

    protected function casts(): array
    {
        return [
            'low_stock_alert' => 'integer',
            'expiration_alert_days' => 'integer',
        ];
    }

    public static function current(): self
    {
        return static::query()->firstOrCreate([], [
            'shop_name' => 'My Supermarket',
            'currency' => 'AFN',
            'receipt_footer' => 'Thank you for shopping with us!',
            'low_stock_alert' => 5,
            'expiration_alert_days' => 7,
            'receipt_width' => '80mm',
        ]);
    }
}
PHP);

w('Category.php', <<<'PHP'
<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\SoftDeletes;

class Category extends Model
{
    use SoftDeletes;

    protected $fillable = ['name', 'slug', 'description', 'is_active'];

    protected function casts(): array
    {
        return ['is_active' => 'boolean'];
    }

    public function products(): HasMany
    {
        return $this->hasMany(Product::class);
    }
}
PHP);

w('Product.php', <<<'PHP'
<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\SoftDeletes;

class Product extends Model
{
    use SoftDeletes;

    protected $fillable = [
        'category_id', 'name', 'barcode', 'sku', 'purchase_price', 'selling_price',
        'unit', 'min_stock', 'stock_quantity', 'description', 'image', 'is_active', 'track_expiry',
    ];

    protected function casts(): array
    {
        return [
            'purchase_price' => 'decimal:2',
            'selling_price' => 'decimal:2',
            'min_stock' => 'decimal:3',
            'stock_quantity' => 'decimal:3',
            'is_active' => 'boolean',
            'track_expiry' => 'boolean',
        ];
    }

    public function category(): BelongsTo
    {
        return $this->belongsTo(Category::class);
    }

    public function stockBatches(): HasMany
    {
        return $this->hasMany(StockBatch::class);
    }

    public function stockMovements(): HasMany
    {
        return $this->hasMany(StockMovement::class);
    }

    public function scopeActive(Builder $query): Builder
    {
        return $query->where('is_active', true);
    }

    public function scopeLowStock(Builder $query): Builder
    {
        return $query->whereColumn('stock_quantity', '<=', 'min_stock');
    }

    public function scopeSearch(Builder $query, ?string $term): Builder
    {
        if (!$term) {
            return $query;
        }

        return $query->where(function (Builder $q) use ($term) {
            $q->where('name', 'like', "%{$term}%")
                ->orWhere('barcode', 'like', "%{$term}%")
                ->orWhere('sku', 'like', "%{$term}%");
        });
    }

    public function getIsLowStockAttribute(): bool
    {
        return (float) $this->stock_quantity <= (float) $this->min_stock;
    }

    public function availableQuantity(): float
    {
        return (float) $this->stockBatches()
            ->where('quantity_remaining', '>', 0)
            ->where(function ($q) {
                $q->whereNull('expiration_date')
                    ->orWhereDate('expiration_date', '>=', now()->toDateString());
            })
            ->sum('quantity_remaining');
    }
}
PHP);

w('Supplier.php', <<<'PHP'
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
PHP);

w('Customer.php', <<<'PHP'
<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\SoftDeletes;

class Customer extends Model
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

    public function sales(): HasMany
    {
        return $this->hasMany(Sale::class);
    }

    public function payments(): HasMany
    {
        return $this->hasMany(SalePayment::class);
    }

    public function outstandingBalance(): float
    {
        $remaining = (float) $this->sales()
            ->where('status', 'completed')
            ->sum('remaining_balance');

        return round((float) $this->opening_balance + $remaining, 2);
    }
}
PHP);

echo "Batch 1 done\n";
