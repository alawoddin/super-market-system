@extends('admin.admin_master')
@section('title','Product Details')
@section('admin')
<div class="content"><div class="container-fluid">
<div class="py-3 d-flex justify-content-between"><h4>{{ $product->name }}</h4><a href="{{ route('products.edit',$product) }}" class="btn btn-primary btn-sm">Edit</a></div>
<div class="row g-3">
<div class="col-md-6"><div class="card"><div class="card-body">
<p><strong>Barcode:</strong> {{ $product->barcode }}</p>
<p><strong>SKU:</strong> {{ $product->sku ?: '-' }}</p>
<p><strong>Category:</strong> {{ $product->category?->name }}</p>
<p><strong>Unit:</strong> {{ $product->unit }}</p>
<p><strong>Purchase:</strong> {{ money($product->purchase_price) }}</p>
<p><strong>Selling:</strong> {{ money($product->selling_price) }}</p>
<p><strong>Stock:</strong> {{ qty_format($product->stock_quantity) }}</p>
</div></div></div>
<div class="col-md-6"><div class="card"><div class="card-body">
<h5>Batches</h5>
<table class="table table-sm"><thead><tr><th>Batch</th><th>Expiry</th><th>Remaining</th><th>Cost</th></tr></thead><tbody>
@forelse($product->stockBatches as $b)
<tr><td>{{ $b->batch_number }}</td><td>{{ $b->expiration_date?->format('Y-m-d') ?? 'N/A' }}</td><td>{{ qty_format($b->quantity_remaining) }}</td><td>{{ money($b->purchase_price) }}</td></tr>
@empty<tr><td colspan="4">No batches</td></tr>@endforelse
</tbody></table>
</div></div></div>
</div></div></div>
@endsection