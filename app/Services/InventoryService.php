<?php

namespace App\Services;

use App\Models\Product;
use App\Models\StockBatch;
use App\Models\StockMovement;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use RuntimeException;

class InventoryService
{
    public function createBatch(array $data): StockBatch
    {
        return DB::transaction(function () use ($data) {
            $product = Product::query()->lockForUpdate()->findOrFail($data['product_id']);
            $qty = (float) $data['quantity_received'];

            $batch = StockBatch::query()->create([
                'product_id' => $product->id,
                'batch_number' => $data['batch_number'] ?? null,
                'expiration_date' => $data['expiration_date'] ?? null,
                'quantity_received' => $qty,
                'quantity_remaining' => $qty,
                'purchase_price' => $data['purchase_price'],
            ]);

            $this->recordMovement(
                product: $product,
                type: 'in',
                quantity: $qty,
                batch: $batch,
                referenceType: $data['reference_type'] ?? null,
                referenceId: $data['reference_id'] ?? null,
                notes: $data['notes'] ?? 'Stock in'
            );

            $product->stock_quantity = (float) $product->stock_quantity + $qty;
            $product->save();

            return $batch;
        });
    }

    /**
     * Allocate stock using FEFO. Returns allocations and total COGS.
     *
     * @return array{allocations: array<int, array{batch_id:int, quantity:float, purchase_price:float}>, cogs: float}
     */
    public function allocateFefo(Product $product, float $quantity): array
    {
        if ($quantity <= 0) {
            throw new RuntimeException('Quantity must be greater than zero.');
        }

        $remaining = $quantity;
        $allocations = [];
        $cogs = 0.0;

        $batches = StockBatch::query()
            ->where('product_id', $product->id)
            ->available()
            ->fefo()
            ->lockForUpdate()
            ->get();

        foreach ($batches as $batch) {
            if ($remaining <= 0) {
                break;
            }

            $take = min((float) $batch->quantity_remaining, $remaining);
            if ($take <= 0) {
                continue;
            }

            $batch->quantity_remaining = (float) $batch->quantity_remaining - $take;
            $batch->save();

            $allocations[] = [
                'batch_id' => $batch->id,
                'quantity' => $take,
                'purchase_price' => (float) $batch->purchase_price,
            ];

            $cogs += $take * (float) $batch->purchase_price;
            $remaining -= $take;

            $this->recordMovement(
                product: $product,
                type: 'out',
                quantity: $take,
                batch: $batch,
                notes: 'Sale allocation (FEFO)'
            );
        }

        if ($remaining > 0.0001) {
            throw new RuntimeException("Insufficient stock for {$product->name}. Short by ".qty_format($remaining));
        }

        $product->stock_quantity = max(0, (float) $product->stock_quantity - $quantity);
        $product->save();

        return [
            'allocations' => $allocations,
            'cogs' => round($cogs, 2),
        ];
    }

    public function restockFromReturn(Product $product, float $quantity, ?StockBatch $preferredBatch = null, ?float $purchasePrice = null): void
    {
        $product = Product::query()->lockForUpdate()->findOrFail($product->id);

        if ($preferredBatch && (float) $preferredBatch->quantity_remaining >= 0) {
            $batch = StockBatch::query()->lockForUpdate()->findOrFail($preferredBatch->id);
            $batch->quantity_remaining = (float) $batch->quantity_remaining + $quantity;
            $batch->save();
        } else {
            $batch = StockBatch::query()->create([
                'product_id' => $product->id,
                'batch_number' => 'RET-'.now()->format('YmdHis'),
                'expiration_date' => null,
                'quantity_received' => $quantity,
                'quantity_remaining' => $quantity,
                'purchase_price' => $purchasePrice ?? (float) $product->purchase_price,
            ]);
        }

        $this->recordMovement(
            product: $product,
            type: 'return_in',
            quantity: $quantity,
            batch: $batch,
            notes: 'Sales return restock'
        );

        $product->stock_quantity = (float) $product->stock_quantity + $quantity;
        $product->save();
    }

    public function reduceBatch(StockBatch $batch, float $quantity, string $notes = 'Stock reduction'): void
    {
        $batch = StockBatch::query()->lockForUpdate()->findOrFail($batch->id);
        $product = Product::query()->lockForUpdate()->findOrFail($batch->product_id);

        if ((float) $batch->quantity_remaining + 0.0001 < $quantity) {
            throw new RuntimeException('Insufficient batch quantity.');
        }

        $batch->quantity_remaining = (float) $batch->quantity_remaining - $quantity;
        $batch->save();

        $this->recordMovement(
            product: $product,
            type: 'return_out',
            quantity: $quantity,
            batch: $batch,
            notes: $notes
        );

        $product->stock_quantity = max(0, (float) $product->stock_quantity - $quantity);
        $product->save();
    }

    public function adjust(Product $product, float $newQuantity, string $notes = 'Manual adjustment'): void
    {
        DB::transaction(function () use ($product, $newQuantity, $notes) {
            $product = Product::query()->lockForUpdate()->findOrFail($product->id);
            $before = (float) $product->stock_quantity;
            $diff = $newQuantity - $before;

            if (abs($diff) < 0.0001) {
                return;
            }

            if ($diff > 0) {
                $batch = StockBatch::query()->create([
                    'product_id' => $product->id,
                    'batch_number' => 'ADJ-'.now()->format('YmdHis'),
                    'quantity_received' => $diff,
                    'quantity_remaining' => $diff,
                    'purchase_price' => (float) $product->purchase_price,
                ]);

                $this->recordMovement($product, 'adjustment', $diff, $batch, null, null, $notes);
            } else {
                $need = abs($diff);
                $batches = StockBatch::query()
                    ->where('product_id', $product->id)
                    ->where('quantity_remaining', '>', 0)
                    ->fefo()
                    ->lockForUpdate()
                    ->get();

                foreach ($batches as $batch) {
                    if ($need <= 0) {
                        break;
                    }
                    $take = min((float) $batch->quantity_remaining, $need);
                    $batch->quantity_remaining = (float) $batch->quantity_remaining - $take;
                    $batch->save();
                    $this->recordMovement($product, 'adjustment', $take, $batch, null, null, $notes);
                    $need -= $take;
                }

                if ($need > 0.0001) {
                    throw new RuntimeException('Cannot adjust below available batch quantities.');
                }
            }

            $product->stock_quantity = $newQuantity;
            $product->save();
        });
    }

    public function recordMovement(
        Product $product,
        string $type,
        float $quantity,
        ?StockBatch $batch = null,
        ?string $referenceType = null,
        ?int $referenceId = null,
        ?string $notes = null
    ): StockMovement {
        $before = (float) $product->stock_quantity;
        $after = match ($type) {
            'in', 'return_in' => $before + $quantity,
            'out', 'return_out' => max(0, $before - $quantity),
            default => $before,
        };

        if ($type === 'adjustment') {
            $after = $before; // caller updates product stock
        }

        return StockMovement::query()->create([
            'product_id' => $product->id,
            'stock_batch_id' => $batch?->id,
            'user_id' => Auth::id(),
            'type' => $type,
            'quantity' => $quantity,
            'quantity_before' => $before,
            'quantity_after' => $type === 'adjustment' ? (float) $product->fresh()->stock_quantity : $after,
            'reference_type' => $referenceType,
            'reference_id' => $referenceId,
            'notes' => $notes,
        ]);
    }

    public function syncProductStock(Product $product): void
    {
        $product->stock_quantity = (float) $product->stockBatches()->sum('quantity_remaining');
        $product->save();
    }
}
