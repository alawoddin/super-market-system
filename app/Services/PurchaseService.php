<?php

namespace App\Services;

use App\Models\Product;
use App\Models\Purchase;
use App\Models\PurchaseItem;
use App\Models\PurchasePayment;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use RuntimeException;

class PurchaseService
{
    public function __construct(private InventoryService $inventory)
    {
    }

    public function create(array $data, array $items): Purchase
    {
        if (count($items) === 0) {
            throw new RuntimeException('Add at least one product to the purchase.');
        }

        return DB::transaction(function () use ($data, $items) {
            $subtotal = 0;
            $normalized = [];

            foreach ($items as $item) {
                $qty = (float) $item['quantity'];
                $cost = (float) $item['unit_cost'];
                $discount = (float) ($item['discount'] ?? 0);
                $line = max(0, ($qty * $cost) - $discount);
                $subtotal += $line;

                $normalized[] = [
                    'product_id' => (int) $item['product_id'],
                    'quantity' => $qty,
                    'unit_cost' => $cost,
                    'discount' => $discount,
                    'subtotal' => round($line, 2),
                    'expiration_date' => $item['expiration_date'] ?? null,
                    'batch_number' => $item['batch_number'] ?? null,
                ];
            }

            $discount = (float) ($data['discount'] ?? 0);
            $grandTotal = max(0, $subtotal - $discount);
            $amountPaid = min($grandTotal, (float) ($data['amount_paid'] ?? 0));
            $remaining = round($grandTotal - $amountPaid, 2);

            $purchase = Purchase::query()->create([
                'invoice_number' => $data['invoice_number'] ?? $this->nextInvoiceNumber(),
                'supplier_id' => $data['supplier_id'],
                'user_id' => Auth::id(),
                'purchase_date' => $data['purchase_date'] ?? now()->toDateString(),
                'subtotal' => round($subtotal, 2),
                'discount' => $discount,
                'grand_total' => round($grandTotal, 2),
                'amount_paid' => $amountPaid,
                'remaining_balance' => $remaining,
                'payment_status' => $this->statusFromAmounts($grandTotal, $amountPaid),
                'status' => 'confirmed',
                'notes' => $data['notes'] ?? null,
            ]);

            foreach ($normalized as $row) {
                $purchaseItem = PurchaseItem::query()->create([
                    'purchase_id' => $purchase->id,
                    ...$row,
                ]);

                $product = Product::query()->lockForUpdate()->findOrFail($row['product_id']);
                $product->purchase_price = $row['unit_cost'];
                // Optional sell price set at purchase time (used later for POS profit)
                $itemSource = collect($items)->firstWhere('product_id', $row['product_id'])
                    ?? collect($items)->first(fn ($i) => (int) $i['product_id'] === (int) $row['product_id']);
                if ($itemSource && isset($itemSource['selling_price']) && $itemSource['selling_price'] !== null && $itemSource['selling_price'] !== '') {
                    $product->selling_price = (float) $itemSource['selling_price'];
                }
                $product->save();

                $packNote = '';
                if (! empty($itemSource['cartons']) && ! empty($itemSource['units_per_carton'])) {
                    $packNote = sprintf(
                        ' (%s cartons × %s units @ %s AFN/carton)',
                        $itemSource['cartons'],
                        $itemSource['units_per_carton'],
                        $itemSource['carton_price'] ?? ''
                    );
                }

                $this->inventory->createBatch([
                    'product_id' => $product->id,
                    'purchase_item_id' => $purchaseItem->id,
                    'batch_number' => $row['batch_number'] ?: ('PO-'.$purchase->invoice_number.'-'.$purchaseItem->id),
                    'expiration_date' => $row['expiration_date'],
                    'quantity_received' => $row['quantity'],
                    'purchase_price' => $row['unit_cost'],
                    'reference_type' => Purchase::class,
                    'reference_id' => $purchase->id,
                    'notes' => 'Purchase '.$purchase->invoice_number.$packNote,
                ]);
            }

            if ($amountPaid > 0) {
                $this->recordPayment($purchase, $amountPaid, 'cash', 'Initial cash purchase payment');
            }

            return $purchase->load(['items.product', 'supplier', 'payments']);
        });
    }

    public function recordPayment(Purchase $purchase, float $amount, string $method = 'cash', ?string $notes = null): PurchasePayment
    {
        return DB::transaction(function () use ($purchase, $amount, $notes) {
            $purchase = Purchase::query()->lockForUpdate()->findOrFail($purchase->id);

            if ($amount <= 0) {
                throw new RuntimeException('Payment amount must be greater than zero.');
            }

            if ($amount > (float) $purchase->remaining_balance + 0.001) {
                throw new RuntimeException('Payment exceeds remaining balance.');
            }

            // Supplier payments are cash only (AFN).
            $payment = PurchasePayment::query()->create([
                'purchase_id' => $purchase->id,
                'supplier_id' => $purchase->supplier_id,
                'user_id' => Auth::id(),
                'payment_number' => $this->nextPaymentNumber(),
                'payment_date' => now()->toDateString(),
                'amount' => round($amount, 2),
                'payment_method' => 'cash',
                'notes' => $notes,
            ]);

            $purchase->amount_paid = round((float) $purchase->amount_paid + $amount, 2);
            $purchase->remaining_balance = round((float) $purchase->grand_total - (float) $purchase->amount_paid, 2);
            $purchase->payment_status = $this->statusFromAmounts((float) $purchase->grand_total, (float) $purchase->amount_paid);
            $purchase->save();

            app(CashRegisterService::class)->addSupplierPayment($amount, 'cash');

            return $payment;
        });
    }

    public function nextInvoiceNumber(): string
    {
        $prefix = 'PO-'.now()->format('Ymd').'-';
        $last = Purchase::withTrashed()
            ->where('invoice_number', 'like', $prefix.'%')
            ->orderByDesc('id')
            ->value('invoice_number');

        $seq = $last ? ((int) substr($last, -4)) + 1 : 1;

        return $prefix.str_pad((string) $seq, 4, '0', STR_PAD_LEFT);
    }

    public function nextPaymentNumber(): string
    {
        $prefix = 'PP-'.now()->format('Ymd').'-';
        $last = PurchasePayment::query()
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
