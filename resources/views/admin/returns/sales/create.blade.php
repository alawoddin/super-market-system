@extends('admin.admin_master')
@section('title','New Sale Return')
@section('admin')
<div class="content"><div class="container-fluid">
<div class="py-3"><h4>New Sale Return</h4></div>
@if(!$sale)
<div class="card"><div class="card-body">
<form method="GET"><label class="form-label">Sale ID</label>
<input name="sale_id" class="form-control mb-2" placeholder="Enter sale ID from sales history" required>
<button class="btn btn-primary">Load Sale</button></form>
</div></div>
@else
<form method="POST" action="{{ route('sale-returns.store') }}">@csrf
<input type="hidden" name="sale_id" value="{{ $sale->id }}">
<div class="card mb-3"><div class="card-body">
<p>Sale: <strong>{{ $sale->invoice_number }}</strong> — {{ money($sale->grand_total) }}</p>
<table class="table"><thead><tr><th>Product</th><th>Sold</th><th>Returned</th><th>Return Qty</th></tr></thead><tbody>
@foreach($sale->items as $i=>$item)
<tr>
<td>{{ $item->product?->name }}<input type="hidden" name="items[{{ $i }}][sale_item_id]" value="{{ $item->id }}"></td>
<td>{{ qty_format($item->quantity) }}</td><td>{{ qty_format($item->returned_quantity) }}</td>
<td><input type="number" step="0.001" min="0" max="{{ $item->returnableQuantity() }}" name="items[{{ $i }}][quantity]" class="form-control" value="0"></td>
</tr>
@endforeach
</tbody></table>
<div class="row g-2">
<input type="hidden" name="refund_method" value="cash">
<div class="col-md-4"><label class="form-label">Refund Method</label><input class="form-control" value="Cash (AFN)" disabled></div>
<div class="col-md-4 form-check mt-4"><input type="checkbox" name="restock" value="1" class="form-check-input" id="restock" checked><label for="restock" class="form-check-label">Restock inventory</label></div>
<div class="col-md-12"><textarea name="notes" class="form-control" placeholder="Notes"></textarea></div>
</div>
<button class="btn btn-primary mt-3">Save Return</button>
</div></div>
</form>
@endif
</div></div>
@endsection