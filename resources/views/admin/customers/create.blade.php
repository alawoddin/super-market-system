@extends('admin.admin_master')
@section('title','Add Customer')
@section('admin')
<div class="content"><div class="container-fluid"><div class="py-3"><h4>Add Customer</h4></div>
<div class="card"><div class="card-body">
<form method="POST" action="{{ route('customers.store') }}">@csrf
<div class="mb-3"><label class="form-label">Name</label><input name="name" class="form-control" required></div>
<div class="mb-3"><label class="form-label">Phone</label><input name="phone" class="form-control"></div>
<div class="mb-3"><label class="form-label">Address</label><textarea name="address" class="form-control"></textarea></div>
<div class="mb-3"><label class="form-label">Opening Balance</label><input type="number" step="0.01" name="opening_balance" class="form-control" value="0"></div>
<div class="mb-3"><label class="form-label">Notes</label><textarea name="notes" class="form-control"></textarea></div>
<button class="btn btn-primary">Save</button>
</form></div></div></div></div>
@endsection