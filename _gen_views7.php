<?php
function ensure($path){ $dir=dirname($path); if(!is_dir($dir)) mkdir($dir,0777,true); }
function w($rel,$content){ $path=__DIR__.'/resources/views/'.$rel; ensure($path); file_put_contents($path,$content); echo "OK $rel\n"; }

w('admin/reports/index.blade.php', <<<'BLADE'
@extends('admin.admin_master')
@section('title','Reports')
@section('admin')
<div class="content"><div class="container-fluid">
<div class="py-3"><h4>Reports & Analytics</h4></div>
<div class="row g-3">
@foreach([
['Sales Report','reports.sales','daily/weekly/monthly sales'],
['Purchase Report','reports.purchases','purchase history totals'],
['Profit & Loss','reports.profit-loss','COGS-based profit'],
['Expense Report','reports.expenses','expense breakdown'],
['Inventory Report','reports.inventory','stock valuation'],
['Debt Report','reports.debts','customer & supplier debts'],
['Cash Register','reports.cash-register','register history'],
] as [$title,$route,$desc])
<div class="col-md-4"><a href="{{ route($route) }}" class="card text-decoration-none"><div class="card-body">
<h5 class="text-dark">{{ $title }}</h5><p class="text-muted mb-0">{{ $desc }}</p>
</div></a></div>
@endforeach
</div></div></div>
@endsection
BLADE);

w('admin/reports/sales.blade.php', <<<'BLADE'
@extends('admin.admin_master')
@section('title','Sales Report')
@section('admin')
<div class="content"><div class="container-fluid">
<div class="py-3 d-flex justify-content-between"><h4>Sales Report</h4>
<a href="{{ route('reports.export-pdf', request()->all()+['type'=>'sales']) }}" class="btn btn-sm btn-outline-danger">Export PDF</a></div>
<form class="row g-2 mb-3"><div class="col-md-3"><input type="date" name="from" value="{{ request('from') }}" class="form-control"></div>
<div class="col-md-3"><input type="date" name="to" value="{{ request('to') }}" class="form-control"></div>
<div class="col-md-2"><button class="btn btn-light">Filter</button></div></form>
<div class="row g-2 mb-3">
<div class="col-md-3"><div class="card card-body">Revenue<br><strong>{{ money($summary['revenue']) }}</strong></div></div>
<div class="col-md-3"><div class="card card-body">Cash Received<br><strong>{{ money($summary['cash_received']) }}</strong></div></div>
<div class="col-md-3"><div class="card card-body">Receivables<br><strong>{{ money($summary['receivables']) }}</strong></div></div>
<div class="col-md-3"><div class="card card-body">Profit<br><strong>{{ money($summary['profit']) }}</strong></div></div>
</div>
<div class="card"><div class="card-body">
<table class="table table-sm"><thead><tr><th>Invoice</th><th>Customer</th><th>Date</th><th>Total</th><th>Paid</th><th>Due</th></tr></thead><tbody>
@foreach($rows as $s)
<tr><td>{{ $s->invoice_number }}</td><td>{{ $s->customer?->name ?? 'Walk-in' }}</td><td>{{ $s->sale_date->format('Y-m-d') }}</td>
<td>{{ money($s->grand_total) }}</td><td>{{ money($s->amount_paid) }}</td><td>{{ money($s->remaining_balance) }}</td></tr>
@endforeach
</tbody></table>{{ $rows->links() }}
</div></div></div></div>
@endsection
BLADE);

w('admin/reports/purchases.blade.php', <<<'BLADE'
@extends('admin.admin_master')
@section('title','Purchase Report')
@section('admin')
<div class="content"><div class="container-fluid">
<div class="py-3"><h4>Purchase Report</h4></div>
<form class="row g-2 mb-3"><div class="col-md-3"><input type="date" name="from" value="{{ request('from') }}" class="form-control"></div>
<div class="col-md-3"><input type="date" name="to" value="{{ request('to') }}" class="form-control"></div>
<div class="col-md-2"><button class="btn btn-light">Filter</button></div></form>
<div class="alert alert-info">Total purchases: <strong>{{ money($total) }}</strong></div>
<div class="card"><div class="card-body">
<table class="table table-sm"><thead><tr><th>Invoice</th><th>Supplier</th><th>Date</th><th>Total</th><th>Due</th></tr></thead><tbody>
@foreach($rows as $p)
<tr><td>{{ $p->invoice_number }}</td><td>{{ $p->supplier?->name }}</td><td>{{ $p->purchase_date->format('Y-m-d') }}</td>
<td>{{ money($p->grand_total) }}</td><td>{{ money($p->remaining_balance) }}</td></tr>
@endforeach
</tbody></table>{{ $rows->links() }}
</div></div></div></div>
@endsection
BLADE);

w('admin/reports/profit-loss.blade.php', <<<'BLADE'
@extends('admin.admin_master')
@section('title','Profit & Loss')
@section('admin')
<div class="content"><div class="container-fluid">
<div class="py-3 d-flex justify-content-between"><h4>Profit & Loss</h4>
<a href="{{ route('reports.export-pdf', request()->all()+['type'=>'pnl']) }}" class="btn btn-sm btn-outline-danger">PDF</a></div>
<form class="row g-2 mb-3"><div class="col-md-3"><input type="date" name="from" value="{{ request('from') }}" class="form-control"></div>
<div class="col-md-3"><input type="date" name="to" value="{{ request('to') }}" class="form-control"></div>
<div class="col-md-2"><button class="btn btn-light">Filter</button></div></form>
<div class="card"><div class="card-body">
<table class="table"><tr><th>Sales Revenue</th><td class="text-end">{{ money($report['revenue']) }}</td></tr>
<tr><th>Discounts</th><td class="text-end">{{ money($report['discounts']) }}</td></tr>
<tr><th>COGS (batch costs)</th><td class="text-end">{{ money($report['cogs']) }}</td></tr>
<tr><th>Gross Profit</th><td class="text-end">{{ money($report['profit']) }}</td></tr>
<tr><th>Expenses</th><td class="text-end">{{ money($report['expenses']) }}</td></tr>
<tr class="table-success"><th>Net Profit</th><td class="text-end fw-bold">{{ money($report['net_profit']) }}</td></tr>
<tr><th>Cash Received</th><td class="text-end">{{ money($report['cash_received']) }}</td></tr>
<tr><th>Receivables</th><td class="text-end">{{ money($report['receivables']) }}</td></tr>
</table>
</div></div></div></div>
@endsection
BLADE);

w('admin/reports/expenses.blade.php', <<<'BLADE'
@extends('admin.admin_master')
@section('title','Expense Report')
@section('admin')
<div class="content"><div class="container-fluid">
<div class="py-3"><h4>Expense Report</h4></div>
<form class="row g-2 mb-3"><div class="col-md-3"><input type="date" name="from" value="{{ request('from') }}" class="form-control"></div>
<div class="col-md-3"><input type="date" name="to" value="{{ request('to') }}" class="form-control"></div>
<div class="col-md-2"><button class="btn btn-light">Filter</button></div></form>
<div class="alert alert-warning">Total expenses: <strong>{{ money($total) }}</strong></div>
<div class="card"><div class="card-body">
<table class="table table-sm"><thead><tr><th>Date</th><th>Title</th><th>Category</th><th>Amount</th></tr></thead><tbody>
@foreach($rows as $e)
<tr><td>{{ $e->expense_date->format('Y-m-d') }}</td><td>{{ $e->title }}</td><td>{{ $e->category?->name }}</td><td>{{ money($e->amount) }}</td></tr>
@endforeach
</tbody></table>{{ $rows->links() }}
</div></div></div></div>
@endsection
BLADE);

w('admin/reports/inventory.blade.php', <<<'BLADE'
@extends('admin.admin_master')
@section('title','Inventory Report')
@section('admin')
<div class="content"><div class="container-fluid">
<div class="py-3"><h4>Inventory Report</h4></div>
<div class="row g-2 mb-3">
<div class="col-md-4"><div class="card card-body">Stock Value<br><strong>{{ money($value) }}</strong></div></div>
<div class="col-md-4"><div class="card card-body">Low Stock<br><strong>{{ $lowStock }}</strong></div></div>
<div class="col-md-4"><div class="card card-body">Expired Batches<br><strong>{{ $expired }}</strong></div></div>
</div>
<div class="card"><div class="card-body">
<table class="table table-sm"><thead><tr><th>Product</th><th>Stock</th><th>Cost</th><th>Value</th></tr></thead><tbody>
@foreach($products as $p)
<tr><td>{{ $p->name }}</td><td>{{ qty_format($p->stock_quantity) }}</td><td>{{ money($p->purchase_price) }}</td>
<td>{{ money($p->stock_quantity * $p->purchase_price) }}</td></tr>
@endforeach
</tbody></table>{{ $products->links() }}
</div></div></div></div>
@endsection
BLADE);

w('admin/reports/debts.blade.php', <<<'BLADE'
@extends('admin.admin_master')
@section('title','Debt Report')
@section('admin')
<div class="content"><div class="container-fluid">
<div class="py-3"><h4>Customer & Supplier Debts</h4></div>
<div class="row g-3">
<div class="col-md-6"><div class="card"><div class="card-body"><h5>Customer Debts</h5>
<table class="table table-sm"><thead><tr><th>Name</th><th>Phone</th><th>Balance</th></tr></thead><tbody>
@forelse($customers as $c)
<tr><td>{{ $c['name'] }}</td><td>{{ $c['phone'] }}</td><td>{{ money($c['balance']) }}</td></tr>
@empty<tr><td colspan="3">No debts</td></tr>@endforelse
</tbody></table></div></div></div>
<div class="col-md-6"><div class="card"><div class="card-body"><h5>Supplier Debts</h5>
<table class="table table-sm"><thead><tr><th>Name</th><th>Phone</th><th>Balance</th></tr></thead><tbody>
@forelse($suppliers as $s)
<tr><td>{{ $s['name'] }}</td><td>{{ $s['phone'] }}</td><td>{{ money($s['balance']) }}</td></tr>
@empty<tr><td colspan="3">No debts</td></tr>@endforelse
</tbody></table></div></div></div>
</div></div></div>
@endsection
BLADE);

w('admin/reports/cash-register.blade.php', <<<'BLADE'
@extends('admin.admin_master')
@section('title','Cash Register Report')
@section('admin')
<div class="content"><div class="container-fluid">
<div class="py-3"><h4>Cash Register Report</h4></div>
<div class="card"><div class="card-body">
<table class="table table-sm"><thead><tr><th>ID</th><th>User</th><th>Opened</th><th>Closed</th><th>Expected</th><th>Actual</th><th>Diff</th></tr></thead><tbody>
@foreach($rows as $r)
<tr><td>#{{ $r->id }}</td><td>{{ $r->user?->name }}</td><td>{{ $r->opened_at->format('Y-m-d H:i') }}</td>
<td>{{ $r->closed_at?->format('Y-m-d H:i') ?? '-' }}</td><td>{{ money($r->expected_closing_balance) }}</td>
<td>{{ $r->actual_closing_balance !== null ? money($r->actual_closing_balance) : '-' }}</td>
<td>{{ $r->cash_difference !== null ? money($r->cash_difference) : '-' }}</td></tr>
@endforeach
</tbody></table>{{ $rows->links() }}
</div></div></div></div>
@endsection
BLADE);

w('admin/settings/edit.blade.php', <<<'BLADE'
@extends('admin.admin_master')
@section('title','Settings')
@section('admin')
<div class="content"><div class="container-fluid">
<div class="py-3"><h4>Shop Settings</h4></div>
<div class="card"><div class="card-body">
<form method="POST" action="{{ route('settings.update') }}" enctype="multipart/form-data">@csrf @method('PUT')
<div class="row g-3">
<div class="col-md-6"><label class="form-label">Shop Name</label><input name="shop_name" class="form-control" value="{{ $settings->shop_name }}" required></div>
<div class="col-md-6"><label class="form-label">Phone</label><input name="phone" class="form-control" value="{{ $settings->phone }}"></div>
<div class="col-md-12"><label class="form-label">Address</label><textarea name="address" class="form-control">{{ $settings->address }}</textarea></div>
<div class="col-md-4"><label class="form-label">Currency</label><input name="currency" class="form-control" value="{{ $settings->currency }}" required></div>
<div class="col-md-4"><label class="form-label">Low Stock Alert</label><input type="number" name="low_stock_alert" class="form-control" value="{{ $settings->low_stock_alert }}" required></div>
<div class="col-md-4"><label class="form-label">Expiration Alert (days)</label><input type="number" name="expiration_alert_days" class="form-control" value="{{ $settings->expiration_alert_days }}" required></div>
<div class="col-md-4"><label class="form-label">Receipt Width</label><select name="receipt_width" class="form-select"><option value="80mm" @selected($settings->receipt_width==='80mm')>80mm</option><option value="58mm" @selected($settings->receipt_width==='58mm')>58mm</option></select></div>
<div class="col-md-8"><label class="form-label">Logo</label><input type="file" name="logo" class="form-control" accept="image/*"></div>
<div class="col-md-6"><label class="form-label">Receipt Header</label><textarea name="receipt_header" class="form-control">{{ $settings->receipt_header }}</textarea></div>
<div class="col-md-6"><label class="form-label">Receipt Footer</label><textarea name="receipt_footer" class="form-control">{{ $settings->receipt_footer }}</textarea></div>
</div>
<button class="btn btn-primary mt-3">Save Settings</button>
</form></div></div></div></div>
@endsection
BLADE);

w('admin/backups/index.blade.php', <<<'BLADE'
@extends('admin.admin_master')
@section('title','Backups')
@section('admin')
<div class="content"><div class="container-fluid">
<div class="py-3 d-flex justify-content-between"><h4 class="m-0">Database Backups</h4>
<form method="POST" action="{{ route('backups.create') }}">@csrf<button class="btn btn-primary btn-sm">Create Backup Now</button></form></div>
<div class="card"><div class="card-body">
<table class="table"><thead><tr><th>File</th><th>Size</th><th>Date</th><th></th></tr></thead><tbody>
@forelse($files as $f)
<tr>
<td>{{ $f->getFilename() }}</td>
<td>{{ number_format($f->getSize()/1024,1) }} KB</td>
<td>{{ date('Y-m-d H:i', $f->getMTime()) }}</td>
<td class="text-end">
<a href="{{ route('backups.download',$f->getFilename()) }}" class="btn btn-sm btn-outline-secondary">Download</a>
<form method="POST" action="{{ route('backups.restore') }}" class="d-inline" onsubmit="return confirm('Restore this backup? Current data will be overwritten.')">@csrf
<input type="hidden" name="file" value="{{ $f->getFilename() }}">
<button class="btn btn-sm btn-outline-danger">Restore</button></form>
</td></tr>
@empty<tr><td colspan="4">No backups yet</td></tr>@endforelse
</tbody></table>
</div></div></div></div>
@endsection
BLADE);

echo "reports/settings/backups done\n";
