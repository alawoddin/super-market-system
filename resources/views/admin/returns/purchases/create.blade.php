@extends('admin.admin_master')
@section('title','Purchase Return')
@section('admin')
<div class="content"><div class="container-fluid">
<div class="py-3"><h4>New Purchase Return</h4></div>
@if(!$purchase)
<div class="card"><div class="card-body">
<form method="GET"><label class="form-label">Purchase ID</label>
<input name="purchase_id" class="form-control mb-2" required>
<button class="btn btn-primary">Load Purchase</button></form>
</div></div>
@else
<form method="POST" action="{{ route('purchase-returns.store') }}">@csrf
<input type="hidden" name="purchase_id" value="{{ $purchase->id }}">
<div class="card"><div class="card-body">
<p>{{ $purchase->invoice_number }} — {{ $purchase->supplier?->name }}</p>
<table class="table"><thead><tr><th>Product</th><th>Purchased</th><th>Batch Left</th><th>Return Qty</th></tr></thead><tbody>
@foreach($purchase->items as $i=>$item)
<tr>
<td>{{ $item->product?->name }}<input type="hidden" name="items[{{ $i }}][purchase_item_id]" value="{{ $item->id }}"></td>
<td>{{ qty_format($item->quantity) }}</td>
<td>{{ qty_format($item->stockBatch?->quantity_remaining ?? 0) }}</td>
<td><input type="number" step="0.001" min="0" name="items[{{ $i }}][quantity]" class="form-control" value="0"></td>
</tr>
@endforeach
</tbody></table>
<textarea name="notes" class="form-control mb-3" placeholder="Notes"></textarea>
<button class="btn btn-primary">Save Return</button>
</div></div>
</form>
@endif
</div></div>
@endsection