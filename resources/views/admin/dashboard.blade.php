@extends('admin.admin_master')
@section('title', 'Dashboard')
@section('admin')
<div class="content"><div class="container-fluid">
<div class="py-3 d-flex justify-content-between align-items-center flex-wrap gap-2">
    <div>
        <h4 class="fs-18 fw-semibold m-0">Dashboard</h4>
        <small class="text-muted">Daily and total sales, purchases, profit (AFN)</small>
    </div>
    <a href="{{ route('pos.index') }}" class="btn btn-success btn-lg">Open Sell (POS)</a>
</div>

<div class="row g-3 mb-3">
    <div class="col-md-4"><a href="{{ route('pos.index') }}" class="btn btn-outline-success w-100 py-3">1. Sell products</a></div>
    <div class="col-md-4"><a href="{{ route('purchases.create') }}" class="btn btn-outline-primary w-100 py-3">2. Buy / add stock</a></div>
    <div class="col-md-4"><a href="{{ route('cash-register.index') }}" class="btn btn-outline-dark w-100 py-3">3. Open / close cash</a></div>
</div>

<div class="row g-3">
    <div class="col-md-4">
        <div class="card border-primary h-100">
            <div class="card-body">
                <div class="text-muted mb-2">Sales</div>
                <div class="d-flex justify-content-between align-items-end mb-2">
                    <div>
                        <div class="small text-muted">Today</div>
                        <div class="fs-4 fw-semibold text-primary">{{ money($stats['today_sales']) }}</div>
                    </div>
                    <div class="text-end">
                        <div class="small text-muted">Total</div>
                        <div class="fs-5 fw-semibold">{{ money($stats['total_sales']) }}</div>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <div class="col-md-4">
        <div class="card border-info h-100">
            <div class="card-body">
                <div class="text-muted mb-2">Purchases</div>
                <div class="d-flex justify-content-between align-items-end mb-2">
                    <div>
                        <div class="small text-muted">Today</div>
                        <div class="fs-4 fw-semibold text-info">{{ money($stats['today_purchases']) }}</div>
                    </div>
                    <div class="text-end">
                        <div class="small text-muted">Total</div>
                        <div class="fs-5 fw-semibold">{{ money($stats['total_purchases']) }}</div>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <div class="col-md-4">
        <div class="card border-success h-100">
            <div class="card-body">
                <div class="text-muted mb-2">Profit</div>
                <div class="d-flex justify-content-between align-items-end mb-2">
                    <div>
                        <div class="small text-muted">Today</div>
                        <div class="fs-4 fw-semibold {{ $stats['today_profit'] >= 0 ? 'text-success' : 'text-danger' }}">{{ money($stats['today_profit']) }}</div>
                    </div>
                    <div class="text-end">
                        <div class="small text-muted">Total</div>
                        <div class="fs-5 fw-semibold {{ $stats['total_profit'] >= 0 ? '' : 'text-danger' }}">{{ money($stats['total_profit']) }}</div>
                    </div>
                </div>
                <div class="small text-muted">Profit = sales − cost of goods − expenses</div>
            </div>
        </div>
    </div>
</div>

<div class="row g-3 mt-1">
@php $cards = [
['Customer Debts', money($stats['customer_debts']), 'danger'],
['Supplier Debts', money($stats['supplier_debts']), 'warning'],
['Stock Value', money($stats['stock_value']), 'primary'],
['Low Stock', $stats['low_stock'], 'warning'],
['Products', $stats['products'], 'secondary'],
['Customers', $stats['customers'], 'secondary'],
]; @endphp
@foreach($cards as [$label,$value,$color])
<div class="col-md-6 col-xl-4">
  <div class="card"><div class="card-body">
    <div class="fs-14 mb-1 text-muted">{{ $label }}</div>
    <div class="fs-22 fw-semibold text-{{ $color }}">{{ $value }}</div>
  </div></div>
</div>
@endforeach
</div>

<div class="row g-3 mt-1">
  <div class="col-xl-6"><div class="card"><div class="card-body">
    <h5 class="mb-3">Daily Sales</h5><canvas id="dailyChart" height="120"></canvas>
  </div></div></div>
  <div class="col-xl-6"><div class="card"><div class="card-body">
    <h5>Low Stock</h5>
    <ul class="list-group list-group-flush">
    @forelse($lowStock as $p)
      <li class="list-group-item d-flex justify-content-between"><span>{{ $p->name }}</span><span class="badge bg-danger">{{ qty_format($p->stock_quantity) }}</span></li>
    @empty
      <li class="list-group-item text-muted">All good</li>
    @endforelse
    </ul>
  </div></div></div>
</div>
</div></div>
@endsection
@push('scripts')
<script>
new Chart(document.getElementById('dailyChart'),{type:'line',data:{labels:@json($daily['labels']),datasets:[{label:'Sales (AFN)',data:@json($daily['data']),borderColor:'#3b82f6',tension:.3,fill:false}]},options:{plugins:{legend:{display:false}}}});
</script>
@endpush
