@extends('admin.admin_master')
@section('title','Reports')
@section('admin')
<div class="content"><div class="container-fluid">
<div class="py-3"><h4>Reports & Analytics</h4></div>
<div class="row g-3">
@foreach([
['Sales Report','reports.sales','daily/weekly/monthly sales'],
['Profit & Loss','reports.profit-loss','COGS-based profit'],
['Expense Report','reports.expenses','expense breakdown'],
['Inventory Report','reports.inventory','stock valuation'],
['Cash Register','reports.cash-register','register history'],
] as [$title,$route,$desc])
<div class="col-md-4"><a href="{{ route($route) }}" class="card text-decoration-none"><div class="card-body">
<h5 class="text-dark">{{ $title }}</h5><p class="text-muted mb-0">{{ $desc }}</p>
</div></a></div>
@endforeach
</div></div></div>
@endsection