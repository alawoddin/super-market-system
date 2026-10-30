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