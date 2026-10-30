@extends('admin.admin_master')
@section('title','Purchase Report')
@section('admin')
<div class="content"><div class="container-fluid">
<div class="py-3"><h4>Purchase Report</h4></div>
<form class="row g-2 mb-3"><div class="col-md-3"><input type="date" name="from" value="{{ request('from') }}" class="form-control"></div>
<div class="col-md-3"><input type="date" name="to" value="{{ request('to') }}" class="form-control"></div>
<div class="col-md-2"><button class="btn btn-light">Filter</button></div></form>
<div class="alert alert-info">Total purchases: <strong>{{ money($total) }}</strong></div>
<div class="card"><div class="card-body">
<table class="table table-sm"><thead><tr><th>Invoice</th><th>Supplier</th><th>Date</th><th>Total</th><th>Due</th></tr></thead><tbody>
@foreach($rows as $p)
<tr><td>{{ $p->invoice_number }}</td><td>{{ $p->supplier?->name }}</td><td>{{ $p->purchase_date->format('Y-m-d') }}</td>
<td>{{ money($p->grand_total) }}</td><td>{{ money($p->remaining_balance) }}</td></tr>
@endforeach
</tbody></table>{{ $rows->links() }}
</div></div></div></div>
@endsection