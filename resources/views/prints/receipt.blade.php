<!DOCTYPE html>
<html lang="fa" dir="rtl">
<head>
    <meta charset="utf-8">
    <title>Receipt {{ $sale->invoice_number }}</title>
    @php
        $width = $settings->receipt_width === '58mm' ? '58mm' : '80mm';
        // Thermal printers can't print to the paper edge: 80mm paper prints ~72mm, 58mm prints ~48mm
        $printable = $width === '58mm' ? '48mm' : '72mm';
        $small = $width === '58mm';
        $logo = $settings->logo ? asset('storage/'.$settings->logo) : asset('images/receipt/logo.png');
        $qr = $settings->receipt_qr ? asset('storage/'.$settings->receipt_qr) : asset('images/receipt/whatsapp-qr.png');
        $units = ['piece' => 'pcs'];
        $paymentMethods = ['cash' => 'نقد', 'card' => 'کارت', 'credit' => 'قرض'];
    @endphp
    <style>
        @page { margin: 0; }
        * { box-sizing: border-box; color: #000 !important; -webkit-print-color-adjust: exact; print-color-adjust: exact; }
        html, body { margin: 0; padding: 0; background: #fff; }
        /* Never wider than what the printer can print, so nothing gets cut off at the edge */
        body { font-family: Tahoma, "Segoe UI", Arial, sans-serif; font-size: {{ $small ? '11px' : '12px' }}; font-weight: bold; width: 100%; max-width: {{ $printable }}; margin: 0 auto; padding: 1mm; direction: rtl; overflow-wrap: anywhere; }
        .center { text-align: center; }
        .ltr { direction: ltr; unicode-bidi: embed; }
        .logo { max-width: 100%; height: auto; display: block; margin: 0 auto 4px; }
        .brand { display: flex; align-items: center; justify-content: center; gap: 4px; margin-bottom: 4px; }
        .brand-cart { width: {{ $small ? '10mm' : '14mm' }}; height: auto; flex-shrink: 0; }
        .brand-text { text-align: center; line-height: 1.2; min-width: 0; }
        .brand-fa { font-size: {{ $small ? '17px' : '22px' }}; font-weight: 900; }
        .brand-en { font-family: Arial, sans-serif; font-size: {{ $small ? '11px' : '14px' }}; font-weight: 900; direction: ltr; }
        .brand-slogan { font-family: "Brush Script MT", "Segoe Script", cursive; font-size: {{ $small ? '12px' : '15px' }}; font-weight: normal; direction: ltr; }
        .invoice-no { font-size: {{ $small ? '12px' : '14px' }}; margin-top: 2px; }
        .barcode { display: block; margin: 2px auto; width: 100%; height: 34px; }
        .datetime { display: flex; justify-content: space-between; align-items: center; gap: 4px; margin: 4px 0; }
        .datetime .labels { line-height: 1.5; }
        .datetime .value { font-size: {{ $small ? '12px' : '14px' }}; white-space: nowrap; }
        /* Fixed columns: a long product name wraps instead of pushing the table off the paper */
        table.items { width: 100%; table-layout: fixed; border-collapse: collapse; margin-top: 6px; }
        table.items th, table.items td { border: 1px solid #000; padding: 2px 1px; text-align: center; vertical-align: middle; }
        table.items th { font-size: {{ $small ? '9px' : '11px' }}; padding: 2px 0; overflow-wrap: normal; }
        table.items td.name { text-align: right; padding-right: 3px; }
        table.items .col-qty { width: 15%; }
        table.items .col-price { width: 22%; }
        table.items .col-total { width: 24%; }
        .totals { width: 100%; border-collapse: collapse; margin-top: 6px; }
        .totals td { padding: 3px 0; }
        .totals td:last-child { text-align: left; direction: ltr; white-space: nowrap; }
        .totals .big td { font-size: {{ $small ? '14px' : '17px' }}; font-weight: 900; }
        .totals .rule td { border-bottom: 1px solid #000; }
        .qrbox { display: flex; align-items: center; gap: 4px; margin-top: 8px; }
        .qrbox .txt { flex: 1; min-width: 0; border: 1px solid #000; border-radius: 8px; padding: 4px 3px; text-align: center; line-height: 1.5; }
        .qrbox .phone { direction: ltr; font-size: {{ $small ? '13px' : '16px' }}; font-weight: 900; letter-spacing: .5px; white-space: nowrap; }
        /* Big enough for a phone to scan through the WhatsApp icon in the middle */
        .qrbox img { width: {{ $small ? '18mm' : '22mm' }}; height: auto; flex-shrink: 0; }
        .footer { margin-top: 6px; }
        @media print { .no-print { display: none; } }
    </style>
</head>
<body>
    <div class="no-print" style="margin-bottom:10px;direction:ltr">
        <button onclick="window.print()">Print</button>
        <a href="{{ route('sales.show', $sale) }}">Back</a>
    </div>

    @if($settings->logo)
        <img src="{{ $logo }}" alt="{{ $settings->shop_name }}" class="logo">
    @else
        {{-- Logo drawn in code so its text can be edited here --}}
        <div class="brand">
            <div class="brand-text">
                <div class="brand-fa">غزنویان هاپیر</div>
                <div class="brand-en">Ghaznowyan Hyper</div>
                <div class="brand-slogan">Better Life</div>
            </div>
            <svg class="brand-cart" viewBox="0 0 64 56" fill="none" stroke="#000" stroke-width="4" stroke-linejoin="round" stroke-linecap="round">
                <path d="M2 4h9l9 32h32l8-24H14"/>
                <path d="M17 21h40M20 29h35M28 12l3 24M40 12l-1 24M50 12l-4 24"/>
                <circle cx="24" cy="47" r="5" fill="#000"/>
                <circle cx="48" cy="47" r="5" fill="#000"/>
            </svg>
        </div>
    @endif
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
            <tr><th>نام کالا</th><th class="col-qty">تعداد</th><th class="col-price">قیمت</th><th class="col-total">مجموعه</th></tr>
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
            <div>سفارش از طریق واتساپ</div>
            <div class="phone">{{ $settings->whatsapp ?: '0747333670' }}</div>
        </div>
        <img src="{{ $qr }}" alt="WhatsApp QR">
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
