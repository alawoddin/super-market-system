@extends('admin.admin_master')
@section('title', 'Dashboard')
@section('admin')
<div class="content">
<div class="container-fluid">

<div class="mk-hero">
    <div class="d-flex flex-wrap justify-content-between align-items-end gap-3">
        <div>
            <div class="small text-uppercase mb-2" style="letter-spacing:.12em;opacity:.75">Welcome back</div>
            <h1>{{ Auth::user()->name }}, ready for today?</h1>
            <p>See today’s numbers and all-time totals in one place — then jump into selling or buying stock.</p>
            <div class="mk-hero-actions">
                <a href="{{ route('pos.index') }}" class="btn btn-light">Open POS</a>
                <a href="{{ route('purchases.create') }}" class="btn btn-outline-light">New purchase</a>
                <a href="{{ route('cash-register.index') }}" class="btn btn-outline-light">Cash register</a>
            </div>
        </div>
        <div class="text-end d-none d-lg-block" style="opacity:.9">
            <div class="small text-uppercase" style="letter-spacing:.1em">Currency</div>
            <div class="fs-3 fw-semibold" style="font-family:var(--mk-display)">AFN</div>
        </div>
    </div>
</div>

<div class="row g-3 mb-3 mk-quick">
    <div class="col-md-4">
        <a href="{{ route('pos.index') }}">
            <span class="icon"><i data-feather="shopping-cart"></i></span>
            <span>
                <strong class="d-block">Sell products</strong>
                <small class="text-muted">Fast POS checkout</small>
            </span>
        </a>
    </div>
    <div class="col-md-4">
        <a href="{{ route('purchases.create') }}">
            <span class="icon"><i data-feather="plus-circle"></i></span>
            <span>
                <strong class="d-block">Buy / add stock</strong>
                <small class="text-muted">Purchase from suppliers</small>
            </span>
        </a>
    </div>
    <div class="col-md-4">
        <a href="{{ route('reports.index') }}">
            <span class="icon"><i data-feather="bar-chart-2"></i></span>
            <span>
                <strong class="d-block">Reports</strong>
                <small class="text-muted">Sales & profit details</small>
            </span>
        </a>
    </div>
</div>

<div class="row g-3">
    <div class="col-md-4">
        <div class="card mk-stat mk-stat-sales h-100">
            <div class="card-body">
                <div class="label mb-2">Sales</div>
                <div class="d-flex justify-content-between align-items-end gap-2">
                    <div>
                        <div class="small text-muted">Today</div>
                        <div class="today">{{ money($stats['today_sales']) }}</div>
                    </div>
                    <div class="text-end total">
                        <div class="small text-muted">Total</div>
                        <div class="fw-semibold">{{ money($stats['total_sales']) }}</div>
                    </div>
                </div>
            </div>
        </div>
    </div>
    <div class="col-md-4">
        <div class="card mk-stat mk-stat-buy h-100">
            <div class="card-body">
                <div class="label mb-2">Purchases</div>
                <div class="d-flex justify-content-between align-items-end gap-2">
                    <div>
                        <div class="small text-muted">Today</div>
                        <div class="today">{{ money($stats['today_purchases']) }}</div>
                    </div>
                    <div class="text-end total">
                        <div class="small text-muted">Total</div>
                        <div class="fw-semibold">{{ money($stats['total_purchases']) }}</div>
                    </div>
                </div>
            </div>
        </div>
    </div>
    <div class="col-md-4">
        <div class="card mk-stat mk-stat-profit h-100">
            <div class="card-body">
                <div class="label mb-2">Profit</div>
                <div class="d-flex justify-content-between align-items-end gap-2">
                    <div>
                        <div class="small text-muted">Today</div>
                        <div class="today {{ $stats['today_profit'] < 0 ? 'text-danger' : '' }}">{{ money($stats['today_profit']) }}</div>
                    </div>
                    <div class="text-end total">
                        <div class="small text-muted">Total</div>
                        <div class="fw-semibold {{ $stats['total_profit'] < 0 ? 'text-danger' : '' }}">{{ money($stats['total_profit']) }}</div>
                    </div>
                </div>
                <div class="small text-muted mt-2">Sales − COGS − expenses</div>
            </div>
        </div>
    </div>
</div>

<div class="row g-3 mt-1">
@php $cards = [
['Customer Debts', money($stats['customer_debts']), 'var(--mk-coral)'],
['Supplier Debts', money($stats['supplier_debts']), 'var(--mk-gold)'],
['Stock Value', money($stats['stock_value']), 'var(--mk-sky)'],
['Low Stock', $stats['low_stock'], 'var(--mk-coral)'],
['Products', $stats['products'], 'var(--mk-leaf)'],
['Customers', $stats['customers'], 'var(--mk-pine)'],
]; @endphp
@foreach($cards as $i => [$label,$value,$color])
<div class="col-md-6 col-xl-4">
  <div class="card mk-mini" style="animation-delay: {{ 0.2 + ($i * 0.04) }}s">
    <div class="card-body">
      <div class="fs-14 mb-1 text-muted">{{ $label }}</div>
      <div class="value" style="color: {{ $color }}">{{ $value }}</div>
    </div>
  </div>
</div>
@endforeach
</div>

<div class="row g-3 mt-1">
  <div class="col-xl-7">
    <div class="card mk-mini">
      <div class="card-body">
        <h5 class="mb-3 mk-display">Daily sales</h5>
        <canvas id="dailyChart" height="120"></canvas>
      </div>
    </div>
  </div>
  <div class="col-xl-5">
    <div class="card mk-mini">
      <div class="card-body">
        <h5 class="mb-3 mk-display">Low stock watch</h5>
        <ul class="list-group list-group-flush">
        @forelse($lowStock as $p)
          <li class="list-group-item d-flex justify-content-between px-0 bg-transparent">
            <span>{{ $p->name }}</span>
            <span class="badge bg-danger">{{ qty_format($p->stock_quantity) }}</span>
          </li>
        @empty
          <li class="list-group-item px-0 bg-transparent text-muted">Stock looks healthy</li>
        @endforelse
        </ul>
      </div>
    </div>
  </div>
</div>

</div>
</div>
@endsection

@push('scripts')
<script>
if (window.feather) feather.replace();
new Chart(document.getElementById('dailyChart'), {
    type: 'line',
    data: {
        labels: @json($daily['labels']),
        datasets: [{
            label: 'Sales (AFN)',
            data: @json($daily['data']),
            borderColor: '#1a7a52',
            backgroundColor: 'rgba(26,122,82,0.12)',
            tension: .35,
            fill: true,
            pointRadius: 3,
            pointBackgroundColor: '#0f3d2e'
        }]
    },
    options: {
        plugins: { legend: { display: false } },
        scales: {
            x: { grid: { display: false } },
            y: { grid: { color: 'rgba(15,61,46,0.06)' } }
        }
    }
});
</script>
@endpush
