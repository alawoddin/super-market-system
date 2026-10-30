@extends('admin.admin_master')
@section('title', 'Dashboard')
@section('admin')
<div class="content"><div class="container-fluid">
<div class="py-3 d-flex justify-content-between align-items-center flex-wrap gap-2">
    <div>
        <h4 class="fs-18 fw-semibold m-0">Today Overview</h4>
        <small class="text-muted">Quick numbers — for selling use <strong>Sell (POS)</strong></small>
    </div>
    <a href="{{ route('pos.index') }}" class="btn btn-success btn-lg">Open Sell (POS)</a>
</div>

<div class="row g-3 mb-3">
    <div class="col-md-4"><a href="{{ route('pos.index') }}" class="btn btn-outline-success w-100 py-3">1. Sell products</a></div>
    <div class="col-md-4"><a href="{{ route('purchases.create') }}" class="btn btn-outline-primary w-100 py-3">2. Buy / add stock</a></div>
    <div class="col-md-4"><a href="{{ route('cash-register.index') }}" class="btn btn-outline-dark w-100 py-3">3. Open / close cash</a></div>
</div>

<div class="row g-3">
@php $cards = [
['Today Sales', money($stats['today_sales']), 'primary'],
['Today Profit', money($stats['today_profit']), 'success'],
['Customer Debts', money($stats['customer_debts']), 'danger'],
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
