<!DOCTYPE html>
<html lang="fa" dir="rtl">
<head>
    <meta charset="utf-8">
    <title>Receipt {{ $sale->invoice_number }}</title>
    @php
        $width = $settings->receipt_width === '58mm' ? '58mm' : '80mm';
        $logo = $settings->logo ? asset('storage/'.$settings->logo) : asset('images/receipt/logo.png');
        $qr = $settings->receipt_qr ? asset('storage/'.$settings->receipt_qr) : asset('images/receipt/whatsapp-qr.png');
        $totalQty = $sale->items->sum(fn ($i) => (float) $i->quantity);
    @endphp
    <style>
        @page { margin: 3mm; }
        * { box-sizing: border-box; }
        body { font-family: Tahoma, "Segoe UI", Arial, sans-serif; font-size: 12px; width: {{ $width }}; margin: 0 auto; color: #000; direction: rtl; }
        .center { text-align: center; }
        .logo { width: {{ $width === '58mm' ? '30mm' : '38mm' }}; height: auto; display: block; margin: 0 auto 4px; }
        .shop { font-weight: bold; font-size: 14px; }
        .meta div { margin: 1px 0; }
        .barcode { display: block; margin: 2px 0 2px auto; max-width: 100%; height: 32px; }
        .ltr { direction: ltr; unicode-bidi: embed; }
        table.items { width: 100%; border-collapse: collapse; margin-top: 6px; }
        table.items th, table.items td { border: 1px solid #000; padding: 2px 3px; text-align: center; vertical-align: middle; }
        table.items th { font-weight: bold; }
        table.items td.name { text-align: right; }
        .totals { width: 100%; border-collapse: collapse; margin-top: 6px; }
        .totals td { padding: 3px 0; }
        .totals td:last-child { text-align: left; direction: ltr; }
        .totals .big td { font-size: 15px; font-weight: bold; }
        .totals .rule td { border-bottom: 1px solid #000; }
        .qrbox { border: 1px solid #000; border-radius: 8px; padding: 5px; margin-top: 8px; display: flex; align-items: center; gap: 6px; }
        .qrbox img { width: {{ $width === '58mm' ? '20mm' : '24mm' }}; height: auto; }
        .qrbox .txt { flex: 1; }
        .qrbox .title { font-weight: bold; }
        .qrbox .phone { font-size: 14px; font-weight: bold; direction: ltr; }
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

    <div class="meta" style="margin-top:6px">
        <div>بل نمبر: <span class="ltr">{{ $sale->invoice_number }}</span></div>
        <svg class="barcode" id="billBarcode"></svg>
        <div>تاریخ: <span class="ltr">{{ $sale->sale_date->format('H:i d-m-Y') }}</span></div>
        <div>چاپ توسط: {{ $sale->user?->name }}</div>
    </div>

    <table class="items">
        <thead>
            <tr><th>#</th><th>جنس</th><th>تعداد</th><th>قیمت</th><th>تخفیف</th><th>مجموعه</th></tr>
        </thead>
        <tbody>
        @foreach($sale->items as $i => $item)
            <tr>
                <td>{{ $i + 1 }}</td>
                <td class="name">{{ $item->product?->name }}</td>
                <td>{{ qty_format($item->quantity) }}</td>
                <td>{{ amount($item->unit_price) }}</td>
                <td>{{ amount($item->discount) }}</td>
                <td>{{ amount($item->subtotal) }}</td>
            </tr>
        @endforeach
        </tbody>
    </table>

    <table class="totals">
        <tr><td><strong>مجموعه اولیه</strong></td><td><strong>{{ amount($sale->subtotal) }} AFN</strong></td></tr>
        <tr><td><strong>تخفیف</strong></td><td><strong>{{ amount($sale->discount) }} AFN</strong></td></tr>
        <tr class="big rule"><td>مجموعه نهایی</td><td>{{ amount($sale->grand_total) }} AFN</td></tr>
        <tr class="rule"><td>تعداد مجموعی</td><td>{{ qty_format($totalQty) }}</td></tr>
        <tr class="rule"><td>اخذ پول</td><td>{{ amount((float) $sale->amount_paid + (float) $sale->change_amount) }} AFN</td></tr>
        <tr class="rule"><td>مبادله (باقی پول)</td><td>{{ amount($sale->change_amount) }} AFN</td></tr>
    </table>

    @if($settings->whatsapp || $settings->receipt_qr || file_exists(public_path('images/receipt/whatsapp-qr.png')))
    <div class="qrbox">
        <div class="txt">
            <div class="title">سفارش و تماس از طریق واتساپ</div>
            <div>برای ارتباط، کد را اسکن کنید</div>
            @if($settings->whatsapp)<div class="phone">{{ $settings->whatsapp }}</div>@endif
        </div>
        <img src="{{ $qr }}" alt="WhatsApp QR">
    </div>
    @endif

    <div class="center footer">{{ $settings->receipt_footer ?: 'از خرید شما سپاسگزاریم' }}</div>

    <script src="{{ asset('backend/assets/libs/jsbarcode/JsBarcode.all.min.js') }}"></script>
    <script>
        JsBarcode('#billBarcode', @json($sale->invoice_number), { format: 'CODE128', width: 1.2, height: 32, margin: 0, displayValue: false });
        // onload waits for the logo and QR images, so the paper is never printed half-empty
        window.onload = () => setTimeout(() => window.print(), 300);
        // Printed from inside the POS: put the cursor back in the barcode box afterwards
        window.addEventListener('afterprint', () => {
            if (window.parent !== window) window.parent.document.querySelector('[x-ref="barcode"]')?.focus();
        });
    </script>
</body>
</html>
