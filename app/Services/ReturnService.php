<?php

namespace App\Services;

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
            $sale->load('items');
            $left = $sale->items->sum(fn ($i) => (float) $i->quantity - (float) $i->returned_quantity);
            if ((float) $sale->remaining_balance <= 0.001 && $left <= 0.001) {
                $sale->status = 'returned';
            }
            $sale->save();

            if ($cashRefund > 0 && ($meta['refund_method'] ?? 'cash') === 'cash') {
                app(CashRegisterService::class)->addCashRefund($cashRefund);
            }

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

}
