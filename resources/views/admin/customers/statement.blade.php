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