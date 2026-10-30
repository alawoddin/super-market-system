@extends('admin.admin_master')
@section('title','Purchase Details')
@section('admin')
<div class="content"><div class="container-fluid">
<div class="py-3 d-flex justify-content-between flex-wrap gap-2">
<h4>{{ $purchase->invoice_number }}</h4>
<div>
<a href="{{ route('purchases.print',$purchase) }}" target="_blank" class="btn btn-sm btn-outline-dark">Print</a>
<a href="{{ route('purchase-returns.create',['purchase_id'=>$purchase->id]) }}" class="btn btn-sm btn-outline-warning">Return</a>
</div></div>
<div class="row g-3">
<div class="col-md-8"><div class="card"><div class="card-body">
<p><strong>Supplier:</strong> {{ $purchase->supplier?->name }}</p>
<p><strong>Date:</strong> {{ $purchase->purchase_date->format('Y-m-d') }}</p>
<table class="table"><thead><tr><th>Product</th><th>Qty</th><th>Cost</th><th>Subtotal</th><th>Expiry</th></tr></thead><tbody>
@foreach($purchase->items as $item)
<tr><td>{{ $item->product?->name }}</td><td>{{ qty_format($item->quantity) }}</td><td>{{ money($item->unit_cost) }}</td><td>{{ money($item->subtotal) }}</td><td>{{ $item->expiration_date?->format('Y-m-d') ?? 'N/A' }}</td></tr>
@endforeach
</tbody></table>
</div></div></div>
<div class="col-md-4">
<div class="card mb-3"><div class="card-body">
<div class="d-flex justify-content-between"><span>Grand Total</span><strong>{{ money($purchase->grand_total) }}</strong></div>
<div class="d-flex justify-content-between"><span>Paid</span><strong>{{ money($purchase->amount_paid) }}</strong></div>
<div class="d-flex justify-content-between"><span>Due</span><strong class="text-danger">{{ money($purchase->remaining_balance) }}</strong></div>
</div></div>
@if($purchase->remaining_balance > 0)
<div class="card"><div class="card-body">
<h5>Record Payment</h5>
<form method="POST" action="{{ route('purchases.payments',$purchase) }}">@csrf
<input type="number" step="0.01" name="amount" class="form-control mb-2" max="{{ $purchase->remaining_balance }}" required>
<input type="hidden" name="payment_method" value="cash">
<div class="form-control mb-2 bg-light">Cash payment (AFN)</div>
<textarea name="notes" class="form-control mb-2" placeholder="Notes"></textarea>
<button class="btn btn-primary w-100">Pay</button>
</form>
</div></div>
@endif
</div></div></div></div>
@endsection