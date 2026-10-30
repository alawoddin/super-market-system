@extends('admin.admin_master')
@section('title','Edit Category')
@section('admin')
<div class="content"><div class="container-fluid">
<div class="py-3"><h4>Edit Category</h4></div>
<div class="card"><div class="card-body">
<form method="POST" action="{{ route('categories.update',$category) }}">@csrf @method('PUT')
<div class="mb-3"><label class="form-label">Name</label><input name="name" class="form-control" value="{{ old('name',$category->name) }}" required></div>
<div class="mb-3"><label class="form-label">Description</label><textarea name="description" class="form-control" rows="3">{{ old('description',$category->description) }}</textarea></div>
<div class="form-check mb-3"><input type="checkbox" name="is_active" value="1" class="form-check-input" id="active" @checked(old('is_active',$category->is_active))><label for="active" class="form-check-label">Active</label></div>
<button class="btn btn-primary">Update</button>
<a href="{{ route('categories.index') }}" class="btn btn-light">Cancel</a>
</form></div></div></div></div>
@endsection