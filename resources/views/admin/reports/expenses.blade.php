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