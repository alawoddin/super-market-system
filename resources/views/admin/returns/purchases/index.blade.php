@extends('admin.admin_master')
@section('title','Purchase Returns')
@section('admin')
<div class="content"><div class="container-fluid">
<div class="py-3 d-flex justify-content-between"><h4 class="m-0">Purchase Returns</h4><a href="{{ route('purchase-returns.create') }}" class="btn btn-primary btn-sm">New Return</a></div>
<div class="card"><div class="card-body">
<table class="table"><thead><tr><th>Return #</th><th>Purchase</th><th>Supplier</th><th>Date</th><th>Total</th></tr></thead><tbody>
@foreach($returns as $r)
<tr><td>{{ $r->return_number }}</td><td>{{ $r->purchase?->invoice_number }}</td><td>{{ $r->supplier?->name }}</td>
<td>{{ $r->return_date->format('Y-m-d H:i') }}</td><td>{{ money($r->total_amount) }}</td></tr>
@endforeach
</tbody></table>{{ $returns->links() }}
</div></div></div></div>
@endsection