@extends('admin.admin_master')
@section('title','Purchase List')
@section('admin')
<div class="content">
<div class="container-fluid">
<div class="py-3 d-flex justify-content-between align-items-center flex-wrap gap-2">
    <div>
        <h4 class="m-0">Purchase List</h4>
        <small class="text-muted">All stock purchases from suppliers (AFN)</small>
    </div>
    <a href="{{ route('purchases.create') }}" class="btn btn-primary">New Purchase</a>
</div>

<div class="card">
<div class="card-body">
<form method="GET" class="row g-2 mb-3">
    <div class="col-md-3">
        <input name="search" value="{{ request('search') }}" class="form-control" placeholder="Invoice # or supplier">
    </div>
    <div class="col-md-2">
        <select name="supplier_id" class="form-select">
            <option value="">All suppliers</option>
            @foreach($suppliers as $supplier)
                <option value="{{ $supplier->id }}" @selected(request('supplier_id') == $supplier->id)>{{ $supplier->name }}</option>
            @endforeach
        </select>
    </div>
    <div class="col-md-2">
        <select name="payment_status" class="form-select">
            <option value="">All payment status</option>
            <option value="paid" @selected(request('payment_status') === 'paid')>Paid</option>
            <option value="partial" @selected(request('payment_status') === 'partial')>Partial</option>
            <option value="unpaid" @selected(request('payment_status') === 'unpaid')>Unpaid</option>
        </select>
    </div>
    <div class="col-md-2">
        <input type="date" name="from" value="{{ request('from') }}" class="form-control" title="From date">
    </div>
    <div class="col-md-2">
        <input type="date" name="to" value="{{ request('to') }}" class="form-control" title="To date">
    </div>
    <div class="col-md-1">
        <button class="btn btn-light w-100">Filter</button>
    </div>
</form>

<div class="table-responsive">
<table class="table table-hover align-middle mb-0">
    <thead>
        <tr>
            <th>Invoice</th>
            <th>Supplier</th>
            <th>Date</th>
            <th class="text-end">Total</th>
            <th class="text-end">Paid</th>
            <th class="text-end">Due</th>
            <th>Payment</th>
            <th></th>
        </tr>
    </thead>
    <tbody>
    @forelse($purchases as $p)
        <tr>
            <td>
                <a href="{{ route('purchases.show', $p) }}" class="fw-semibold text-decoration-none">{{ $p->invoice_number }}</a>
            </td>
            <td>{{ $p->supplier?->name ?? '—' }}</td>
            <td>{{ $p->purchase_date->format('Y-m-d') }}</td>
            <td class="text-end">{{ money($p->grand_total) }}</td>
            <td class="text-end">{{ money($p->amount_paid) }}</td>
            <td class="text-end {{ $p->remaining_balance > 0 ? 'text-danger' : '' }}">{{ money($p->remaining_balance) }}</td>
            <td>
                <span class="badge bg-{{ $p->payment_status === 'paid' ? 'success' : ($p->payment_status === 'partial' ? 'warning' : 'danger') }}">
                    {{ ucfirst($p->payment_status) }}
                </span>
            </td>
            <td class="text-nowrap text-end">
                <a href="{{ route('purchases.show', $p) }}" class="btn btn-sm btn-outline-secondary">View</a>
                <a href="{{ route('purchases.print', $p) }}" target="_blank" class="btn btn-sm btn-outline-dark">Print</a>
            </td>
        </tr>
    @empty
        <tr>
            <td colspan="8" class="text-center text-muted py-5">
                No purchases yet.
                <div class="mt-2"><a href="{{ route('purchases.create') }}" class="btn btn-primary btn-sm">Create first purchase</a></div>
            </td>
        </tr>
    @endforelse
    </tbody>
</table>
</div>

@if($purchases->hasPages())
<div class="mt-3">{{ $purchases->links() }}</div>
@endif
</div>
</div>
</div>
</div>
@endsection
