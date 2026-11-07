@extends('admin.admin_master')
@section('title','Customer')
@section('admin')
<div class="content">
<div class="container-fluid">
<div class="py-3 d-flex justify-content-between align-items-center flex-wrap gap-2">
    <div>
        <a href="{{ route('customers.index') }}" class="text-muted text-decoration-none small">&larr; Back to customers</a>
        <h4 class="mb-0 mt-1">{{ $customer->name }}</h4>
        @if($customer->phone)<small class="text-muted">{{ $customer->phone }}</small>@endif
    </div>
    <div class="d-flex gap-2">
        <a href="{{ route('customers.edit', $customer) }}" class="btn btn-sm btn-outline-primary">Edit</a>
        <a href="{{ route('customer-payments.create', ['customer_id' => $customer->id]) }}" class="btn btn-sm btn-primary">Receive Payment</a>
    </div>
</div>

@if(session('last_payment_id'))
<div class="card mb-3 border-success">
    <div class="card-body d-flex justify-content-between align-items-center flex-wrap gap-2">
        <div>
            <strong class="text-success">Payment saved</strong>
            <div class="small text-muted">Customer due updated. You can print the receipt.</div>
        </div>
        <div class="d-flex gap-2">
            <a href="{{ route('customer-payments.receipt', session('last_payment_id')) }}" target="_blank" class="btn btn-sm btn-outline-dark">Print receipt</a>
            <a href="{{ route('customers.index') }}" class="btn btn-sm btn-light">Back to list</a>
        </div>
    </div>
</div>
@endif

<div class="card mb-3">
    <div class="card-body d-flex justify-content-between align-items-center">
        <div>
            <div class="text-muted small text-uppercase" style="letter-spacing:.06em">Outstanding debt</div>
            <div class="fs-3 fw-semibold {{ $balance > 0 ? 'text-danger' : 'text-success' }}">{{ money($balance) }}</div>
        </div>
        @if($balance > 0)
            <a href="{{ route('customer-payments.create', ['customer_id' => $customer->id]) }}" class="btn btn-primary">Pay due now</a>
        @endif
    </div>
</div>

<div class="row g-3">
    <div class="col-md-6">
        <div class="card h-100">
            <div class="card-body">
                <h5 class="mb-3">Sales</h5>
                @forelse($customer->sales as $s)
                    <div class="d-flex justify-content-between border-bottom py-2">
                        <a href="{{ route('sales.show', $s) }}">{{ $s->invoice_number }}</a>
                        <span>{{ money($s->grand_total) }} / due {{ money($s->remaining_balance) }}</span>
                    </div>
                @empty
                    <div class="text-muted">No sales yet</div>
                @endforelse
            </div>
        </div>
    </div>
    <div class="col-md-6">
        <div class="card h-100">
            <div class="card-body">
                <h5 class="mb-3">Payments</h5>
                @forelse($customer->payments as $pay)
                    <div class="d-flex justify-content-between border-bottom py-2 align-items-center">
                        <div>
                            <div>{{ $pay->payment_number }}</div>
                            <small class="text-muted">{{ $pay->payment_date?->format('Y-m-d') }}</small>
                        </div>
                        <div class="text-end">
                            <div class="fw-semibold">{{ money($pay->amount) }}</div>
                            <a href="{{ route('customer-payments.receipt', $pay) }}" target="_blank" class="small">Receipt</a>
                        </div>
                    </div>
                @empty
                    <div class="text-muted">No payments yet</div>
                @endforelse
            </div>
        </div>
    </div>
</div>

<div class="mt-3">
    <a href="{{ route('customers.index') }}" class="btn btn-outline-secondary">&larr; Back to customers</a>
</div>
</div>
</div>
@endsection
