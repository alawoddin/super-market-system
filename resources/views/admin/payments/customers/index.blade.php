@extends('admin.admin_master')
@section('title','Customer Payments')
@section('admin')
<div class="content"><div class="container-fluid">
<div class="py-3 d-flex justify-content-between"><h4 class="m-0">Customer Payments</h4><a href="{{ route('customer-payments.create') }}" class="btn btn-primary btn-sm">Receive Payment</a></div>
<div class="card"><div class="card-body">
<table class="table"><thead><tr><th>Payment #</th><th>Customer</th><th>Date</th><th>Amount</th><th>Method</th><th></th></tr></thead><tbody>
@foreach($payments as $p)
<tr><td>{{ $p->payment_number }}</td><td>{{ $p->customer?->name }}</td><td>{{ $p->payment_date->format('Y-m-d') }}</td>
<td>{{ money($p->amount) }}</td><td>Cash</td>
<td><a href="{{ route('customer-payments.receipt',$p) }}" target="_blank" class="btn btn-sm btn-outline-dark">Receipt</a></td></tr>
@endforeach
</tbody></table>{{ $payments->links() }}
</div></div></div></div>
@endsection