@extends('admin.admin_master')
@section('title','Edit Expense')
@section('admin')
<div class="content"><div class="container-fluid"><div class="py-3"><h4>Edit Expense</h4></div>
<div class="card"><div class="card-body">
<form method="POST" action="{{ route('expenses.update',$expense) }}">@csrf @method('PUT')
<div class="mb-3"><label class="form-label">Title</label><input name="title" class="form-control" value="{{ $expense->title }}" required></div>
<div class="mb-3"><label class="form-label">Amount</label><input type="number" step="0.01" name="amount" class="form-control" value="{{ (float) $expense->amount }}" required></div>
<div class="mb-3"><label class="form-label">Date</label><input type="date" name="expense_date" class="form-control" value="{{ $expense->expense_date->format('Y-m-d') }}" required></div>
<input type="hidden" name="payment_method" value="cash">
<div class="mb-3"><label class="form-label">Payment Method</label><input class="form-control" value="Cash (AFN)" disabled></div>
<div class="mb-3"><label class="form-label">Description</label><textarea name="description" class="form-control">{{ $expense->description }}</textarea></div>
<button class="btn btn-primary">Update</button>
</form></div></div></div></div>
@endsection