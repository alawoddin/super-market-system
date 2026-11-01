@extends('admin.admin_master')
@section('title','Cash Register')
@section('admin')
<div class="content"><div class="container-fluid">
<div class="py-3"><h4>Cash Register</h4></div>
@if($current)
<div class="card mb-3 border-success"><div class="card-body">
<h5>Open Register</h5>
<p>Opened: {{ $current->opened_at->format('Y-m-d H:i') }} by {{ $current->user?->name }}</p>
<div class="row g-2">
<div class="col-md-2">Opening: {{ money($current->opening_balance) }}</div>
<div class="col-md-2">Cash Sales: {{ money($current->cash_sales) }}</div>
<div class="col-md-2">Expenses: {{ money($current->cash_expenses) }}</div>
<div class="col-md-2">Refunds: {{ money($current->cash_refunds) }}</div>
<div class="col-md-4">Closing amount: <strong>{{ money($current->expected_closing_balance) }}</strong></div>
</div>
<div class="mt-3 p-3 rounded {{ $currentProfit < 0 ? 'bg-danger-subtle' : 'bg-success-subtle' }}">
    Today's profit so far: <strong class="fs-5 {{ $currentProfit < 0 ? 'text-danger' : 'text-success' }}">{{ money($currentProfit) }}</strong>
    <small class="text-muted ms-2">sales − returns − product cost − expenses</small>
</div>
<form method="POST" action="{{ route('cash-register.close') }}" class="row g-2 mt-3">@csrf
<div class="col-md-8"><input name="notes" class="form-control" placeholder="Notes"></div>
<div class="col-md-4"><button class="btn btn-danger w-100" onclick="return confirm('Close the register for today?')">Close Register</button></div>
</form>
</div></div>
@else
<div class="card mb-3"><div class="card-body">
<h5>Open Cash Register</h5>
<form method="POST" action="{{ route('cash-register.open') }}" class="row g-2">@csrf
<div class="col-md-4"><input type="number" step="0.01" min="0" name="opening_balance" class="form-control" placeholder="Opening balance" required></div>
<div class="col-md-4"><input name="notes" class="form-control" placeholder="Notes"></div>
<div class="col-md-4"><button class="btn btn-success w-100">Open</button></div>
</form>
</div></div>
@endif
<div class="card"><div class="card-body"><h5>History</h5>
<table class="table table-sm"><thead><tr><th>Opened</th><th>Closed</th><th>Opening</th><th>Closing</th><th>Day profit</th><th>Status</th><th></th></tr></thead><tbody>
@foreach($history as $h)
<tr>
<td>{{ $h->opened_at->format('Y-m-d H:i') }}</td><td>{{ $h->closed_at?->format('Y-m-d H:i') ?? '-' }}</td>
<td>{{ money($h->opening_balance) }}</td>
<td>{{ $h->status === 'closed' ? money($h->actual_closing_balance) : '-' }}</td>
<td class="fw-semibold {{ $profits[$h->id] < 0 ? 'text-danger' : 'text-success' }}">{{ money($profits[$h->id]) }}</td>
<td>{{ $h->status }}</td>
<td>@if($h->status === 'closed')<a href="{{ route('cash-register.show',$h) }}" class="btn btn-sm btn-outline-secondary">Day report</a>@endif</td>
</tr>
@endforeach
</tbody></table>{{ $history->links() }}
</div></div></div></div>
@endsection
