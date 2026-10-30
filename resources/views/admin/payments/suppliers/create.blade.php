@extends('admin.admin_master')
@section('title','Supplier Payment')
@section('admin')
<div class="content"><div class="container-fluid"><div class="py-3"><h4>Pay Supplier</h4></div>
<div class="card"><div class="card-body">
<form method="POST" action="{{ route('supplier-payments.store') }}">@csrf
<div class="mb-3"><label class="form-label">Purchase ID</label>
<input type="number" name="purchase_id" class="form-control" value="{{ $purchase->id ?? '' }}" required>
@if($purchase)<small class="text-muted">{{ $purchase->invoice_number }} due {{ money($purchase->remaining_balance) }}</small>@endif
</div>
<div class="mb-3"><label class="form-label">Amount</label><input type="number" step="0.01" name="amount" class="form-control" required></div>
<div class="mb-3"><label class="form-label">Method</label><select name="payment_method" class="form-select"><option value="cash">Cash</option><option value="card">Card</option><option value="other">Other</option></select></div>
<div class="mb-3"><label class="form-label">Notes</label><textarea name="notes" class="form-control"></textarea></div>
<button class="btn btn-primary">Save Payment</button>
</form></div></div></div></div>
@endsection