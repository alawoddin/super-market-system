@extends('admin.admin_master')
@section('title','Receive Payment')
@section('admin')
<div class="content"><div class="container-fluid"><div class="py-3"><h4>Receive Customer Payment</h4></div>
<div class="card"><div class="card-body">
<form method="POST" action="{{ route('customer-payments.store') }}">@csrf
<div class="mb-3"><label class="form-label">Customer</label>
<select name="customer_id" class="form-select" required>
<option value="">Select</option>
@foreach($customers as $c)
<option value="{{ $c->id }}" @selected(($customer->id ?? null)==$c->id)>{{ $c->name }} — due {{ money($c->outstandingBalance()) }}</option>
@endforeach
</select></div>
<div class="mb-3"><label class="form-label">Amount</label><input type="number" step="0.01" name="amount" class="form-control" required></div>
<div class="mb-3"><label class="form-label">Date</label><input type="date" name="payment_date" class="form-control" value="{{ date('Y-m-d') }}" required></div>
<input type="hidden" name="payment_method" value="cash">
<div class="mb-3"><label class="form-label">Payment Method</label><input class="form-control" value="Cash (AFN)" disabled></div>
<div class="mb-3"><label class="form-label">Notes</label><textarea name="notes" class="form-control"></textarea></div>
<button class="btn btn-primary">Save Payment</button>
</form></div></div></div></div>
@endsection