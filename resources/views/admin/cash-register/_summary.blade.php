{{-- Day summary for one register. Needs $register and $summary. --}}
<div class="row g-3 mb-3">
    <div class="col-md-4"><div class="card h-100"><div class="card-body">
        <div class="small text-muted">Started with</div>
        <div class="fs-4 fw-semibold">{{ money($register->opening_balance) }}</div>
        <div class="small text-muted">{{ $register->opened_at->format('Y-m-d H:i') }}</div>
    </div></div></div>
    <div class="col-md-4"><div class="card h-100"><div class="card-body">
        <div class="small text-muted">Closed with</div>
        <div class="fs-4 fw-semibold">{{ money($register->actual_closing_balance ?? $register->expected_closing_balance) }}</div>
        <div class="small text-muted">{{ $register->closed_at?->format('Y-m-d H:i') ?? 'Still open' }} · start + sales − expenses − refunds</div>
    </div></div></div>
    <div class="col-md-4"><div class="card h-100"><div class="card-body">
        <div class="small text-muted">Net profit</div>
        <div class="fs-4 fw-semibold {{ $summary['net_profit'] < 0 ? 'text-danger' : 'text-success' }}">{{ money($summary['net_profit']) }}</div>
        <div class="small text-muted">sales − returns − product cost − expenses</div>
    </div></div></div>
</div>

<div class="row g-3 mb-3">
    <div class="col-lg-5"><div class="card h-100"><div class="card-body">
        <h5 class="mb-3">Day result</h5>
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
