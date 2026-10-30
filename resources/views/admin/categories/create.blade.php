@extends('admin.admin_master')
@section('title','Add Category')
@section('admin')
<div class="content"><div class="container-fluid">
<div class="py-3"><h4>Add Category</h4></div>
<div class="card"><div class="card-body">
<form method="POST" action="{{ route('categories.store') }}">@csrf
<div class="mb-3"><label class="form-label">Name</label><input name="name" class="form-control" value="{{ old('name') }}" required>@error('name')<small class="text-danger">{{ $message }}</small>@enderror</div>
<div class="mb-3"><label class="form-label">Description</label><textarea name="description" class="form-control" rows="3">{{ old('description') }}</textarea></div>
<div class="form-check mb-3"><input type="checkbox" name="is_active" value="1" class="form-check-input" id="active" checked><label for="active" class="form-check-label">Active</label></div>
<button class="btn btn-primary">Save</button>
<a href="{{ route('categories.index') }}" class="btn btn-light">Cancel</a>
</form></div></div></div></div>
@endsection