@extends('admin.admin_master')
@section('title','Debt Report')
@section('admin')
<div class="content"><div class="container-fluid">
<div class="py-3"><h4>Customer & Supplier Debts</h4></div>
<div class="row g-3">
<div class="col-md-6"><div class="card"><div class="card-body"><h5>Customer Debts</h5>
<table class="table table-sm"><thead><tr><th>Name</th><th>Phone</th><th>Balance</th></tr></thead><tbody>
@forelse($customers as $c)
<tr><td>{{ $c['name'] }}</td><td>{{ $c['phone'] }}</td><td>{{ money($c['balance']) }}</td></tr>
@empty<tr><td colspan="3">No debts</td></tr>@endforelse
</tbody></table></div></div></div>
<div class="col-md-6"><div class="card"><div class="card-body"><h5>Supplier Debts</h5>
<table class="table table-sm"><thead><tr><th>Name</th><th>Phone</th><th>Balance</th></tr></thead><tbody>
@forelse($suppliers as $s)
<tr><td>{{ $s['name'] }}</td><td>{{ $s['phone'] }}</td><td>{{ money($s['balance']) }}</td></tr>
@empty<tr><td colspan="3">No debts</td></tr>@endforelse
</tbody></table></div></div></div>
</div></div></div>
@endsection