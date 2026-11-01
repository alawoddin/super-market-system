@extends('admin.admin_master')
@section('title','Cash Register')
@section('admin')
<div class="content"><div class="container-fluid">
<div class="py-3"><h4 class="m-0">Cash Register</h4></div>

@if($current)
<div class="d-flex justify-content-between align-items-center mb-2">
    <h5 class="m-0">Open since {{ $current->opened_at->format('Y-m-d H:i') }} <small class="text-muted">by {{ $current->user?->name }}</small></h5>
    <span class="badge bg-success">Open</span>
</div>

@include('admin.cash-register._summary', ['register' => $current, 'summary' => $summary])

<div class="card mb-3 border-danger"><div class="card-body">
<h5>Close the register</h5>
<p class="text-muted mb-3">Count the cash in the drawer. The box is filled with what the system expects — change it if your count is different.</p>
<form method="POST" action="{{ route('cash-register.close') }}" class="row g-2 align-items-end">@csrf
<div class="col-md-3">
    <label class="form-label">Cash counted (AFN)</label>
    <input type="number" step="0.01" min="0" name="actual_closing_balance" id="actualCash" class="form-control form-control-lg" value="{{ qty_format($current->expected_closing_balance) }}" required>
</div>
<div class="col-md-3">
    <label class="form-label">Short / Over</label>
    <div class="form-control form-control-lg bg-light" id="cashDiff">0.00</div>
</div>
<div class="col-md-3"><label class="form-label">Notes</label><input name="notes" class="form-control form-control-lg" placeholder="Optional"></div>
<div class="col-md-3"><button class="btn btn-danger btn-lg w-100" onclick="return confirm('Close the register for today?')">Close Register</button></div>
</form>
</div></div>
@else
<div class="card mb-3 border-success"><div class="card-body">
<h5>Start the day</h5>
<p class="text-muted mb-3">Enter the cash already in the drawer before you start selling.</p>
<form method="POST" action="{{ route('cash-register.open') }}" class="row g-2 align-items-end">@csrf
<div class="col-md-4"><label class="form-label">Starting cash (AFN)</label><input type="number" step="0.01" min="0" name="opening_balance" class="form-control form-control-lg" placeholder="e.g. 10" required></div>
<div class="col-md-4"><label class="form-label">Notes</label><input name="notes" class="form-control form-control-lg" placeholder="Optional"></div>
<div class="col-md-4"><button class="btn btn-success btn-lg w-100">Open Register</button></div>
</form>
</div></div>
@endif

<div class="card"><div class="card-body"><h5>History</h5>
<div class="table-responsive">
<table class="table table-sm align-middle"><thead><tr><th>Opened</th><th>Closed</th><th class="text-end">Started with</th><th class="text-end">Cash sales</th><th class="text-end">Expenses</th><th class="text-end">Closed with</th><th class="text-end">Short / Over</th><th></th></tr></thead><tbody>
@foreach($history as $h)
@php $d = (float) $h->cash_difference; @endphp
<tr>
<td>{{ $h->opened_at->format('Y-m-d H:i') }}</td><td>{{ $h->closed_at?->format('Y-m-d H:i') ?? '-' }}</td>
<td class="text-end">{{ money($h->opening_balance) }}</td>
<td class="text-end">{{ money($h->cash_sales) }}</td>
<td class="text-end">{{ money($h->cash_expenses) }}</td>
<td class="text-end">{{ $h->actual_closing_balance !== null ? money($h->actual_closing_balance) : '-' }}</td>
<td class="text-end {{ $d < 0 ? 'text-danger' : ($d > 0 ? 'text-success' : '') }}">{{ $h->cash_difference !== null ? ($d > 0 ? '+' : '').money($d) : '-' }}</td>
<td class="text-end">@if($h->status === 'open')<span class="badge bg-success">Open</span>@else<a href="{{ route('cash-register.show',$h) }}" class="btn btn-sm btn-outline-secondary">Day report</a>@endif</td>
</tr>
@endforeach
</tbody></table>
</div>{{ $history->links() }}
</div></div></div></div>
@endsection

@if($current)
@push('scripts')
<script>
(function () {
    const expected = {{ (float) $current->expected_closing_balance }};
    const input = document.getElementById('actualCash');
    const out = document.getElementById('cashDiff');
    function update() {
        const diff = (parseFloat(input.value) || 0) - expected;
        out.textContent = (diff > 0 ? '+' : '') + diff.toFixed(2) + (diff < 0 ? '  (missing)' : diff > 0 ? '  (extra)' : '  (correct)');
        out.className = 'form-control form-control-lg bg-light ' + (diff < 0 ? 'text-danger' : diff > 0 ? 'text-success' : '');
    }
    input.addEventListener('input', update);
    update();
})();
</script>
@endpush
@endif
