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
            $grandTotal = max(0, $subtotal - $overallDiscount);
            $amountPaid = (float) ($data['amount_paid'] ?? 0);
            $isCredit = $amountPaid + 0.001 < $grandTotal;

            if ($isCredit && empty($data['customer_id'])) {
                throw new RuntimeException('Credit sales require a registered customer.');
            }

            if ($amountPaid > $grandTotal) {
                $change = round($amountPaid - $grandTotal, 2);
                $appliedPaid = $grandTotal;
            } else {
                $change = 0;
                $appliedPaid = $amountPaid;
            }

            $remaining = round($grandTotal - $appliedPaid, 2);

            $sale = Sale::query()->create([
                'invoice_number' => $this->nextInvoiceNumber(),
                'customer_id' => $data['customer_id'] ?? null,
                'user_id' => Auth::id(),
                'sale_date' => now(),
                'subtotal' => round($subtotal, 2),
                'discount' => $overallDiscount,
                'grand_total' => round($grandTotal, 2),
                'amount_paid' => $appliedPaid,
                'remaining_balance' => $remaining,
                'change_amount' => $change,
                'payment_status' => $this->statusFromAmounts($grandTotal, $appliedPaid),
                'payment_method' => $data['payment_method'] ?? 'cash',
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
                    'customer_id' => $sale->customer_id,
                    'user_id' => Auth::id(),
                    'payment_number' => $this->nextPaymentNumber(),
                    'payment_date' => now()->toDateString(),
                    'amount' => $appliedPaid,
                    'payment_method' => $data['payment_method'] ?? 'cash',
                    'notes' => 'Sale payment',
                ]);

                if (($data['payment_method'] ?? 'cash') === 'cash') {
                    app(CashRegisterService::class)->addCashSale($appliedPaid);
                }
            }

            return $sale->load(['items.product', 'customer', 'payments', 'user']);
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

    public function recordCustomerPayment(array $data): SalePayment
    {
        return DB::transaction(function () use ($data) {
            $amount = (float) $data['amount'];
            if ($amount <= 0) {
                throw new RuntimeException('Invalid payment amount.');
            }

            $payment = SalePayment::query()->create([
                'sale_id' => $data['sale_id'] ?? null,
                'customer_id' => $data['customer_id'],
                'user_id' => Auth::id(),
                'payment_number' => $this->nextPaymentNumber(),
                'payment_date' => $data['payment_date'] ?? now()->toDateString(),
                'amount' => round($amount, 2),
                'payment_method' => $data['payment_method'] ?? 'cash',
                'notes' => $data['notes'] ?? null,
            ]);

            $remainingToApply = $amount;

            $sales = Sale::query()
                ->where('customer_id', $data['customer_id'])
                ->where('status', 'completed')
                ->where('remaining_balance', '>', 0)
                ->when(! empty($data['sale_id']), fn ($q) => $q->where('id', $data['sale_id']))
                ->orderBy('sale_date')
                ->lockForUpdate()
                ->get();

            foreach ($sales as $sale) {
                if ($remainingToApply <= 0) {
                    break;
                }

                $apply = min((float) $sale->remaining_balance, $remainingToApply);
                $sale->amount_paid = round((float) $sale->amount_paid + $apply, 2);
                $sale->remaining_balance = round((float) $sale->grand_total - (float) $sale->amount_paid, 2);
                $sale->payment_status = $this->statusFromAmounts((float) $sale->grand_total, (float) $sale->amount_paid);
                $sale->save();
                $remainingToApply -= $apply;
            }

            if (($data['payment_method'] ?? 'cash') === 'cash') {
                app(CashRegisterService::class)->addCustomerDebtPayment($amount);
            }

            return $payment;
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

    private function statusFromAmounts(float $total, float $paid): string
    {
        if ($paid <= 0) {
            return 'unpaid';
        }

        if ($paid + 0.001 >= $total) {
            return 'paid';
        }

        return 'partial';
    }
}
