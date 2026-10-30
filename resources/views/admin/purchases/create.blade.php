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