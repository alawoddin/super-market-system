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