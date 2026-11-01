{{-- Day summary for one register. Needs $register and $summary. --}}
@php
    $closed = $register->status === 'closed';
    $diff = (float) $register->cash_difference;
@endphp

<div class="row g-3 mb-3">
    <div class="col-6 col-xl-3"><div class="card h-100"><div class="card-body">
        <div class="small text-muted">Started with</div>
        <div class="fs-4 fw-semibold">{{ money($register->opening_balance) }}</div>
        <div class="small text-muted">{{ $register->opened_at->format('Y-m-d H:i') }}</div>
    </div></div></div>
    <div class="col-6 col-xl-3"><div class="card h-100"><div class="card-body">
        <div class="small text-muted">Cash in drawer should be</div>
        <div class="fs-4 fw-semibold">{{ money($register->expected_closing_balance) }}</div>
        <div class="small text-muted">start + sales − expenses − refunds</div>
    </div></div></div>
    <div class="col-6 col-xl-3"><div class="card h-100"><div class="card-body">
        <div class="small text-muted">{{ $closed ? 'Closed with (counted)' : 'Closed with' }}</div>
        <div class="fs-4 fw-semibold">{{ $closed ? money($register->actual_closing_balance) : '—' }}</div>
        <div class="small text-muted">{{ $closed ? $register->closed_at->format('Y-m-d H:i') : 'Still open' }}</div>
    </div></div></div>
    <div class="col-6 col-xl-3"><div class="card h-100"><div class="card-body">
        <div class="small text-muted">Short / Over</div>
        @if($closed)
            <div class="fs-4 fw-semibold {{ $diff < 0 ? 'text-danger' : ($diff > 0 ? 'text-success' : '') }}">{{ $diff > 0 ? '+' : '' }}{{ money($diff) }}</div>
            <div class="small text-muted">{{ $diff < 0 ? 'Cash missing' : ($diff > 0 ? 'Extra cash' : 'Drawer is correct') }}</div>
        @else
            <div class="fs-4 fw-semibold">—</div><div class="small text-muted">Shown after closing</div>
        @endif
    </div></div></div>
</div>

<div class="row g-3 mb-3">
    <div class="col-lg-5"><div class="card h-100"><div class="card-body">
        <h5 class="mb-3">{{ $closed ? 'Day result' : 'Today so far' }}</h5>
        <table class="table table-sm mb-0">
            <tr><td>Sales ({{ $summary['sales_count'] }} receipts, {{ qty_format($summary['items_sold']) }} items)</td><td class="text-end">{{ money($summary['sales_total']) }}</td></tr>
            <tr><td class="text-muted">Discounts given</td><td class="text-end text-muted">{{ money($summary['discounts']) }}</td></tr>
            <tr><td>Returns (refunded)</td><td class="text-end text-danger">− {{ money($summary['refunds']) }}</td></tr>
            <tr><td>Cost of products sold</td><td class="text-end text-danger">− {{ money($summary['cogs']) }}</td></tr>
            <tr class="fw-semibold"><td>Profit from sales</td><td class="text-end">{{ money($summary['gross_profit']) }}</td></tr>
            <tr><td>Expenses</td><td class="text-end text-danger">− {{ money($summary['expenses_total']) }}</td></tr>
            <tr class="table-success fw-bold"><td>Net profit</td><td class="text-end {{ $summary['net_profit'] < 0 ? 'text-danger' : '' }}">{{ money($summary['net_profit']) }}</td></tr>
        </table>
    </div></div></div>

    <div class="col-lg-7"><div class="card h-100"><div class="card-body">
        <h5 class="mb-3">Products sold</h5>
        <div class="table-responsive" style="max-height:320px;overflow-y:auto">
        <table class="table table-sm mb-0">
            <thead><tr><th>Product</th><th class="text-end">Qty</th><th class="text-end">Sales</th><th class="text-end">Profit</th></tr></thead>
            <tbody>
            @forelse($summary['products'] as $row)
                <tr><td>{{ $row->product?->name ?? 'Deleted product' }}</td><td class="text-end">{{ qty_format($row->qty) }}</td>
                <td class="text-end">{{ money($row->total) }}</td><td class="text-end">{{ money($row->total - $row->cost) }}</td></tr>
            @empty
                <tr><td colspan="4" class="text-muted">No sales yet</td></tr>
            @endforelse
            </tbody>
        </table>
        </div>
    </div></div></div>
</div>

<div class="card mb-3"><div class="card-body">
    <h5 class="mb-3">Expenses</h5>
    <table class="table table-sm mb-0">
        <thead><tr><th>Title</th><th>Description</th><th class="text-end">Amount</th></tr></thead>
        <tbody>
        @forelse($summary['expenses'] as $e)
            <tr><td>{{ $e->title }}</td><td class="text-muted">{{ $e->description }}</td><td class="text-end">{{ money($e->amount) }}</td></tr>
        @empty
            <tr><td colspan="3" class="text-muted">No expenses</td></tr>
        @endforelse
        </tbody>
    </table>
</div></div>
