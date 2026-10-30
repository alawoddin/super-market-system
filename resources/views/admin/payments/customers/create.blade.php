@extends('admin.admin_master')
@section('title','Receive Payment')
@section('admin')
<div class="content">
<div class="container-fluid">
<div class="py-3 d-flex justify-content-between align-items-center flex-wrap gap-2">
    <div>
        @if($customer)
            <a href="{{ route('customers.show', $customer) }}" class="text-muted text-decoration-none small">&larr; Back to {{ $customer->name }}</a>
        @else
            <a href="{{ route('customers.index') }}" class="text-muted text-decoration-none small">&larr; Back to customers</a>
        @endif
        <h4 class="m-0 mt-1">Receive Customer Payment</h4>
        <small class="text-muted">Cash repayment for customer debt (AFN)</small>
    </div>
    <div class="d-flex gap-2">
        @if($customer)
            <a href="{{ route('customers.show', $customer) }}" class="btn btn-outline-secondary btn-sm">Customer page</a>
        @endif
        <a href="{{ route('customers.index') }}" class="btn btn-light btn-sm">Customers</a>
    </div>
</div>

<div class="card">
<div class="card-body">
<form method="POST" action="{{ route('customer-payments.store') }}">@csrf
    <div class="mb-3">
        <label class="form-label">Customer</label>
        <select name="customer_id" class="form-select" required>
            <option value="">Select</option>
            @foreach($customers as $c)
                <option value="{{ $c->id }}" @selected(old('customer_id', $customer->id ?? null) == $c->id)>
                    {{ $c->name }} — due {{ money($c->outstandingBalance()) }}
                </option>
            @endforeach
        </select>
    </div>
    <div class="mb-3">
        <label class="form-label">Amount</label>
        <input type="number" step="0.01" name="amount" class="form-control @error('amount') is-invalid @enderror" value="{{ old('amount') }}" required>
        @error('amount')<div class="invalid-feedback">{{ $message }}</div>@enderror
    </div>
    <div class="mb-3">
        <label class="form-label">Date</label>
        <input type="date" name="payment_date" class="form-control" value="{{ old('payment_date', date('Y-m-d')) }}" required>
    </div>
    <input type="hidden" name="payment_method" value="cash">
    <div class="mb-3">
        <label class="form-label">Payment Method</label>
        <input class="form-control" value="Cash (AFN)" disabled>
    </div>
    <div class="mb-3">
        <label class="form-label">Notes</label>
        <textarea name="notes" class="form-control">{{ old('notes') }}</textarea>
    </div>
    <div class="d-flex gap-2">
        <button class="btn btn-primary">Save Payment</button>
        @if($customer)
            <a href="{{ route('customers.show', $customer) }}" class="btn btn-outline-secondary">Cancel / Back</a>
        @else
            <a href="{{ route('customers.index') }}" class="btn btn-outline-secondary">Cancel / Back</a>
        @endif
    </div>
</form>
</div>
</div>
</div>
</div>
@endsection
