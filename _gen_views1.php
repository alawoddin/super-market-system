<?php
function ensure($path) {
    $dir = dirname($path);
    if (!is_dir($dir)) mkdir($dir, 0777, true);
}
function w($rel, $content) {
    $path = __DIR__ . '/resources/views/' . $rel;
    ensure($path);
    file_put_contents($path, $content);
    echo "OK $rel\n";
}

$pageHeader = <<<'HTML'
<div class="py-3 d-flex align-items-sm-center flex-sm-row flex-column">
    <div class="flex-grow-1">
        <h4 class="fs-18 fw-semibold m-0">%s</h4>
    </div>
    <div>%s</div>
</div>
HTML;

// DASHBOARD
w('admin/dashboard.blade.php', <<<'BLADE'
@extends('admin.admin_master')
@section('title', 'Dashboard')
@section('admin')
<div class="content"><div class="container-fluid">
<div class="py-3"><h4 class="fs-18 fw-semibold m-0">Dashboard</h4></div>
<div class="row g-3">
@php $cards = [
['Today Sales', money($stats['today_sales']), 'primary'],
['Today Purchases', money($stats['today_purchases']), 'info'],
['Today Expenses', money($stats['today_expenses']), 'warning'],
['Today Profit', money($stats['today_profit']), 'success'],
['Products', $stats['products'], 'secondary'],
['Categories', $stats['categories'], 'secondary'],
['Customers', $stats['customers'], 'secondary'],
['Suppliers', $stats['suppliers'], 'secondary'],
['Stock Value', money($stats['stock_value']), 'dark'],
['Low Stock', $stats['low_stock'], 'danger'],
['Expiring', $stats['expiring'], 'warning'],
['Expired', $stats['expired'], 'danger'],
['Customer Debts', money($stats['customer_debts']), 'danger'],
['Supplier Debts', money($stats['supplier_debts']), 'danger'],
]; @endphp
@foreach($cards as [$label,$value,$color])
<div class="col-md-6 col-xl-3">
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
    <h5 class="mb-3">Monthly Sales</h5><canvas id="monthlyChart" height="120"></canvas>
  </div></div></div>
</div>

<div class="row g-3">
  <div class="col-xl-6"><div class="card"><div class="card-body">
    <h5>Recent Sales</h5>
    <div class="table-responsive"><table class="table table-sm"><thead><tr><th>Invoice</th><th>Customer</th><th>Total</th></tr></thead><tbody>
    @forelse($recentSales as $s)
      <tr><td><a href="{{ route('sales.show',$s) }}">{{ $s->invoice_number }}</a></td><td>{{ $s->customer?->name ?? 'Walk-in' }}</td><td>{{ money($s->grand_total) }}</td></tr>
    @empty<tr><td colspan="3">No sales yet</td></tr>@endforelse
    </tbody></table></div>
  </div></div></div>
  <div class="col-xl-6"><div class="card"><div class="card-body">
    <h5>Recent Purchases</h5>
    <div class="table-responsive"><table class="table table-sm"><thead><tr><th>Invoice</th><th>Supplier</th><th>Total</th></tr></thead><tbody>
    @forelse($recentPurchases as $p)
      <tr><td><a href="{{ route('purchases.show',$p) }}">{{ $p->invoice_number }}</a></td><td>{{ $p->supplier?->name }}</td><td>{{ money($p->grand_total) }}</td></tr>
    @empty<tr><td colspan="3">No purchases yet</td></tr>@endforelse
    </tbody></table></div>
  </div></div></div>
</div>

<div class="row g-3">
  <div class="col-xl-4"><div class="card"><div class="card-body">
    <h5>Low Stock</h5>
    <ul class="list-group list-group-flush">
    @forelse($lowStock as $p)<li class="list-group-item d-flex justify-content-between"><span>{{ $p->name }}</span><span class="badge bg-danger">{{ qty_format($p->stock_quantity) }}</span></li>
    @empty<li class="list-group-item">All good</li>@endforelse
    </ul>
  </div></div></div>
  <div class="col-xl-4"><div class="card"><div class="card-body">
    <h5>Expiring Soon</h5>
    <ul class="list-group list-group-flush">
    @forelse($expiring as $b)<li class="list-group-item d-flex justify-content-between"><span>{{ $b->product?->name }}</span><span class="badge bg-warning">{{ $b->expiration_date?->format('Y-m-d') }}</span></li>
    @empty<li class="list-group-item">None</li>@endforelse
    </ul>
  </div></div></div>
  <div class="col-xl-4"><div class="card"><div class="card-body">
    <h5>Recent Expenses</h5>
    <ul class="list-group list-group-flush">
    @forelse($recentExpenses as $e)<li class="list-group-item d-flex justify-content-between"><span>{{ $e->title }}</span><span>{{ money($e->amount) }}</span></li>
    @empty<li class="list-group-item">None</li>@endforelse
    </ul>
  </div></div></div>
</div>
</div></div>
@endsection
@push('scripts')
<script>
new Chart(document.getElementById('dailyChart'),{type:'line',data:{labels:@json($daily['labels']),datasets:[{label:'Sales (AFN)',data:@json($daily['data']),borderColor:'#3b82f6',tension:.3,fill:false}]},options:{plugins:{legend:{display:false}}}});
new Chart(document.getElementById('monthlyChart'),{type:'bar',data:{labels:@json($monthly['labels']),datasets:[{label:'Sales (AFN)',data:@json($monthly['data']),backgroundColor:'#10b981'}]},options:{plugins:{legend:{display:false}}}});
</script>
@endpush
BLADE);

echo "Dashboard done\n";
