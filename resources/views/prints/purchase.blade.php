<!DOCTYPE html>
<html>
<head>
    <meta charset="utf-8">
    <title>Purchase {{ $purchase->invoice_number }}</title>
    <style>
        body { font-family: Arial, sans-serif; font-size: 13px; max-width: 800px; margin: 20px auto; }
        table { width: 100%; border-collapse: collapse; margin-top: 12px; }
        th, td { border: 1px solid #ddd; padding: 6px; text-align: left; }
        .right { text-align: right; }
        @media print { .no-print { display: none; } }
    </style>
</head>
<body>
    <div class="no-print"><button onclick="window.print()">Print</button></div>
    <h2>{{ $settings->shop_name }}</h2>
    <p>Purchase Invoice: <strong>{{ $purchase->invoice_number }}</strong></p>
    <p>Supplier: {{ $purchase->supplier?->name }}</p>
    <p>Date: {{ $purchase->purchase_date->format('Y-m-d') }}</p>
    <table>
        <thead><tr><th>Product</th><th>Qty</th><th>Cost</th><th>Subtotal</th><th>Expiry</th></tr></thead>
        <tbody>
        @foreach($purchase->items as $item)
            <tr>
                <td>{{ $item->product?->name }}</td>
                <td>{{ qty_format($item->quantity) }}</td>
                <td>{{ number_format($item->unit_cost, 2) }}</td>
                <td>{{ number_format($item->subtotal, 2) }}</td>
                <td>{{ $item->expiration_date?->format('Y-m-d') ?? 'N/A' }}</td>
            </tr>
        @endforeach
        </tbody>
    </table>
    <p class="right"><strong>Grand Total: {{ number_format($purchase->grand_total, 2) }} AFN</strong></p>
    <p class="right">Paid: {{ number_format($purchase->amount_paid, 2) }} | Due: {{ number_format($purchase->remaining_balance, 2) }}</p>
</body>
</html>
