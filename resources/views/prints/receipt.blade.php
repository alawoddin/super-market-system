<!DOCTYPE html>
<html>
<head>
    <meta charset="utf-8">
    <title>Receipt {{ $sale->invoice_number }}</title>
    <style>
        @page { margin: 4mm; }
        body { font-family: "Courier New", monospace; font-size: 12px; width: {{ $settings->receipt_width === '58mm' ? '58mm' : '80mm' }}; margin: 0 auto; color: #000; }
        .center { text-align: center; }
        .bold { font-weight: bold; }
        .line { border-top: 1px dashed #000; margin: 6px 0; }
        table { width: 100%; border-collapse: collapse; }
        td { padding: 2px 0; vertical-align: top; }
        .right { text-align: right; }
        @media print { .no-print { display: none; } body { width: {{ $settings->receipt_width }}; } }
    </style>
</head>
<body>
    <div class="no-print" style="margin-bottom:10px;">
        <button onclick="window.print()">Print</button>
        <a href="{{ route('sales.show', $sale) }}">Back</a>
    </div>

    <div class="center bold">{{ $settings->shop_name }}</div>
    @if($settings->address)<div class="center">{{ $settings->address }}</div>@endif
    @if($settings->phone)<div class="center">Tel: {{ $settings->phone }}</div>@endif
    @if($settings->receipt_header)<div class="center">{{ $settings->receipt_header }}</div>@endif
    <div class="line"></div>
    <div>Invoice: {{ $sale->invoice_number }}</div>
    <div>Date: {{ $sale->sale_date->format('Y-m-d H:i') }}</div>
    <div>Cashier: {{ $sale->user?->name }}</div>
    <div>Customer: {{ $sale->customer?->name ?? 'Walk-in' }}</div>
    <div class="line"></div>
    <table>
        @foreach($sale->items as $item)
            <tr>
                <td colspan="3">{{ $item->product?->name }}</td>
            </tr>
            <tr>
                <td>{{ qty_format($item->quantity) }} x {{ number_format($item->unit_price, 2) }}</td>
                <td>@if($item->discount > 0)-{{ number_format($item->discount, 2) }}@endif</td>
                <td class="right">{{ number_format($item->subtotal, 2) }}</td>
            </tr>
        @endforeach
    </table>
    <div class="line"></div>
    <table>
        <tr><td>Subtotal</td><td class="right">{{ number_format($sale->subtotal, 2) }}</td></tr>
        <tr><td>Discount</td><td class="right">{{ number_format($sale->discount, 2) }}</td></tr>
        <tr class="bold"><td>Grand Total</td><td class="right">{{ number_format($sale->grand_total, 2) }} AFN</td></tr>
        <tr><td>Sale Type</td><td class="right">{{ $sale->remaining_balance > 0 ? 'Credit / Partial Cash' : 'Cash Payment' }}</td></tr>
        <tr><td>Cash Paid (AFN)</td><td class="right">{{ number_format($sale->amount_paid, 2) }}</td></tr>
        @if($sale->change_amount > 0)
            <tr><td>Change (AFN)</td><td class="right">{{ number_format($sale->change_amount, 2) }}</td></tr>
        @endif
        @if($sale->remaining_balance > 0)
            <tr><td>Balance Due (AFN)</td><td class="right">{{ number_format($sale->remaining_balance, 2) }}</td></tr>
        @endif
    </table>
    <div class="line"></div>
    <div class="center">{{ $settings->receipt_footer ?: 'Thank you for shopping with us!' }}</div>
    <script>window.onload = () => setTimeout(() => window.print(), 300);</script>
</body>
</html>
