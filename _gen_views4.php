<?php
function ensure($path){ $dir=dirname($path); if(!is_dir($dir)) mkdir($dir,0777,true); }
function w($rel,$content){ $path=__DIR__.'/resources/views/'.$rel; ensure($path); file_put_contents($path,$content); echo "OK $rel\n"; }

w('admin/purchases/index.blade.php', <<<'BLADE'
@extends('admin.admin_master')
@section('title','Purchases')
@section('admin')
<div class="content"><div class="container-fluid">
<div class="py-3 d-flex justify-content-between"><h4 class="m-0">Purchases</h4><a href="{{ route('purchases.create') }}" class="btn btn-primary btn-sm">New Purchase</a></div>
<div class="card"><div class="card-body">
<form class="row g-2 mb-3">
<div class="col-md-3"><input name="search" value="{{ request('search') }}" class="form-control" placeholder="Invoice #"></div>
<div class="col-md-2"><input type="date" name="from" value="{{ request('from') }}" class="form-control"></div>
<div class="col-md-2"><input type="date" name="to" value="{{ request('to') }}" class="form-control"></div>
<div class="col-md-2"><button class="btn btn-light">Filter</button></div>
</form>
<table class="table table-hover"><thead><tr><th>Invoice</th><th>Supplier</th><th>Date</th><th>Total</th><th>Paid</th><th>Due</th><th>Status</th><th></th></tr></thead><tbody>
@foreach($purchases as $p)
<tr>
<td>{{ $p->invoice_number }}</td><td>{{ $p->supplier?->name }}</td><td>{{ $p->purchase_date->format('Y-m-d') }}</td>
<td>{{ money($p->grand_total) }}</td><td>{{ money($p->amount_paid) }}</td><td>{{ money($p->remaining_balance) }}</td>
<td><span class="badge bg-{{ $p->payment_status==='paid'?'success':($p->payment_status==='partial'?'warning':'danger') }}">{{ $p->payment_status }}</span></td>
<td><a href="{{ route('purchases.show',$p) }}" class="btn btn-sm btn-outline-secondary">View</a></td>
</tr>
@endforeach
</tbody></table>{{ $purchases->links() }}
</div></div></div></div>
@endsection
BLADE);

w('admin/purchases/create.blade.php', <<<'BLADE'
@extends('admin.admin_master')
@section('title','New Purchase')
@section('admin')
<div class="content" x-data="purchaseForm()">
<div class="container-fluid">
<div class="py-3"><h4>New Purchase</h4></div>
<form method="POST" action="{{ route('purchases.store') }}" @submit="prepare">@csrf
<div class="card mb-3"><div class="card-body row g-3">
<div class="col-md-4"><label class="form-label">Supplier</label>
<select name="supplier_id" class="form-select" required><option value="">Select</option>@foreach($suppliers as $s)<option value="{{ $s->id }}">{{ $s->name }}</option>@endforeach</select></div>
<div class="col-md-4"><label class="form-label">Invoice #</label><input name="invoice_number" class="form-control" value="{{ $invoice }}"></div>
<div class="col-md-4"><label class="form-label">Date</label><input type="date" name="purchase_date" class="form-control" value="{{ date('Y-m-d') }}" required></div>
</div></div>
<div class="card mb-3"><div class="card-body">
<div class="d-flex gap-2 mb-3">
<select class="form-select" x-model="selected">
<option value="">Add product...</option>
@foreach($products as $p)
<option value='@json(["id"=>$p->id,"name"=>$p->name,"cost"=>(float)$p->purchase_price])'>{{ $p->name }} ({{ $p->barcode }})</option>
@endforeach
</select>
<button type="button" class="btn btn-outline-primary" @click="addLine()">Add</button>
</div>
<table class="table"><thead><tr><th>Product</th><th>Qty</th><th>Cost</th><th>Discount</th><th>Expiry</th><th>Batch</th><th></th></tr></thead>
<tbody>
<template x-for="(line,i) in lines" :key="i">
<tr>
<td><input type="hidden" :name="`items[${i}][product_id]`" :value="line.id"><span x-text="line.name"></span></td>
<td><input type="number" step="0.001" class="form-control" :name="`items[${i}][quantity]`" x-model.number="line.qty" required></td>
<td><input type="number" step="0.01" class="form-control" :name="`items[${i}][unit_cost]`" x-model.number="line.cost" required></td>
<td><input type="number" step="0.01" class="form-control" :name="`items[${i}][discount]`" x-model.number="line.discount"></td>
<td><input type="date" class="form-control" :name="`items[${i}][expiration_date]`" x-model="line.expiry"></td>
<td><input class="form-control" :name="`items[${i}][batch_number]`" x-model="line.batch"></td>
<td><button type="button" class="btn btn-sm btn-outline-danger" @click="lines.splice(i,1)">x</button></td>
</tr>
</template>
</tbody></table>
</div></div>
<div class="card"><div class="card-body row g-3">
<div class="col-md-3"><label class="form-label">Discount</label><input type="number" step="0.01" name="discount" class="form-control" value="0"></div>
<div class="col-md-3"><label class="form-label">Amount Paid</label><input type="number" step="0.01" name="amount_paid" class="form-control" value="0"></div>
<div class="col-md-3"><label class="form-label">Payment Method</label><select name="payment_method" class="form-select"><option value="cash">Cash</option><option value="card">Card</option><option value="other">Other</option></select></div>
<div class="col-md-3"><label class="form-label">Notes</label><input name="notes" class="form-control"></div>
<div class="col-12"><button class="btn btn-primary">Save Purchase</button></div>
</div></div>
</form>
</div></div>
@endsection
@push('scripts')
<script>
function purchaseForm(){return{selected:'',lines:[],addLine(){if(!this.selected)return;const p=JSON.parse(this.selected);this.lines.push({id:p.id,name:p.name,qty:1,cost:p.cost,discount:0,expiry:'',batch:''});this.selected='';},prepare(e){if(!this.lines.length){e.preventDefault();alert('Add at least one product');}}}}
</script>
@endpush
BLADE);

w('admin/purchases/show.blade.php', <<<'BLADE'
@extends('admin.admin_master')
@section('title','Purchase Details')
@section('admin')
<div class="content"><div class="container-fluid">
<div class="py-3 d-flex justify-content-between flex-wrap gap-2">
<h4>{{ $purchase->invoice_number }}</h4>
<div>
<a href="{{ route('purchases.print',$purchase) }}" target="_blank" class="btn btn-sm btn-outline-dark">Print</a>
<a href="{{ route('purchase-returns.create',['purchase_id'=>$purchase->id]) }}" class="btn btn-sm btn-outline-warning">Return</a>
</div></div>
<div class="row g-3">
<div class="col-md-8"><div class="card"><div class="card-body">
<p><strong>Supplier:</strong> {{ $purchase->supplier?->name }}</p>
<p><strong>Date:</strong> {{ $purchase->purchase_date->format('Y-m-d') }}</p>
<table class="table"><thead><tr><th>Product</th><th>Qty</th><th>Cost</th><th>Subtotal</th><th>Expiry</th></tr></thead><tbody>
@foreach($purchase->items as $item)
<tr><td>{{ $item->product?->name }}</td><td>{{ qty_format($item->quantity) }}</td><td>{{ money($item->unit_cost) }}</td><td>{{ money($item->subtotal) }}</td><td>{{ $item->expiration_date?->format('Y-m-d') ?? 'N/A' }}</td></tr>
@endforeach
</tbody></table>
</div></div></div>
<div class="col-md-4">
<div class="card mb-3"><div class="card-body">
<div class="d-flex justify-content-between"><span>Grand Total</span><strong>{{ money($purchase->grand_total) }}</strong></div>
<div class="d-flex justify-content-between"><span>Paid</span><strong>{{ money($purchase->amount_paid) }}</strong></div>
<div class="d-flex justify-content-between"><span>Due</span><strong class="text-danger">{{ money($purchase->remaining_balance) }}</strong></div>
</div></div>
@if($purchase->remaining_balance > 0)
<div class="card"><div class="card-body">
<h5>Record Payment</h5>
<form method="POST" action="{{ route('purchases.payments',$purchase) }}">@csrf
<input type="number" step="0.01" name="amount" class="form-control mb-2" max="{{ $purchase->remaining_balance }}" required>
<select name="payment_method" class="form-select mb-2"><option value="cash">Cash</option><option value="card">Card</option><option value="other">Other</option></select>
<textarea name="notes" class="form-control mb-2" placeholder="Notes"></textarea>
<button class="btn btn-primary w-100">Pay</button>
</form>
</div></div>
@endif
</div></div></div></div>
@endsection
BLADE);

w('admin/sales/index.blade.php', <<<'BLADE'
@extends('admin.admin_master')
@section('title','Sales')
@section('admin')
<div class="content"><div class="container-fluid">
<div class="py-3 d-flex justify-content-between"><h4 class="m-0">Sales History</h4><a href="{{ route('pos.index') }}" class="btn btn-primary btn-sm">Open POS</a></div>
<div class="card"><div class="card-body">
<form class="row g-2 mb-3">
<div class="col-md-3"><input name="search" value="{{ request('search') }}" class="form-control" placeholder="Invoice"></div>
<div class="col-md-2"><input type="date" name="from" value="{{ request('from') }}" class="form-control"></div>
<div class="col-md-2"><input type="date" name="to" value="{{ request('to') }}" class="form-control"></div>
<div class="col-md-2"><button class="btn btn-light">Filter</button></div>
</form>
<table class="table table-hover"><thead><tr><th>Invoice</th><th>Customer</th><th>Date</th><th>Total</th><th>Paid</th><th>Due</th><th>Status</th><th></th></tr></thead><tbody>
@foreach($sales as $s)
<tr>
<td>{{ $s->invoice_number }}</td><td>{{ $s->customer?->name ?? 'Walk-in' }}</td><td>{{ $s->sale_date->format('Y-m-d H:i') }}</td>
<td>{{ money($s->grand_total) }}</td><td>{{ money($s->amount_paid) }}</td><td>{{ money($s->remaining_balance) }}</td>
<td><span class="badge bg-secondary">{{ $s->status }}</span></td>
<td><a href="{{ route('sales.show',$s) }}" class="btn btn-sm btn-outline-secondary">View</a>
<a href="{{ route('sales.receipt',$s) }}" target="_blank" class="btn btn-sm btn-outline-dark">Receipt</a></td>
</tr>
@endforeach
</tbody></table>{{ $sales->links() }}
</div></div></div></div>
@endsection
BLADE);

w('admin/sales/show.blade.php', <<<'BLADE'
@extends('admin.admin_master')
@section('title','Sale Details')
@section('admin')
<div class="content"><div class="container-fluid">
<div class="py-3 d-flex justify-content-between flex-wrap gap-2">
<h4>{{ $sale->invoice_number }}</h4>
<div class="d-flex gap-2">
<a href="{{ route('sales.receipt',$sale) }}" target="_blank" class="btn btn-sm btn-outline-dark">Print Receipt</a>
<a href="{{ route('sale-returns.create',['sale_id'=>$sale->id]) }}" class="btn btn-sm btn-outline-warning">Return</a>
@if($sale->status==='completed')
<form method="POST" action="{{ route('sales.cancel',$sale) }}" onsubmit="return confirm('Cancel this sale and restore stock?')">@csrf
<button class="btn btn-sm btn-outline-danger">Cancel Sale</button></form>
@endif
</div></div>
<div class="card"><div class="card-body">
<p><strong>Customer:</strong> {{ $sale->customer?->name ?? 'Walk-in' }}</p>
<p><strong>Cashier:</strong> {{ $sale->user?->name }}</p>
<p><strong>Date:</strong> {{ $sale->sale_date->format('Y-m-d H:i') }}</p>
<table class="table"><thead><tr><th>Product</th><th>Qty</th><th>Price</th><th>Disc</th><th>Subtotal</th></tr></thead><tbody>
@foreach($sale->items as $item)
<tr><td>{{ $item->product?->name }}</td><td>{{ qty_format($item->quantity) }}</td><td>{{ money($item->unit_price) }}</td><td>{{ money($item->discount) }}</td><td>{{ money($item->subtotal) }}</td></tr>
@endforeach
</tbody></table>
<div class="text-end">
<div>Subtotal: {{ money($sale->subtotal) }}</div>
<div>Discount: {{ money($sale->discount) }}</div>
<div class="fs-5 fw-bold">Grand Total: {{ money($sale->grand_total) }}</div>
<div>Paid: {{ money($sale->amount_paid) }} | Due: {{ money($sale->remaining_balance) }} | Change: {{ money($sale->change_amount) }}</div>
<div>COGS: {{ money($sale->cogs) }} | Profit: {{ money($sale->profit) }}</div>
</div>
</div></div></div></div>
@endsection
BLADE);

echo "purchase/sales views done\n";
