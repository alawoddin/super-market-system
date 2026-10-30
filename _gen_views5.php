<?php
function ensure($path){ $dir=dirname($path); if(!is_dir($dir)) mkdir($dir,0777,true); }
function w($rel,$content){ $path=__DIR__.'/resources/views/'.$rel; ensure($path); file_put_contents($path,$content); echo "OK $rel\n"; }

w('admin/inventory/index.blade.php', <<<'BLADE'
@extends('admin.admin_master')
@section('title','Inventory')
@section('admin')
<div class="content"><div class="container-fluid">
<div class="py-3 d-flex justify-content-between"><h4 class="m-0">Current Stock</h4>
<div><a href="{{ route('inventory.batches') }}" class="btn btn-sm btn-outline-warning">Batches</a>
<a href="{{ route('inventory.movements') }}" class="btn btn-sm btn-outline-secondary">Movements</a></div></div>
<div class="card"><div class="card-body">
<form class="row g-2 mb-3"><div class="col-md-4"><input name="search" value="{{ request('search') }}" class="form-control" placeholder="Search"></div>
<div class="col-md-2"><select name="stock" class="form-select"><option value="">All</option><option value="low" @selected(request('stock')=='low')>Low stock</option></select></div>
<div class="col-md-2"><button class="btn btn-light">Filter</button></div></form>
<table class="table"><thead><tr><th>Product</th><th>Category</th><th>Stock</th><th>Min</th><th>Value</th><th>Adjust</th></tr></thead><tbody>
@foreach($products as $p)
<tr>
<td>{{ $p->name }}</td><td>{{ $p->category?->name }}</td>
<td class="{{ $p->is_low_stock?'text-danger fw-bold':'' }}">{{ qty_format($p->stock_quantity) }}</td>
<td>{{ qty_format($p->min_stock) }}</td>
<td>{{ money($p->stock_quantity * $p->purchase_price) }}</td>
<td>
<form method="POST" action="{{ route('inventory.adjust',$p) }}" class="d-flex gap-1">@csrf
<input type="number" step="0.001" name="stock_quantity" class="form-control form-control-sm" style="width:90px" value="{{ $p->stock_quantity }}" required>
<button class="btn btn-sm btn-outline-primary">Set</button>
</form>
</td></tr>
@endforeach
</tbody></table>{{ $products->links() }}
</div></div></div></div>
@endsection
BLADE);

w('admin/inventory/batches.blade.php', <<<'BLADE'
@extends('admin.admin_master')
@section('title','Stock Batches')
@section('admin')
<div class="content"><div class="container-fluid">
<div class="py-3 d-flex justify-content-between"><h4 class="m-0">Batches / Expiration</h4>
<div class="btn-group">
<a href="?filter=" class="btn btn-sm btn-outline-secondary">All</a>
<a href="?filter=expiring" class="btn btn-sm btn-outline-warning">Expiring</a>
<a href="?filter=expired" class="btn btn-sm btn-outline-danger">Expired</a>
</div></div>
<div class="card"><div class="card-body">
<table class="table"><thead><tr><th>Product</th><th>Batch</th><th>Expiry</th><th>Received</th><th>Remaining</th><th>Cost</th></tr></thead><tbody>
@foreach($batches as $b)
<tr class="{{ $b->is_expired ? 'table-danger' : ($b->is_expiring_soon ? 'table-warning' : '') }}">
<td>{{ $b->product?->name }}</td><td>{{ $b->batch_number }}</td>
<td>{{ $b->expiration_date?->format('Y-m-d') ?? 'N/A' }}</td>
<td>{{ qty_format($b->quantity_received) }}</td><td>{{ qty_format($b->quantity_remaining) }}</td>
<td>{{ money($b->purchase_price) }}</td></tr>
@endforeach
</tbody></table>{{ $batches->links() }}
</div></div></div></div>
@endsection
BLADE);

w('admin/inventory/movements.blade.php', <<<'BLADE'
@extends('admin.admin_master')
@section('title','Stock Movements')
@section('admin')
<div class="content"><div class="container-fluid">
<div class="py-3"><h4>Stock Movement History</h4></div>
<div class="card"><div class="card-body">
<table class="table table-sm"><thead><tr><th>Date</th><th>Product</th><th>Type</th><th>Qty</th><th>Before</th><th>After</th><th>User</th><th>Notes</th></tr></thead><tbody>
@foreach($movements as $m)
<tr>
<td>{{ $m->created_at->format('Y-m-d H:i') }}</td><td>{{ $m->product?->name }}</td><td>{{ $m->type }}</td>
<td>{{ qty_format($m->quantity) }}</td><td>{{ qty_format($m->quantity_before) }}</td><td>{{ qty_format($m->quantity_after) }}</td>
<td>{{ $m->user?->name }}</td><td>{{ $m->notes }}</td></tr>
@endforeach
</tbody></table>{{ $movements->links() }}
</div></div></div></div>
@endsection
BLADE);

w('admin/expenses/index.blade.php', <<<'BLADE'
@extends('admin.admin_master')
@section('title','Expenses')
@section('admin')
<div class="content"><div class="container-fluid">
<div class="py-3 d-flex justify-content-between"><h4 class="m-0">Expenses</h4><a href="{{ route('expenses.create') }}" class="btn btn-primary btn-sm">Add Expense</a></div>
<div class="card"><div class="card-body">
<form class="row g-2 mb-3">
<div class="col-md-2"><input type="date" name="from" value="{{ request('from') }}" class="form-control"></div>
<div class="col-md-2"><input type="date" name="to" value="{{ request('to') }}" class="form-control"></div>
<div class="col-md-3"><select name="category_id" class="form-select"><option value="">All categories</option>@foreach($categories as $c)<option value="{{ $c->id }}" @selected(request('category_id')==$c->id)>{{ $c->name }}</option>@endforeach</select></div>
<div class="col-md-2"><button class="btn btn-light">Filter</button></div>
</form>
<table class="table"><thead><tr><th>Date</th><th>Title</th><th>Category</th><th>Amount</th><th>Method</th><th></th></tr></thead><tbody>
@foreach($expenses as $e)
<tr><td>{{ $e->expense_date->format('Y-m-d') }}</td><td>{{ $e->title }}</td><td>{{ $e->category?->name }}</td><td>{{ money($e->amount) }}</td><td>{{ $e->payment_method }}</td>
<td><a href="{{ route('expenses.edit',$e) }}" class="btn btn-sm btn-outline-primary">Edit</a>
<form action="{{ route('expenses.destroy',$e) }}" method="POST" class="d-inline" onsubmit="return confirm('Delete?')">@csrf @method('DELETE')<button class="btn btn-sm btn-outline-danger">Delete</button></form></td></tr>
@endforeach
</tbody></table>{{ $expenses->links() }}
</div></div></div></div>
@endsection
BLADE);

w('admin/expenses/create.blade.php', <<<'BLADE'
@extends('admin.admin_master')
@section('title','Add Expense')
@section('admin')
<div class="content"><div class="container-fluid"><div class="py-3"><h4>Add Expense</h4></div>
<div class="card"><div class="card-body">
<form method="POST" action="{{ route('expenses.store') }}">@csrf
<div class="mb-3"><label class="form-label">Title</label><input name="title" class="form-control" required></div>
<div class="mb-3"><label class="form-label">Category</label><select name="expense_category_id" class="form-select" required>@foreach($categories as $c)<option value="{{ $c->id }}">{{ $c->name }}</option>@endforeach</select></div>
<div class="mb-3"><label class="form-label">Amount (AFN)</label><input type="number" step="0.01" name="amount" class="form-control" required></div>
<div class="mb-3"><label class="form-label">Date</label><input type="date" name="expense_date" class="form-control" value="{{ date('Y-m-d') }}" required></div>
<div class="mb-3"><label class="form-label">Payment Method</label><select name="payment_method" class="form-select"><option value="cash">Cash</option><option value="card">Card</option><option value="other">Other</option></select></div>
<div class="mb-3"><label class="form-label">Description</label><textarea name="description" class="form-control"></textarea></div>
<button class="btn btn-primary">Save</button>
</form></div></div></div></div>
@endsection
BLADE);

w('admin/expenses/edit.blade.php', <<<'BLADE'
@extends('admin.admin_master')
@section('title','Edit Expense')
@section('admin')
<div class="content"><div class="container-fluid"><div class="py-3"><h4>Edit Expense</h4></div>
<div class="card"><div class="card-body">
<form method="POST" action="{{ route('expenses.update',$expense) }}">@csrf @method('PUT')
<div class="mb-3"><label class="form-label">Title</label><input name="title" class="form-control" value="{{ $expense->title }}" required></div>
<div class="mb-3"><label class="form-label">Category</label><select name="expense_category_id" class="form-select" required>@foreach($categories as $c)<option value="{{ $c->id }}" @selected($expense->expense_category_id==$c->id)>{{ $c->name }}</option>@endforeach</select></div>
<div class="mb-3"><label class="form-label">Amount</label><input type="number" step="0.01" name="amount" class="form-control" value="{{ $expense->amount }}" required></div>
<div class="mb-3"><label class="form-label">Date</label><input type="date" name="expense_date" class="form-control" value="{{ $expense->expense_date->format('Y-m-d') }}" required></div>
<div class="mb-3"><label class="form-label">Payment Method</label><select name="payment_method" class="form-select">@foreach(['cash','card','other'] as $m)<option value="{{ $m }}" @selected($expense->payment_method==$m)>{{ ucfirst($m) }}</option>@endforeach</select></div>
<div class="mb-3"><label class="form-label">Description</label><textarea name="description" class="form-control">{{ $expense->description }}</textarea></div>
<button class="btn btn-primary">Update</button>
</form></div></div></div></div>
@endsection
BLADE);

w('admin/cash-register/index.blade.php', <<<'BLADE'
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
BLADE);

w('admin/cash-register/show.blade.php', <<<'BLADE'
@extends('admin.admin_master')
@section('title','Register Details')
@section('admin')
<div class="content"><div class="container-fluid">
<div class="py-3"><h4>Register #{{ $register->id }}</h4></div>
<div class="card"><div class="card-body">
<p>User: {{ $register->user?->name }}</p>
<p>Opened: {{ $register->opened_at }}</p>
<p>Closed: {{ $register->closed_at ?? '-' }}</p>
<p>Opening: {{ money($register->opening_balance) }}</p>
<p>Cash sales: {{ money($register->cash_sales) }}</p>
<p>Customer debt payments: {{ money($register->customer_debt_payments) }}</p>
<p>Supplier payments: {{ money($register->supplier_payments) }}</p>
<p>Expenses: {{ money($register->cash_expenses) }}</p>
<p>Refunds: {{ money($register->cash_refunds) }}</p>
<p>Expected: {{ money($register->expected_closing_balance) }}</p>
<p>Actual: {{ $register->actual_closing_balance !== null ? money($register->actual_closing_balance) : '-' }}</p>
<p>Difference: {{ $register->cash_difference !== null ? money($register->cash_difference) : '-' }}</p>
<p>Notes: {{ $register->notes }}</p>
</div></div></div></div>
@endsection
BLADE);

echo "ops views done\n";
