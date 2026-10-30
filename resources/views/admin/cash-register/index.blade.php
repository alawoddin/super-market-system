@extends('admin.admin_master')
@section('title','Cash Register')
@section('admin')
<div class="content"><div class="container-fluid">
<div class="py-3"><h4>Cash Register</h4></div>
@if($current)
<div class="card mb-3 border-success"><div class="card-body">
<h5>Open Register #{{ $current->id }}</h5>
<p>Opened: {{ $current->opened_at->format('Y-m-d H:i') }} by {{ $current->user?->name }}</p>
<div class="row g-2">
<div class="col-md-3">Opening: {{ money($current->opening_balance) }}</div>
<div class="col-md-3">Cash Sales: {{ money($current->cash_sales) }}</div>
<div class="col-md-3">Debt Payments: {{ money($current->customer_debt_payments) }}</div>
<div class="col-md-3">Supplier Payments: {{ money($current->supplier_payments) }}</div>
<div class="col-md-3">Expenses: {{ money($current->cash_expenses) }}</div>
<div class="col-md-3">Refunds: {{ money($current->cash_refunds) }}</div>
<div class="col-md-3">Expected: <strong>{{ money($current->expected_closing_balance) }}</strong></div>
</div>
<form method="POST" action="{{ route('cash-register.close') }}" class="row g-2 mt-3">@csrf
<div class="col-md-4"><input type="number" step="0.01" name="actual_closing_balance" class="form-control" placeholder="Actual cash count" required></div>
<div class="col-md-4"><input name="notes" class="form-control" placeholder="Notes"></div>
<div class="col-md-4"><button class="btn btn-danger w-100">Close Register</button></div>
</form>
</div></div>
@else
<div class="card mb-3"><div class="card-body">
<h5>Open Cash Register</h5>
<form method="POST" action="{{ route('cash-register.open') }}" class="row g-2">@csrf
<div class="col-md-4"><input type="number" step="0.01" name="opening_balance" class="form-control" placeholder="Opening balance" required></div>
<div class="col-md-4"><input name="notes" class="form-control" placeholder="Notes"></div>
<div class="col-md-4"><button class="btn btn-success w-100">Open</button></div>
</form>
</div></div>
@endif
<div class="card"><div class="card-body"><h5>History</h5>
<table class="table table-sm"><thead><tr><th>ID</th><th>Opened</th><th>Closed</th><th>Expected</th><th>Actual</th><th>Diff</th><th>Status</th></tr></thead><tbody>
@foreach($history as $h)
<tr><td><a href="{{ route('cash-register.show',$h) }}">#{{ $h->id }}</a></td>
<td>{{ $h->opened_at->format('Y-m-d H:i') }}</td><td>{{ $h->closed_at?->format('Y-m-d H:i') ?? '-' }}</td>
<td>{{ money($h->expected_closing_balance) }}</td><td>{{ $h->actual_closing_balance !== null ? money($h->actual_closing_balance) : '-' }}</td>
<td>{{ $h->cash_difference !== null ? money($h->cash_difference) : '-' }}</td><td>{{ $h->status }}</td></tr>
@endforeach
</tbody></table>{{ $history->links() }}
</div></div></div></div>
@endsection