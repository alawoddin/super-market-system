@extends('admin.admin_master')
@section('title','Supplier Payments')
@section('admin')
<div class="content"><div class="container-fluid">
<div class="py-3 d-flex justify-content-between"><h4 class="m-0">Supplier Payments</h4><a href="{{ route('supplier-payments.create') }}" class="btn btn-primary btn-sm">Pay Supplier</a></div>
<div class="card"><div class="card-body">
<table class="table"><thead><tr><th>Payment #</th><th>Supplier</th><th>Purchase</th><th>Date</th><th>Amount</th><th></th></tr></thead><tbody>
@foreach($payments as $p)
<tr><td>{{ $p->payment_number }}</td><td>{{ $p->supplier?->name }}</td><td>{{ $p->purchase?->invoice_number }}</td>
<td>{{ $p->payment_date->format('Y-m-d') }}</td><td>{{ money($p->amount) }}</td>
<td><a href="{{ route('supplier-payments.receipt',$p) }}" target="_blank" class="btn btn-sm btn-outline-dark">Receipt</a></td></tr>
@endforeach
</tbody></table>{{ $payments->links() }}
</div></div></div></div>
@endsection