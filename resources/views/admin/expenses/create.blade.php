@extends('admin.admin_master')
@section('title','Add Expense')
@section('admin')
<div class="content"><div class="container-fluid"><div class="py-3"><h4>Add Expense</h4></div>
<div class="card"><div class="card-body">
<form method="POST" action="{{ route('expenses.store') }}">@csrf
<div class="mb-3"><label class="form-label">Title</label><input name="title" class="form-control" required></div>
<div class="mb-3"><label class="form-label">Category</label><select name="expense_category_id" class="form-select" required>@foreach($categories as $c)<option value="{{ $c->id }}">{{ $c->name }}</option>@endforeach</select></div>
<div class="mb-3"><label class="form-label">Amount (AFN)</label><input type="number" step="0.01" name="amount" class="form-control" required></div>
<div class="mb-3"><label class="form-label">Date</label><input type="date" name="expense_date" class="form-control" value="{{ date('Y-m-d') }}" required></div>
<input type="hidden" name="payment_method" value="cash">
<div class="mb-3"><label class="form-label">Payment Method</label><input class="form-control" value="Cash (AFN)" disabled></div>
<div class="mb-3"><label class="form-label">Description</label><textarea name="description" class="form-control"></textarea></div>
<button class="btn btn-primary">Save</button>
</form></div></div></div></div>
@endsection