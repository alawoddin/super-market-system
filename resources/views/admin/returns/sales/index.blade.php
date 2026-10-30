@extends('admin.admin_master')
@section('title','Sale Returns')
@section('admin')
<div class="content"><div class="container-fluid">
<div class="py-3 d-flex justify-content-between"><h4 class="m-0">Sale Returns</h4><a href="{{ route('sale-returns.create') }}" class="btn btn-primary btn-sm">New Return</a></div>
<div class="card"><div class="card-body">
<table class="table"><thead><tr><th>Return #</th><th>Sale</th><th>Date</th><th>Refund</th><th>Method</th><th></th></tr></thead><tbody>
@foreach($returns as $r)
<tr><td>{{ $r->return_number }}</td><td>{{ $r->sale?->invoice_number }}</td><td>{{ $r->return_date->format('Y-m-d H:i') }}</td>
<td>{{ money($r->refund_amount) }}</td><td>{{ $r->refund_method }}</td>
<td><a href="{{ route('sale-returns.show',$r) }}" class="btn btn-sm btn-outline-secondary">View</a></td></tr>
@endforeach
</tbody></table>{{ $returns->links() }}
</div></div></div></div>
@endsection