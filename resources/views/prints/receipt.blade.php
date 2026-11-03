<!DOCTYPE html>
<html lang="fa" dir="rtl">
<head>
    <meta charset="utf-8">
    <title>Receipt {{ $sale->invoice_number }}</title>
    @php
        $width = $settings->receipt_width === '58mm' ? '58mm' : '80mm';
        $logo = $settings->logo ? asset('storage/'.$settings->logo) : asset('images/receipt/logo.png');
        $qr = $settings->receipt_qr ? asset('storage/'.$settings->receipt_qr) : asset('images/receipt/website-qr.png');
        $website = 'www.ghaznowyanhyper.af';
        $units = ['piece' => 'pcs'];
        $paymentMethods = ['cash' => 'نقد', 'card' => 'کارت', 'credit' => 'قرض'];
    @endphp
    <style>
        @page { margin: 3mm; }
        * { box-sizing: border-box; color: #000 !important; -webkit-print-color-adjust: exact; print-color-adjust: exact; }
        body { font-family: Tahoma, "Segoe UI", Arial, sans-serif; font-size: 13px; font-weight: bold; width: {{ $width }}; margin: 0 auto; background: #fff; direction: rtl; }
        .center { text-align: center; }
        .ltr { direction: ltr; unicode-bidi: embed; }
        .logo { width: 100%; height: auto; display: block; margin: 0 auto 4px; }
        .invoice-no { font-size: 15px; letter-spacing: 1px; margin-top: 2px; }
        .barcode { display: block; margin: 2px auto; max-width: 100%; height: 34px; }
        .datetime { display: flex; justify-content: space-between; align-items: center; margin: 4px 0; }
        .datetime .labels { line-height: 1.5; }
        .datetime .value { font-size: 15px; }
        table.items { width: 100%; border-collapse: collapse; margin-top: 6px; }
        table.items th, table.items td { border: 1.5px solid #000; padding: 3px; text-align: center; vertical-align: middle; }
        table.items td.name { text-align: right; }
        .totals { width: 100%; border-collapse: collapse; margin-top: 6px; }
        .totals td { padding: 3px 0; }
        .totals td:last-child { text-align: left; direction: ltr; }
        .totals .big td { font-size: 20px; font-weight: 900; }
        .totals .rule td { border-bottom: 1.5px solid #000; }
        .qrbox { display: flex; align-items: center; gap: 6px; margin-top: 10px; }
        .qrbox .txt { flex: 1; border: 1.5px solid #000; border-radius: 10px; padding: 6px 4px; text-align: center; line-height: 1.6; }
        .qrbox .site { direction: ltr; font-size: {{ $width === '58mm' ? '10px' : '12px' }}; }
        .qrbox img { width: {{ $width === '58mm' ? '18mm' : '22mm' }}; height: auto; }
        .footer { margin-top: 6px; }
        @media print { .no-print { display: none; } }
    </style>
</head>
<body>
    <div class="no-print" style="margin-bottom:10px;direction:ltr">
        <button onclick="window.print()">Print</button>
        <a href="{{ route('sales.show', $sale) }}">Back</a>
    </div>

    <img src="{{ $logo }}" alt="{{ $settings->shop_name }}" class="logo">
    @if($settings->address)<div class="center">{{ $settings->address }}</div>@endif
    @if($settings->phone)<div class="center">تلفن: <span class="ltr">{{ $settings->phone }}</span></div>@endif
    @if($settings->receipt_header)<div class="center">{{ $settings->receipt_header }}</div>@endif

    <div class="center invoice-no ltr">{{ $sale->invoice_number }}</div>
    <svg class="barcode" id="billBarcode"></svg>

    <div class="datetime">
        <div class="labels">تاریخ:<br>وقت:</div>
        <div class="value ltr">{{ $sale->sale_date->format('d.m.Y') }}&nbsp;&nbsp;&nbsp;{{ $sale->sale_date->format('H:i') }}</div>
    </div>

    <table class="items">
        <thead>
            <tr><th>نام کالا</th><th>تعداد</th><th>قیمت</th><th>مجموعه</th></tr>
        </thead>
        <tbody>
        @foreach($sale->items as $item)
            <tr>
                <td class="name">{{ $item->product?->name }}</td>
                <td>{{ qty_format($item->quantity) }}<br>{{ $units[$item->product?->unit] ?? $item->product?->unit }}</td>
                <td>{{ amount($item->unit_price) }}<br>AFN</td>
                <td>{{ amount($item->subtotal) }}<br>AFN</td>
            </tr>
        @endforeach
        </tbody>
    </table>

    <table class="totals">
        <tr><td>مجموعه اولیه:</td><td>{{ amount($sale->subtotal) }} AFN</td></tr>
        <tr><td>تخفیف:</td><td>{{ amount($sale->discount) }} AFN</td></tr>
        <tr class="big rule"><td>مجموعه نهایی:</td><td>{{ amount($sale->grand_total) }}AFN</td></tr>
        <tr class="rule"><td>پول نقد:</td><td>{{ amount((float) $sale->amount_paid + (float) $sale->change_amount) }} AFN</td></tr>
        <tr class="rule"><td>باقی مانده:</td><td>{{ amount($sale->change_amount) }} AFN</td></tr>
        <tr class="rule"><td>پرداخت:</td><td>{{ $paymentMethods[$sale->payment_method] ?? $sale->payment_method }}</td></tr>
    </table>

    <div class="qrbox">
        <div class="txt">
            <div>لینک سفارش آنلاین</div>
            <div class="site">{{ $website }}</div>
        </div>
        <img src="{{ $qr }}" alt="QR">
    </div>

    @if($settings->receipt_footer)<div class="center footer" dir="auto">{{ $settings->receipt_footer }}</div>@endif

    <script src="{{ asset('backend/assets/libs/jsbarcode/JsBarcode.all.min.js') }}"></script>
    <script>
        JsBarcode('#billBarcode', @json($sale->invoice_number), { format: 'CODE128', width: 1.4, height: 34, margin: 0, displayValue: false, lineColor: '#000' });
        // onload waits for the logo and QR images, so the paper is never printed half-empty
        window.onload = () => setTimeout(() => window.print(), 300);
        // Printed from inside the POS: put the cursor back in the barcode box afterwards
        window.addEventListener('afterprint', () => {
            if (window.parent !== window) window.parent.document.querySelector('[x-ref="barcode"]')?.focus();
        });
    </script>
</body>
</html>
