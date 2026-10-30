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