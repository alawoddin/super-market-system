@extends('admin.admin_master')
@section('title','New Sale Return')
@section('admin')
<div class="content"><div class="container-fluid">
<div class="py-3 d-flex justify-content-between align-items-center"><h4 class="m-0">Return Items</h4><a href="{{ route('sale-returns.index') }}" class="btn btn-light btn-sm">Return history</a></div>

<div class="card mb-3"><div class="card-body">
<form method="GET" action="{{ route('sale-returns.create') }}" class="row g-2">
    <div class="col-md-8"><input name="q" value="{{ $q }}" class="form-control form-control-lg" placeholder="Scan barcode, or type product name / invoice number" autofocus autocomplete="off"></div>
    <div class="col-md-2"><button class="btn btn-primary btn-lg w-100">Search</button></div>
</form>
</div></div>

@if($q !== '' || request('sale_id'))
<div class="card"><div class="card-body">
@if($lines->isEmpty())
    <p class="text-muted mb-0">No sale found that can be returned for “{{ $q ?: 'this sale' }}”.</p>
@else
<p class="text-muted">Choose the sale the item came from (newest first), enter how many came back, and press Return.</p>
<div class="table-responsive">
<table class="table align-middle">
<thead><tr><th>Product</th><th>Invoice</th><th>Sold on</th><th class="text-end">Price</th><th class="text-end">Sold</th><th class="text-end">Can return</th><th style="min-width:330px">Return</th></tr></thead>
<tbody>
@foreach($lines as $line)
@php $unit = (float) $line->quantity > 0 ? (float) $line->subtotal / (float) $line->quantity : (float) $line->unit_price; @endphp
<tr>
<td><strong>{{ $line->product?->name }}</strong><div class="small text-muted">{{ $line->product?->barcode }}</div></td>
<td>{{ $line->sale->invoice_number }}</td>
<td>{{ $line->sale->sale_date->format('Y-m-d H:i') }}</td>
<td class="text-end">{{ money($unit) }}</td>
<td class="text-end">{{ qty_format($line->quantity) }}</td>
<td class="text-end fw-semibold">{{ qty_format($line->returnableQuantity()) }}</td>
<td>
<form method="POST" action="{{ route('sale-returns.store') }}" class="d-flex gap-2 align-items-center">@csrf
<input type="hidden" name="sale_item_id" value="{{ $line->id }}">
<input type="number" name="quantity" class="form-control" style="width:90px" min="0.001" step="any" max="{{ qty_format($line->returnableQuantity()) }}" value="1" required>
<div class="form-check text-nowrap m-0"><input type="checkbox" name="restock" value="1" class="form-check-input" id="restock{{ $line->id }}" checked><label class="form-check-label" for="restock{{ $line->id }}">Add to stock</label></div>
<button class="btn btn-warning" data-confirm="Return this item and give the money back?" data-confirm-button="Yes, return">Return</button>
</form>
</td>
</tr>
@endforeach
</tbody></table>
</div>
<p class="small text-muted mb-0">Untick “Add to stock” if the item is broken or expired and cannot be sold again.</p>
@endif
</div></div>
@endif
</div></div>
@endsection
