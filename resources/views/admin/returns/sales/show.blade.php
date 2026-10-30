@extends('admin.admin_master')
@section('title','Sale Return')
@section('admin')
<div class="content"><div class="container-fluid">
<div class="py-3"><h4>{{ $return->return_number }}</h4></div>
<div class="card"><div class="card-body">
<p>Sale: {{ $return->sale?->invoice_number }}</p>
<p>Refund: {{ money($return->refund_amount) }} ({{ $return->refund_method }})</p>
<table class="table"><thead><tr><th>Product</th><th>Qty</th><th>Amount</th></tr></thead><tbody>
@foreach($return->items as $item)
<tr><td>{{ $item->product?->name }}</td><td>{{ qty_format($item->quantity) }}</td><td>{{ money($item->subtotal) }}</td></tr>
@endforeach
</tbody></table>
</div></div></div></div>
@endsection