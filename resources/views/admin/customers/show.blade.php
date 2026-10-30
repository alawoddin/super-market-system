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