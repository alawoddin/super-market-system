<?php
function ensure($path){ $dir=dirname($path); if(!is_dir($dir)) mkdir($dir,0777,true); }
function w($rel,$content){ $path=__DIR__.'/resources/views/'.$rel; ensure($path); file_put_contents($path,$content); echo "OK $rel\n"; }

// Suppliers
w('admin/suppliers/index.blade.php', <<<'BLADE'
@extends('admin.admin_master')
@section('title','Suppliers')
@section('admin')
<div class="content"><div class="container-fluid">
<div class="py-3 d-flex justify-content-between"><h4 class="m-0">Suppliers</h4><a href="{{ route('suppliers.create') }}" class="btn btn-primary btn-sm">Add Supplier</a></div>
<div class="card"><div class="card-body">
<form class="row g-2 mb-3"><div class="col-md-4"><input name="search" value="{{ request('search') }}" class="form-control" placeholder="Search"></div><div class="col-md-2"><button class="btn btn-light">Search</button></div></form>
<table class="table table-hover"><thead><tr><th>Name</th><th>Phone</th><th>Opening</th><th>Outstanding</th><th></th></tr></thead><tbody>
@foreach($suppliers as $s)
<tr><td>{{ $s->name }}</td><td>{{ $s->phone }}</td><td>{{ money($s->opening_balance) }}</td><td>{{ money($s->outstandingBalance()) }}</td>
<td class="text-end"><a href="{{ route('suppliers.show',$s) }}" class="btn btn-sm btn-outline-secondary">View</a>
<a href="{{ route('suppliers.edit',$s) }}" class="btn btn-sm btn-outline-primary">Edit</a></td></tr>
@endforeach
</tbody></table>{{ $suppliers->links() }}
</div></div></div></div>
@endsection
BLADE);

w('admin/suppliers/create.blade.php', <<<'BLADE'
@extends('admin.admin_master')
@section('title','Add Supplier')
@section('admin')
<div class="content"><div class="container-fluid"><div class="py-3"><h4>Add Supplier</h4></div>
<div class="card"><div class="card-body">
<form method="POST" action="{{ route('suppliers.store') }}">@csrf
<div class="mb-3"><label class="form-label">Name</label><input name="name" class="form-control" required></div>
<div class="mb-3"><label class="form-label">Phone</label><input name="phone" class="form-control"></div>
<div class="mb-3"><label class="form-label">Address</label><textarea name="address" class="form-control"></textarea></div>
<div class="mb-3"><label class="form-label">Opening Balance (AFN)</label><input type="number" step="0.01" name="opening_balance" class="form-control" value="0"></div>
<div class="mb-3"><label class="form-label">Notes</label><textarea name="notes" class="form-control"></textarea></div>
<button class="btn btn-primary">Save</button>
</form></div></div></div></div>
@endsection
BLADE);

w('admin/suppliers/edit.blade.php', <<<'BLADE'
@extends('admin.admin_master')
@section('title','Edit Supplier')
@section('admin')
<div class="content"><div class="container-fluid"><div class="py-3"><h4>Edit Supplier</h4></div>
<div class="card"><div class="card-body">
<form method="POST" action="{{ route('suppliers.update',$supplier) }}">@csrf @method('PUT')
<div class="mb-3"><label class="form-label">Name</label><input name="name" class="form-control" value="{{ $supplier->name }}" required></div>
<div class="mb-3"><label class="form-label">Phone</label><input name="phone" class="form-control" value="{{ $supplier->phone }}"></div>
<div class="mb-3"><label class="form-label">Address</label><textarea name="address" class="form-control">{{ $supplier->address }}</textarea></div>
<div class="mb-3"><label class="form-label">Opening Balance</label><input type="number" step="0.01" name="opening_balance" class="form-control" value="{{ $supplier->opening_balance }}"></div>
<div class="mb-3"><label class="form-label">Notes</label><textarea name="notes" class="form-control">{{ $supplier->notes }}</textarea></div>
<div class="form-check mb-3"><input type="checkbox" name="is_active" value="1" class="form-check-input" id="a" @checked($supplier->is_active)><label for="a" class="form-check-label">Active</label></div>
<button class="btn btn-primary">Update</button>
</form></div></div></div></div>
@endsection
BLADE);

w('admin/suppliers/show.blade.php', <<<'BLADE'
@extends('admin.admin_master')
@section('title','Supplier')
@section('admin')
<div class="content"><div class="container-fluid">
<div class="py-3 d-flex justify-content-between"><h4>{{ $supplier->name }}</h4>
<a href="{{ route('supplier-payments.create',['purchase_id'=>optional($supplier->purchases->first())->id]) }}" class="btn btn-sm btn-primary">Record Payment</a></div>
<div class="alert alert-info">Outstanding balance: <strong>{{ money($balance) }}</strong></div>
<div class="row g-3">
<div class="col-md-6"><div class="card"><div class="card-body"><h5>Purchases</h5>
@foreach($supplier->purchases as $p)
<div class="d-flex justify-content-between border-bottom py-2"><a href="{{ route('purchases.show',$p) }}">{{ $p->invoice_number }}</a><span>{{ money($p->grand_total) }}</span></div>
@endforeach
</div></div></div>
<div class="col-md-6"><div class="card"><div class="card-body"><h5>Payments</h5>
@foreach($supplier->payments as $pay)
<div class="d-flex justify-content-between border-bottom py-2"><span>{{ $pay->payment_number }}</span><span>{{ money($pay->amount) }}</span></div>
@endforeach
</div></div></div>
</div></div></div>
@endsection
BLADE);

// Customers similar
w('admin/customers/index.blade.php', <<<'BLADE'
@extends('admin.admin_master')
@section('title','Customers')
@section('admin')
<div class="content"><div class="container-fluid">
<div class="py-3 d-flex justify-content-between"><h4 class="m-0">Customers</h4><a href="{{ route('customers.create') }}" class="btn btn-primary btn-sm">Add Customer</a></div>
<div class="card"><div class="card-body">
<form class="row g-2 mb-3"><div class="col-md-4"><input name="search" value="{{ request('search') }}" class="form-control" placeholder="Search"></div><div class="col-md-2"><button class="btn btn-light">Search</button></div></form>
<table class="table table-hover"><thead><tr><th>Name</th><th>Phone</th><th>Debt</th><th></th></tr></thead><tbody>
@foreach($customers as $c)
<tr><td>{{ $c->name }}</td><td>{{ $c->phone }}</td><td>{{ money($c->outstandingBalance()) }}</td>
<td class="text-end">
<a href="{{ route('customers.show',$c) }}" class="btn btn-sm btn-outline-secondary">View</a>
<a href="{{ route('customers.statement',$c) }}" class="btn btn-sm btn-outline-info">Statement</a>
<a href="{{ route('customers.edit',$c) }}" class="btn btn-sm btn-outline-primary">Edit</a>
</td></tr>
@endforeach
</tbody></table>{{ $customers->links() }}
</div></div></div></div>
@endsection
BLADE);

w('admin/customers/create.blade.php', <<<'BLADE'
@extends('admin.admin_master')
@section('title','Add Customer')
@section('admin')
<div class="content"><div class="container-fluid"><div class="py-3"><h4>Add Customer</h4></div>
<div class="card"><div class="card-body">
<form method="POST" action="{{ route('customers.store') }}">@csrf
<div class="mb-3"><label class="form-label">Name</label><input name="name" class="form-control" required></div>
<div class="mb-3"><label class="form-label">Phone</label><input name="phone" class="form-control"></div>
<div class="mb-3"><label class="form-label">Address</label><textarea name="address" class="form-control"></textarea></div>
<div class="mb-3"><label class="form-label">Opening Balance</label><input type="number" step="0.01" name="opening_balance" class="form-control" value="0"></div>
<div class="mb-3"><label class="form-label">Notes</label><textarea name="notes" class="form-control"></textarea></div>
<button class="btn btn-primary">Save</button>
</form></div></div></div></div>
@endsection
BLADE);

w('admin/customers/edit.blade.php', <<<'BLADE'
@extends('admin.admin_master')
@section('title','Edit Customer')
@section('admin')
<div class="content"><div class="container-fluid"><div class="py-3"><h4>Edit Customer</h4></div>
<div class="card"><div class="card-body">
<form method="POST" action="{{ route('customers.update',$customer) }}">@csrf @method('PUT')
<div class="mb-3"><label class="form-label">Name</label><input name="name" class="form-control" value="{{ $customer->name }}" required></div>
<div class="mb-3"><label class="form-label">Phone</label><input name="phone" class="form-control" value="{{ $customer->phone }}"></div>
<div class="mb-3"><label class="form-label">Address</label><textarea name="address" class="form-control">{{ $customer->address }}</textarea></div>
<div class="mb-3"><label class="form-label">Opening Balance</label><input type="number" step="0.01" name="opening_balance" class="form-control" value="{{ $customer->opening_balance }}"></div>
<div class="mb-3"><label class="form-label">Notes</label><textarea name="notes" class="form-control">{{ $customer->notes }}</textarea></div>
<div class="form-check mb-3"><input type="checkbox" name="is_active" value="1" class="form-check-input" id="a" @checked($customer->is_active)><label for="a" class="form-check-label">Active</label></div>
<button class="btn btn-primary">Update</button>
</form></div></div></div></div>
@endsection
BLADE);

w('admin/customers/show.blade.php', <<<'BLADE'
@extends('admin.admin_master')
@section('title','Customer')
@section('admin')
<div class="content"><div class="container-fluid">
<div class="py-3 d-flex justify-content-between"><h4>{{ $customer->name }}</h4>
<a href="{{ route('customer-payments.create',['customer_id'=>$customer->id]) }}" class="btn btn-sm btn-primary">Receive Payment</a></div>
<div class="alert alert-warning">Outstanding debt: <strong>{{ money($balance) }}</strong></div>
<div class="row g-3">
<div class="col-md-6"><div class="card"><div class="card-body"><h5>Sales</h5>
@foreach($customer->sales as $s)
<div class="d-flex justify-content-between border-bottom py-2"><a href="{{ route('sales.show',$s) }}">{{ $s->invoice_number }}</a><span>{{ money($s->grand_total) }} / due {{ money($s->remaining_balance) }}</span></div>
@endforeach
</div></div></div>
<div class="col-md-6"><div class="card"><div class="card-body"><h5>Payments</h5>
@foreach($customer->payments as $pay)
<div class="d-flex justify-content-between border-bottom py-2"><span>{{ $pay->payment_number }}</span><span>{{ money($pay->amount) }}</span></div>
@endforeach
</div></div></div>
</div></div></div>
@endsection
BLADE);

w('admin/customers/statement.blade.php', <<<'BLADE'
@extends('admin.admin_master')
@section('title','Customer Statement')
@section('admin')
<div class="content"><div class="container-fluid">
<div class="py-3 d-flex justify-content-between"><h4>Statement — {{ $customer->name }}</h4>
<button onclick="window.print()" class="btn btn-sm btn-outline-dark">Print</button></div>
<div class="alert alert-info">Current balance: {{ money($balance) }}</div>
<div class="card"><div class="card-body">
<table class="table"><thead><tr><th>Date</th><th>Type</th><th>Reference</th><th>Debit</th><th>Credit</th></tr></thead><tbody>
<tr><td>—</td><td>Opening</td><td>—</td><td>{{ money($customer->opening_balance) }}</td><td>—</td></tr>
@foreach($sales as $s)
<tr><td>{{ $s->sale_date->format('Y-m-d') }}</td><td>Sale</td><td>{{ $s->invoice_number }}</td><td>{{ money($s->grand_total) }}</td><td>{{ money($s->amount_paid) }}</td></tr>
@endforeach
@foreach($payments as $p)
<tr><td>{{ $p->payment_date->format('Y-m-d') }}</td><td>Payment</td><td>{{ $p->payment_number }}</td><td>—</td><td>{{ money($p->amount) }}</td></tr>
@endforeach
</tbody></table>
</div></div></div></div>
@endsection
BLADE);

echo "people views done\n";
