@extends('admin.admin_master')
@section('title','Sale Details')
@section('admin')
<div class="content"><div class="container-fluid">
<div class="py-3 d-flex justify-content-between flex-wrap gap-2">
<h4>{{ $sale->invoice_number }}</h4>
<div class="d-flex gap-2">
<a href="{{ route('sales.receipt',$sale) }}" target="_blank" class="btn btn-sm btn-outline-dark">Print Receipt</a>
<a href="{{ route('sale-returns.create',['sale_id'=>$sale->id]) }}" class="btn btn-sm btn-outline-warning">Return</a>
@if($sale->status==='completed')
<form method="POST" action="{{ route('sales.cancel',$sale) }}" data-confirm="Cancel this sale and put the items back in stock?" data-confirm-button="Yes, cancel sale">@csrf
<button class="btn btn-sm btn-outline-danger">Cancel Sale</button></form>
@endif
</div></div>
<div class="card"><div class="card-body">
<p><strong>Cashier:</strong> {{ $sale->user?->name }}</p>
<p><strong>Date:</strong> {{ $sale->sale_date->format('Y-m-d H:i') }}</p>
<table class="table"><thead><tr><th>Product</th><th>Qty</th><th>Price</th><th>Disc</th><th>Subtotal</th></tr></thead><tbody>
@foreach($sale->items as $item)
<tr><td>{{ $item->product?->name }}</td><td>{{ qty_format($item->quantity) }}</td><td>{{ money($item->unit_price) }}</td><td>{{ money($item->discount) }}</td><td>{{ money($item->subtotal) }}</td></tr>
@endforeach
</tbody></table>
<div class="text-end">
<div>Subtotal: {{ money($sale->subtotal) }}</div>
<div>Discount: {{ money($sale->discount) }}</div>
<div class="fs-5 fw-bold">Grand Total: {{ money($sale->grand_total) }}</div>
<div>Paid: {{ money($sale->amount_paid) }} | Due: {{ money($sale->remaining_balance) }} | Change: {{ money($sale->change_amount) }}</div>
<div>COGS: {{ money($sale->cogs) }} | Profit: {{ money($sale->profit) }}</div>
</div>
</div></div></div></div>
@endsection