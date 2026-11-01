<?php

namespace App\Services;

use App\Models\Product;
use App\Models\Sale;
use App\Models\SaleItem;
use App\Models\SaleItemBatch;
use App\Models\SalePayment;
use App\Models\StockBatch;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use RuntimeException;

class SalesService
{
    public function __construct(private InventoryService $inventory)
    {
    }

    public function create(array $data, array $items): Sale
    {
        if (count($items) === 0) {
            throw new RuntimeException('Cart is empty.');
        }

        return DB::transaction(function () use ($data, $items) {
            $subtotal = 0;
            $normalized = [];

            foreach ($items as $item) {
                $product = Product::query()->lockForUpdate()->findOrFail($item['product_id']);

                if (! $product->is_active) {
                    throw new RuntimeException("Product {$product->name} is inactive.");
                }

                $qty = (float) $item['quantity'];
                $price = (float) ($item['unit_price'] ?? $product->selling_price);
                $discount = (float) ($item['discount'] ?? 0);
                $line = max(0, ($qty * $price) - $discount);

                // Block expired-only stock
                $available = (float) StockBatch::query()
                    ->where('product_id', $product->id)
                    ->available()
                    ->sum('quantity_remaining');

                if ($qty > $available + 0.0001) {
                    throw new RuntimeException("Insufficient sellable stock for {$product->name}.");
                }

                $subtotal += $line;
                $normalized[] = compact('product', 'qty', 'price', 'discount', 'line');
            }

            $overallDiscount = (float) ($data['discount'] ?? 0);
            if ($overallDiscount > $subtotal + 0.001) {
                throw new RuntimeException('Discount cannot be more than the total.');
            }
            $grandTotal = max(0, $subtotal - $overallDiscount);
            $amountPaid = (float) ($data['amount_paid'] ?? 0);

            // Cash only: the full total must be paid at the counter.
            if ($amountPaid + 0.001 < $grandTotal) {
                throw new RuntimeException('Amount paid is less than the total.');
            }

            $change = round($amountPaid - $grandTotal, 2);
            $appliedPaid = $grandTotal;

            $sale = Sale::query()->create([
                'invoice_number' => $this->nextInvoiceNumber(),
                'user_id' => Auth::id(),
                'sale_date' => now(),
                'subtotal' => round($subtotal, 2),
                'discount' => $overallDiscount,
                'grand_total' => round($grandTotal, 2),
                'amount_paid' => $appliedPaid,
                'remaining_balance' => 0,
                'change_amount' => $change,
                'payment_status' => 'paid',
                'payment_method' => 'cash',
                'status' => 'completed',
                'cogs' => 0,
                'notes' => $data['notes'] ?? null,
            ]);

            $totalCogs = 0;

            foreach ($normalized as $row) {
                /** @var Product $product */
                $product = $row['product'];
                $allocation = $this->inventory->allocateFefo($product, $row['qty']);

                $saleItem = SaleItem::query()->create([
                    'sale_id' => $sale->id,
                    'product_id' => $product->id,
                    'quantity' => $row['qty'],
                    'unit_price' => $row['price'],
                    'discount' => $row['discount'],
                    'subtotal' => round($row['line'], 2),
                    'cost_total' => $allocation['cogs'],
                ]);

                foreach ($allocation['allocations'] as $alloc) {
                    SaleItemBatch::query()->create([
                        'sale_item_id' => $saleItem->id,
                        'stock_batch_id' => $alloc['batch_id'],
                        'quantity' => $alloc['quantity'],
                        'purchase_price' => $alloc['purchase_price'],
                    ]);
                }

                $totalCogs += $allocation['cogs'];
            }

            $sale->cogs = round($totalCogs, 2);
            $sale->save();

            if ($appliedPaid > 0) {
                SalePayment::query()->create([
                    'sale_id' => $sale->id,
                    'user_id' => Auth::id(),
                    'payment_number' => $this->nextPaymentNumber(),
                    'payment_date' => now()->toDateString(),
                    'amount' => $appliedPaid,
                    'payment_method' => 'cash',
                    'notes' => 'Cash sale payment',
                ]);

                app(CashRegisterService::class)->addCashSale($appliedPaid);
            }

            return $sale->load(['items.product', 'payments', 'user']);
        });
    }

    public function cancel(Sale $sale): Sale
    {
        return DB::transaction(function () use ($sale) {
            $sale = Sale::query()->with(['items.batchAllocations'])->lockForUpdate()->findOrFail($sale->id);

            if ($sale->status !== 'completed') {
                throw new RuntimeException('Only completed sales can be cancelled.');
            }

            foreach ($sale->items as $item) {
                foreach ($item->batchAllocations as $alloc) {
                    $batch = StockBatch::query()->lockForUpdate()->find($alloc->stock_batch_id);
                    if ($batch) {
                        $this->inventory->restockFromReturn(
                            $item->product,
                            (float) $alloc->quantity,
                            $batch,
                            (float) $alloc->purchase_price
                        );
                    }
                }
            }

            $sale->status = 'cancelled';
            $sale->remaining_balance = 0;
            $sale->save();

            return $sale;
        });
    }

    public function nextInvoiceNumber(): string
    {
        $prefix = 'INV-'.now()->format('Ymd').'-';
        $last = Sale::withTrashed()
            ->where('invoice_number', 'like', $prefix.'%')
            ->orderByDesc('id')
            ->value('invoice_number');
        $seq = $last ? ((int) substr($last, -4)) + 1 : 1;

        return $prefix.str_pad((string) $seq, 4, '0', STR_PAD_LEFT);
    }

    public function nextPaymentNumber(): string
    {
        $prefix = 'SP-'.now()->format('Ymd').'-';
        $last = SalePayment::query()
            ->where('payment_number', 'like', $prefix.'%')
            ->orderByDesc('id')
            ->value('payment_number');
        $seq = $last ? ((int) substr($last, -4)) + 1 : 1;

        return $prefix.str_pad((string) $seq, 4, '0', STR_PAD_LEFT);
    }
}
