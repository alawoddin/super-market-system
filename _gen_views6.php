<?php
function ensure($path){ $dir=dirname($path); if(!is_dir($dir)) mkdir($dir,0777,true); }
function w($rel,$content){ $path=__DIR__.'/resources/views/'.$rel; ensure($path); file_put_contents($path,$content); echo "OK $rel\n"; }

w('admin/returns/sales/index.blade.php', <<<'BLADE'
@extends('admin.admin_master')
@section('title','Sale Returns')
@section('admin')
<div class="content"><div class="container-fluid">
<div class="py-3 d-flex justify-content-between"><h4 class="m-0">Sale Returns</h4><a href="{{ route('sale-returns.create') }}" class="btn btn-primary btn-sm">New Return</a></div>
<div class="card"><div class="card-body">
<table class="table"><thead><tr><th>Return #</th><th>Sale</th><th>Date</th><th>Refund</th><th>Method</th><th></th></tr></thead><tbody>
@foreach($returns as $r)
<tr><td>{{ $r->return_number }}</td><td>{{ $r->sale?->invoice_number }}</td><td>{{ $r->return_date->format('Y-m-d H:i') }}</td>
<td>{{ money($r->refund_amount) }}</td><td>{{ $r->refund_method }}</td>
<td><a href="{{ route('sale-returns.show',$r) }}" class="btn btn-sm btn-outline-secondary">View</a></td></tr>
@endforeach
</tbody></table>{{ $returns->links() }}
</div></div></div></div>
@endsection
BLADE);

w('admin/returns/sales/create.blade.php', <<<'BLADE'
@extends('admin.admin_master')
@section('title','New Sale Return')
@section('admin')
<div class="content"><div class="container-fluid">
<div class="py-3"><h4>New Sale Return</h4></div>
@if(!$sale)
<div class="card"><div class="card-body">
<form method="GET"><label class="form-label">Sale ID</label>
<input name="sale_id" class="form-control mb-2" placeholder="Enter sale ID from sales history" required>
<button class="btn btn-primary">Load Sale</button></form>
</div></div>
@else
<form method="POST" action="{{ route('sale-returns.store') }}">@csrf
<input type="hidden" name="sale_id" value="{{ $sale->id }}">
<div class="card mb-3"><div class="card-body">
<p>Sale: <strong>{{ $sale->invoice_number }}</strong> — {{ money($sale->grand_total) }}</p>
<table class="table"><thead><tr><th>Product</th><th>Sold</th><th>Returned</th><th>Return Qty</th></tr></thead><tbody>
@foreach($sale->items as $i=>$item)
<tr>
<td>{{ $item->product?->name }}<input type="hidden" name="items[{{ $i }}][sale_item_id]" value="{{ $item->id }}"></td>
<td>{{ qty_format($item->quantity) }}</td><td>{{ qty_format($item->returned_quantity) }}</td>
<td><input type="number" step="0.001" min="0" max="{{ $item->returnableQuantity() }}" name="items[{{ $i }}][quantity]" class="form-control" value="0"></td>
</tr>
@endforeach
</tbody></table>
<div class="row g-2">
<div class="col-md-4"><label class="form-label">Refund Method</label><select name="refund_method" class="form-select"><option value="cash">Cash</option><option value="card">Card</option><option value="other">Other</option></select></div>
<div class="col-md-4 form-check mt-4"><input type="checkbox" name="restock" value="1" class="form-check-input" id="restock" checked><label for="restock" class="form-check-label">Restock inventory</label></div>
<div class="col-md-12"><textarea name="notes" class="form-control" placeholder="Notes"></textarea></div>
</div>
<button class="btn btn-primary mt-3">Save Return</button>
</div></div>
</form>
@endif
</div></div>
@endsection
BLADE);

w('admin/returns/sales/show.blade.php', <<<'BLADE'
@extends('admin.admin_master')
@section('title','Sale Return')
@section('admin')
<div class="content"><div class="container-fluid">
<div class="py-3"><h4>{{ $return->return_number }}</h4></div>
<div class="card"><div class="card-body">
<p>Sale: {{ $return->sale?->invoice_number }}</p>
<p>Refund: {{ money($return->refund_amount) }} ({{ $return->refund_method }})</p>
<table class="table"><thead><tr><th>Product</th><th>Qty</th><th>Amount</th></tr></thead><tbody>
@foreach($return->items as $item)
<tr><td>{{ $item->product?->name }}</td><td>{{ qty_format($item->quantity) }}</td><td>{{ money($item->subtotal) }}</td></tr>
@endforeach
</tbody></table>
</div></div></div></div>
@endsection
BLADE);

w('admin/returns/purchases/index.blade.php', <<<'BLADE'
@extends('admin.admin_master')
@section('title','Purchase Returns')
@section('admin')
<div class="content"><div class="container-fluid">
<div class="py-3 d-flex justify-content-between"><h4 class="m-0">Purchase Returns</h4><a href="{{ route('purchase-returns.create') }}" class="btn btn-primary btn-sm">New Return</a></div>
<div class="card"><div class="card-body">
<table class="table"><thead><tr><th>Return #</th><th>Purchase</th><th>Supplier</th><th>Date</th><th>Total</th></tr></thead><tbody>
@foreach($returns as $r)
<tr><td>{{ $r->return_number }}</td><td>{{ $r->purchase?->invoice_number }}</td><td>{{ $r->supplier?->name }}</td>
<td>{{ $r->return_date->format('Y-m-d H:i') }}</td><td>{{ money($r->total_amount) }}</td></tr>
@endforeach
</tbody></table>{{ $returns->links() }}
</div></div></div></div>
@endsection
BLADE);

w('admin/returns/purchases/create.blade.php', <<<'BLADE'
@extends('admin.admin_master')
@section('title','Purchase Return')
@section('admin')
<div class="content"><div class="container-fluid">
<div class="py-3"><h4>New Purchase Return</h4></div>
@if(!$purchase)
<div class="card"><div class="card-body">
<form method="GET"><label class="form-label">Purchase ID</label>
<input name="purchase_id" class="form-control mb-2" required>
<button class="btn btn-primary">Load Purchase</button></form>
</div></div>
@else
<form method="POST" action="{{ route('purchase-returns.store') }}">@csrf
<input type="hidden" name="purchase_id" value="{{ $purchase->id }}">
<div class="card"><div class="card-body">
<p>{{ $purchase->invoice_number }} — {{ $purchase->supplier?->name }}</p>
<table class="table"><thead><tr><th>Product</th><th>Purchased</th><th>Batch Left</th><th>Return Qty</th></tr></thead><tbody>
@foreach($purchase->items as $i=>$item)
<tr>
<td>{{ $item->product?->name }}<input type="hidden" name="items[{{ $i }}][purchase_item_id]" value="{{ $item->id }}"></td>
<td>{{ qty_format($item->quantity) }}</td>
<td>{{ qty_format($item->stockBatch?->quantity_remaining ?? 0) }}</td>
<td><input type="number" step="0.001" min="0" name="items[{{ $i }}][quantity]" class="form-control" value="0"></td>
</tr>
@endforeach
</tbody></table>
<textarea name="notes" class="form-control mb-3" placeholder="Notes"></textarea>
<button class="btn btn-primary">Save Return</button>
</div></div>
</form>
@endif
</div></div>
@endsection
BLADE);

w('admin/payments/customers/index.blade.php', <<<'BLADE'
@extends('admin.admin_master')
@section('title','Customer Payments')
@section('admin')
<div class="content"><div class="container-fluid">
<div class="py-3 d-flex justify-content-between"><h4 class="m-0">Customer Payments</h4><a href="{{ route('customer-payments.create') }}" class="btn btn-primary btn-sm">Receive Payment</a></div>
<div class="card"><div class="card-body">
<table class="table"><thead><tr><th>Payment #</th><th>Customer</th><th>Date</th><th>Amount</th><th>Method</th><th></th></tr></thead><tbody>
@foreach($payments as $p)
<tr><td>{{ $p->payment_number }}</td><td>{{ $p->customer?->name }}</td><td>{{ $p->payment_date->format('Y-m-d') }}</td>
<td>{{ money($p->amount) }}</td><td>{{ $p->payment_method }}</td>
<td><a href="{{ route('customer-payments.receipt',$p) }}" target="_blank" class="btn btn-sm btn-outline-dark">Receipt</a></td></tr>
@endforeach
</tbody></table>{{ $payments->links() }}
</div></div></div></div>
@endsection
BLADE);

w('admin/payments/customers/create.blade.php', <<<'BLADE'
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
<div class="mb-3"><label class="form-label">Method</label><select name="payment_method" class="form-select"><option value="cash">Cash</option><option value="card">Card</option><option value="other">Other</option></select></div>
<div class="mb-3"><label class="form-label">Notes</label><textarea name="notes" class="form-control"></textarea></div>
<button class="btn btn-primary">Save Payment</button>
</form></div></div></div></div>
@endsection
BLADE);

w('admin/payments/suppliers/index.blade.php', <<<'BLADE'
@extends('admin.admin_master')
@section('title','Supplier Payments')
@section('admin')
<div class="content"><div class="container-fluid">
<div class="py-3 d-flex justify-content-between"><h4 class="m-0">Supplier Payments</h4><a href="{{ route('supplier-payments.create') }}" class="btn btn-primary btn-sm">Pay Supplier</a></div>
<div class="card"><div class="card-body">
<table class="table"><thead><tr><th>Payment #</th><th>Supplier</th><th>Purchase</th><th>Date</th><th>Amount</th><th></th></tr></thead><tbody>
@foreach($payments as $p)
<tr><td>{{ $p->payment_number }}</td><td>{{ $p->supplier?->name }}</td><td>{{ $p->purchase?->invoice_number }}</td>
<td>{{ $p->payment_date->format('Y-m-d') }}</td><td>{{ money($p->amount) }}</td>
<td><a href="{{ route('supplier-payments.receipt',$p) }}" target="_blank" class="btn btn-sm btn-outline-dark">Receipt</a></td></tr>
@endforeach
</tbody></table>{{ $payments->links() }}
</div></div></div></div>
@endsection
BLADE);

w('admin/payments/suppliers/create.blade.php', <<<'BLADE'
@extends('admin.admin_master')
@section('title','Supplier Payment')
@section('admin')
<div class="content"><div class="container-fluid"><div class="py-3"><h4>Pay Supplier</h4></div>
<div class="card"><div class="card-body">
<form method="POST" action="{{ route('supplier-payments.store') }}">@csrf
<div class="mb-3"><label class="form-label">Purchase ID</label>
<input type="number" name="purchase_id" class="form-control" value="{{ $purchase->id ?? '' }}" required>
@if($purchase)<small class="text-muted">{{ $purchase->invoice_number }} due {{ money($purchase->remaining_balance) }}</small>@endif
</div>
<div class="mb-3"><label class="form-label">Amount</label><input type="number" step="0.01" name="amount" class="form-control" required></div>
<div class="mb-3"><label class="form-label">Method</label><select name="payment_method" class="form-select"><option value="cash">Cash</option><option value="card">Card</option><option value="other">Other</option></select></div>
<div class="mb-3"><label class="form-label">Notes</label><textarea name="notes" class="form-control"></textarea></div>
<button class="btn btn-primary">Save Payment</button>
</form></div></div></div></div>
@endsection
BLADE);

echo "returns/payments done\n";
