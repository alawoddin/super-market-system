<!DOCTYPE html>
<html>
<head>
    <meta charset="utf-8">
    <title>Payment {{ $payment->payment_number }}</title>
    <style>
        body { font-family: "Courier New", monospace; width: 80mm; margin: 0 auto; font-size: 12px; }
        .center { text-align: center; }
        .line { border-top: 1px dashed #000; margin: 8px 0; }
        @media print { .no-print { display: none; } }
    </style>
</head>
<body>
    <div class="no-print"><button onclick="window.print()">Print</button></div>
    <div class="center"><strong>{{ $settings->shop_name }}</strong></div>
    <div class="center">{{ $type === 'customer' ? 'Customer Payment Receipt' : 'Supplier Payment Receipt' }}</div>
    <div class="line"></div>
    <div>Payment #: {{ $payment->payment_number }}</div>
    <div>Date: {{ $payment->payment_date->format('Y-m-d') }}</div>
    @if($type === 'customer')
        <div>Customer: {{ $payment->customer?->name }}</div>
    @else
        <div>Supplier: {{ $payment->supplier?->name }}</div>
    @endif
    <div>Amount: {{ number_format($payment->amount, 2) }} AFN</div>
    <div>Method: {{ strtoupper($payment->payment_method) }}</div>
    @if($payment->notes)<div>Notes: {{ $payment->notes }}</div>@endif
    <div class="line"></div>
    <div class="center">Thank you</div>
</body>
</html>
