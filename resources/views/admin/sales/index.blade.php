@extends('admin.admin_master')
@section('title','Sales')
@section('admin')
<div class="content"><div class="container-fluid">
<div class="py-3 d-flex justify-content-between"><h4 class="m-0">Sales History</h4><a href="{{ route('pos.index') }}" class="btn btn-primary btn-sm">Open POS</a></div>
<div class="card"><div class="card-body">
<form class="row g-2 mb-3">
<div class="col-md-3"><input name="search" value="{{ request('search') }}" class="form-control" placeholder="Invoice"></div>
<div class="col-md-2"><input type="date" name="from" value="{{ request('from') }}" class="form-control"></div>
<div class="col-md-2"><input type="date" name="to" value="{{ request('to') }}" class="form-control"></div>
<div class="col-md-2"><button class="btn btn-light">Filter</button></div>
</form>
<table class="table table-hover"><thead><tr><th>Invoice</th><th>Cashier</th><th>Date</th><th>Total</th><th>Status</th><th></th></tr></thead><tbody>
@foreach($sales as $s)
<tr>
<td>{{ $s->invoice_number }}</td><td>{{ $s->user?->name }}</td><td>{{ $s->sale_date->format('Y-m-d H:i') }}</td>
<td>{{ money($s->grand_total) }}</td>
<td><span class="badge bg-secondary">{{ $s->status }}</span></td>
<td><a href="{{ route('sales.show',$s) }}" class="btn btn-sm btn-outline-secondary">View</a>
<a href="{{ route('sales.receipt',$s) }}" target="_blank" class="btn btn-sm btn-outline-dark">Receipt</a></td>
</tr>
@endforeach
</tbody></table>{{ $sales->links() }}
</div></div></div></div>
@endsection