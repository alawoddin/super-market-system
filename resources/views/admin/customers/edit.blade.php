@extends('admin.admin_master')
@section('title','Edit Customer')
@section('admin')
<div class="content"><div class="container-fluid"><div class="py-3"><h4>Edit Customer</h4></div>
<div class="card"><div class="card-body">
<form method="POST" action="{{ route('customers.update',$customer) }}">@csrf @method('PUT')
<div class="mb-3"><label class="form-label">Name</label><input name="name" class="form-control" value="{{ $customer->name }}" required></div>
<div class="mb-3"><label class="form-label">Phone</label><input name="phone" class="form-control" value="{{ $customer->phone }}"></div>
<div class="mb-3"><label class="form-label">Address</label><textarea name="address" class="form-control">{{ $customer->address }}</textarea></div>
<div class="mb-3"><label class="form-label">Opening Balance</label><input type="number" step="0.01" name="opening_balance" class="form-control" value="{{ $customer->opening_balance }}"></div>
<div class="mb-3"><label class="form-label">Notes</label><textarea name="notes" class="form-control">{{ $customer->notes }}</textarea></div>
<div class="form-check mb-3"><input type="checkbox" name="is_active" value="1" class="form-check-input" id="a" @checked($customer->is_active)><label for="a" class="form-check-label">Active</label></div>
<button class="btn btn-primary">Update</button>
</form></div></div></div></div>
@endsection