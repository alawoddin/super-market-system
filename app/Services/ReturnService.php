<?php

namespace App\Services;

use App\Models\Purchase;
use App\Models\PurchaseItem;
use App\Models\PurchaseReturn;
use App\Models\PurchaseReturnItem;
use App\Models\Sale;
use App\Models\SaleItem;
use App\Models\SaleReturn;
use App\Models\SaleReturnItem;
use App\Models\StockBatch;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use RuntimeException;

class ReturnService
{
    public function __construct(private InventoryService $inventory)
    {
    }

    public function createSaleReturn(Sale $sale, array $items, array $meta = []): SaleReturn
    {
        return DB::transaction(function () use ($sale, $items, $meta) {
            $sale = Sale::query()->with('items')->lockForUpdate()->findOrFail($sale->id);

            if ($sale->status !== 'completed') {
                throw new RuntimeException('Only completed sales can be returned.');
            }

            $refund = 0;
            $rows = [];

            foreach ($items as $row) {
                $saleItem = SaleItem::query()->lockForUpdate()->findOrFail($row['sale_item_id']);
                if ($saleItem->sale_id !== $sale->id) {
                    throw new RuntimeException('Invalid sale item.');
                }

                $qty = (float) $row['quantity'];
                if ($qty <= 0 || $qty > $saleItem->returnableQuantity() + 0.0001) {
                    throw new RuntimeException('Invalid return quantity for '.$saleItem->product->name);
                }

                $unitNet = (float) $saleItem->quantity > 0
                    ? ((float) $saleItem->subtotal / (float) $saleItem->quantity)
                    : (float) $saleItem->unit_price;

                $line = round($unitNet * $qty, 2);
                $refund += $line;
                $rows[] = compact('saleItem', 'qty', 'unitNet', 'line');
            }

            $return = SaleReturn::query()->create([
                'return_number' => $this->nextSaleReturnNumber(),
                'sale_id' => $sale->id,
                'user_id' => Auth::id(),
                'return_date' => now(),
                'refund_amount' => round($refund, 2),
                'refund_method' => $meta['refund_method'] ?? 'cash',
                'restock' => (bool) ($meta['restock'] ?? true),
                'notes' => $meta['notes'] ?? null,
            ]);

            foreach ($rows as $row) {
                /** @var SaleItem $saleItem */
                $saleItem = $row['saleItem'];

                SaleReturnItem::query()->create([
                    'sale_return_id' => $return->id,
                    'sale_item_id' => $saleItem->id,
                    'product_id' => $saleItem->product_id,
                    'quantity' => $row['qty'],
                    'unit_price' => round($row['unitNet'], 2),
                    'subtotal' => $row['line'],
                ]);

                $saleItem->returned_quantity = (float) $saleItem->returned_quantity + $row['qty'];
                $saleItem->save();

                if ($return->restock) {
                    $preferred = $saleItem->batchAllocations()->with('stockBatch')->first()?->stockBatch;
                    $this->inventory->restockFromReturn(
                        $saleItem->product,
                        $row['qty'],
                        $preferred,
                        (float) ($preferred?->purchase_price ?? $saleItem->product->purchase_price)
                    );
                }
            }

            // Reduce remaining debt first, then treat excess as cash refund impact
            $debtReduction = min((float) $sale->remaining_balance, $refund);
            $cashRefund = round($refund - $debtReduction, 2);

            $sale->remaining_balance = round((float) $sale->remaining_balance - $debtReduction, 2);
            if ((float) $sale->remaining_balance <= 0.001 && $sale->items()->sum(DB::raw('quantity - returned_quantity')) <= 0.001) {
                $sale->status = 'returned';
            }
            $sale->save();

            if ($cashRefund > 0 && ($meta['refund_method'] ?? 'cash') === 'cash') {
                app(CashRegisterService::class)->addCashRefund($cashRefund);
            }

            return $return->load('items.product');
        });
    }

    public function createPurchaseReturn(Purchase $purchase, array $items, ?string $notes = null): PurchaseReturn
    {
        return DB::transaction(function () use ($purchase, $items, $notes) {
            $purchase = Purchase::query()->with('items')->lockForUpdate()->findOrFail($purchase->id);
            $total = 0;
            $rows = [];

            foreach ($items as $row) {
                $purchaseItem = PurchaseItem::query()->lockForUpdate()->findOrFail($row['purchase_item_id']);
                if ($purchaseItem->purchase_id !== $purchase->id) {
                    throw new RuntimeException('Invalid purchase item.');
                }

                $qty = (float) $row['quantity'];
                if ($qty <= 0 || $qty > (float) $purchaseItem->quantity + 0.0001) {
                    throw new RuntimeException('Invalid purchase return quantity.');
                }

                $line = round($qty * (float) $purchaseItem->unit_cost, 2);
                $total += $line;
                $rows[] = compact('purchaseItem', 'qty', 'line');
            }

            $return = PurchaseReturn::query()->create([
                'return_number' => $this->nextPurchaseReturnNumber(),
                'purchase_id' => $purchase->id,
                'supplier_id' => $purchase->supplier_id,
                'user_id' => Auth::id(),
                'return_date' => now(),
                'total_amount' => round($total, 2),
                'notes' => $notes,
            ]);

            foreach ($rows as $row) {
                /** @var PurchaseItem $purchaseItem */
                $purchaseItem = $row['purchaseItem'];
                $batch = $purchaseItem->stockBatch;

                if (! $batch || (float) $batch->quantity_remaining + 0.0001 < $row['qty']) {
                    throw new RuntimeException('Not enough remaining stock in original batch to return.');
                }

                PurchaseReturnItem::query()->create([
                    'purchase_return_id' => $return->id,
                    'purchase_item_id' => $purchaseItem->id,
                    'product_id' => $purchaseItem->product_id,
                    'stock_batch_id' => $batch->id,
                    'quantity' => $row['qty'],
                    'unit_cost' => $purchaseItem->unit_cost,
                    'subtotal' => $row['line'],
                ]);

                $this->inventory->reduceBatch($batch, $row['qty'], 'Purchase return '.$return->return_number);
            }

            // Reduce supplier payable via remaining balance adjustment
            $purchase->remaining_balance = max(0, round((float) $purchase->remaining_balance - $total, 2));
            $purchase->grand_total = max(0, round((float) $purchase->grand_total - $total, 2));
            $purchase->save();

            return $return->load('items.product');
        });
    }

    private function nextSaleReturnNumber(): string
    {
        $prefix = 'SR-'.now()->format('Ymd').'-';
        $last = SaleReturn::query()->where('return_number', 'like', $prefix.'%')->orderByDesc('id')->value('return_number');
        $seq = $last ? ((int) substr($last, -4)) + 1 : 1;

        return $prefix.str_pad((string) $seq, 4, '0', STR_PAD_LEFT);
    }

    private function nextPurchaseReturnNumber(): string
    {
        $prefix = 'PR-'.now()->format('Ymd').'-';
        $last = PurchaseReturn::query()->where('return_number', 'like', $prefix.'%')->orderByDesc('id')->value('return_number');
        $seq = $last ? ((int) substr($last, -4)) + 1 : 1;

        return $prefix.str_pad((string) $seq, 4, '0', STR_PAD_LEFT);
    }
}
