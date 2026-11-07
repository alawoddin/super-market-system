<!DOCTYPE html>
<html>
<head>
    <meta charset="utf-8">
    <title>Payment {{ $payment->payment_number }}</title>
    <style>
        @page { margin: 0; }
        /* Same printable width as the sale receipt so nothing is cut off on 80mm paper */
        body { font-family: "Courier New", monospace; width: 100%; max-width: 72mm; margin: 0 auto; font-size: 12px; padding: 1mm; }
        .center { text-align: center; }
        .line { border-top: 1px dashed #000; margin: 8px 0; }
        .no-print { margin-bottom: 12px; display: flex; gap: 8px; flex-wrap: wrap; }
        .no-print a, .no-print button {
            font-family: Arial, sans-serif;
            font-size: 13px;
            padding: 8px 12px;
            border: 1px solid #333;
            background: #fff;
            text-decoration: none;
            color: #000;
            cursor: pointer;
        }
        @media print { .no-print { display: none; } }
    </style>
</head>
<body>
    <div class="no-print">
        <button onclick="window.print()">Print</button>
        @if(!empty($backUrl))
            <a href="{{ $backUrl }}">&larr; Back to customer</a>
        @endif
        <a href="{{ route('customers.index') }}">Customers list</a>
    </div>
    <div class="center"><strong>{{ $settings->shop_name }}</strong></div>
    <div class="center">Customer Payment Receipt</div>
    <div class="line"></div>
    <div>Payment #: {{ $payment->payment_number }}</div>
    <div>Date: {{ $payment->payment_date->format('Y-m-d') }}</div>
    <div>Customer: {{ $payment->customer?->name }}</div>
    <div>Amount: {{ number_format($payment->amount, 2) }} AFN</div>
    <div>Method: Cash</div>
    @if($payment->customer)<div>Debt left: {{ number_format($payment->customer->outstandingBalance(), 2) }} AFN</div>@endif
    @if($payment->notes)<div>Notes: {{ $payment->notes }}</div>@endif
    <div class="line"></div>
    <div class="center">Thank you</div>
</body>
</html>
