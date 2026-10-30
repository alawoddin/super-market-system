@extends('admin.admin_master')
@section('title','Purchases')
@section('admin')
<div class="content"><div class="container-fluid">
<div class="py-3 d-flex justify-content-between"><h4 class="m-0">Purchases</h4><a href="{{ route('purchases.create') }}" class="btn btn-primary btn-sm">New Purchase</a></div>
<div class="card"><div class="card-body">
<form class="row g-2 mb-3">
<div class="col-md-3"><input name="search" value="{{ request('search') }}" class="form-control" placeholder="Invoice #"></div>
<div class="col-md-2"><input type="date" name="from" value="{{ request('from') }}" class="form-control"></div>
<div class="col-md-2"><input type="date" name="to" value="{{ request('to') }}" class="form-control"></div>
<div class="col-md-2"><button class="btn btn-light">Filter</button></div>
</form>
<table class="table table-hover"><thead><tr><th>Invoice</th><th>Supplier</th><th>Date</th><th>Total</th><th>Paid</th><th>Due</th><th>Status</th><th></th></tr></thead><tbody>
@foreach($purchases as $p)
<tr>
<td>{{ $p->invoice_number }}</td><td>{{ $p->supplier?->name }}</td><td>{{ $p->purchase_date->format('Y-m-d') }}</td>
<td>{{ money($p->grand_total) }}</td><td>{{ money($p->amount_paid) }}</td><td>{{ money($p->remaining_balance) }}</td>
<td><span class="badge bg-{{ $p->payment_status==='paid'?'success':($p->payment_status==='partial'?'warning':'danger') }}">{{ $p->payment_status }}</span></td>
<td><a href="{{ route('purchases.show',$p) }}" class="btn btn-sm btn-outline-secondary">View</a></td>
</tr>
@endforeach
</tbody></table>{{ $purchases->links() }}
</div></div></div></div>
@endsection