@extends('admin.admin_master')
@section('title','Expenses')
@section('admin')
<div class="content"><div class="container-fluid">
<div class="py-3 d-flex justify-content-between"><h4 class="m-0">Expenses</h4><a href="{{ route('expenses.create') }}" class="btn btn-primary btn-sm">Add Expense</a></div>
<div class="card"><div class="card-body">
<form class="row g-2 mb-3">
<div class="col-md-2"><input type="date" name="from" value="{{ request('from') }}" class="form-control"></div>
<div class="col-md-2"><input type="date" name="to" value="{{ request('to') }}" class="form-control"></div>
<div class="col-md-2"><button class="btn btn-light">Filter</button></div>
</form>
<table class="table"><thead><tr><th>Date</th><th>Title</th><th>Amount</th><th>Method</th><th></th></tr></thead><tbody>
@foreach($expenses as $e)
<tr><td>{{ $e->expense_date->format('Y-m-d') }}</td><td>{{ $e->title }}</td><td>{{ money($e->amount) }}</td><td>Cash</td>
<td><a href="{{ route('expenses.edit',$e) }}" class="btn btn-sm btn-outline-primary">Edit</a>
<form action="{{ route('expenses.destroy',$e) }}" method="POST" class="d-inline" data-confirm="Delete this expense?" data-confirm-button="Yes, delete">@csrf @method('DELETE')<button class="btn btn-sm btn-outline-danger">Delete</button></form></td></tr>
@endforeach
</tbody></table>{{ $expenses->links() }}
</div></div></div></div>
@endsection