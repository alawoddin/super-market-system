@extends('admin.admin_master')
@section('title','Edit Supplier')
@section('admin')
<div class="content"><div class="container-fluid"><div class="py-3"><h4>Edit Supplier</h4></div>
<div class="card"><div class="card-body">
<form method="POST" action="{{ route('suppliers.update',$supplier) }}">@csrf @method('PUT')
<div class="mb-3"><label class="form-label">Name</label><input name="name" class="form-control" value="{{ $supplier->name }}" required></div>
<div class="mb-3"><label class="form-label">Phone</label><input name="phone" class="form-control" value="{{ $supplier->phone }}"></div>
<div class="mb-3"><label class="form-label">Address</label><textarea name="address" class="form-control">{{ $supplier->address }}</textarea></div>
<div class="mb-3"><label class="form-label">Opening Balance</label><input type="number" step="0.01" name="opening_balance" class="form-control" value="{{ $supplier->opening_balance }}"></div>
<div class="mb-3"><label class="form-label">Notes</label><textarea name="notes" class="form-control">{{ $supplier->notes }}</textarea></div>
<div class="form-check mb-3"><input type="checkbox" name="is_active" value="1" class="form-check-input" id="a" @checked($supplier->is_active)><label for="a" class="form-check-label">Active</label></div>
<button class="btn btn-primary">Update</button>
</form></div></div></div></div>
@endsection