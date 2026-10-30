@extends('admin.admin_master')
@section('title','Categories')
@section('admin')
<div class="content"><div class="container-fluid">
<div class="py-3 d-flex justify-content-between align-items-center">
  <h4 class="m-0">Categories</h4>
  <a href="{{ route('categories.create') }}" class="btn btn-primary btn-sm">Add Category</a>
</div>
<div class="card"><div class="card-body">
<form class="row g-2 mb-3"><div class="col-md-4"><input type="text" name="search" value="{{ request('search') }}" class="form-control" placeholder="Search..."></div>
<div class="col-md-2"><button class="btn btn-light">Search</button></div></form>
<div class="table-responsive"><table class="table table-hover"><thead><tr><th>Name</th><th>Products</th><th>Status</th><th></th></tr></thead><tbody>
@foreach($categories as $c)
<tr>
<td>{{ $c->name }}<div class="text-muted small">{{ $c->description }}</div></td>
<td>{{ $c->products_count }}</td>
<td><span class="badge bg-{{ $c->is_active?'success':'secondary' }}">{{ $c->is_active?'Active':'Inactive' }}</span></td>
<td class="text-end">
  <a href="{{ route('categories.edit',$c) }}" class="btn btn-sm btn-outline-primary">Edit</a>
  <form action="{{ route('categories.destroy',$c) }}" method="POST" class="d-inline" onsubmit="return confirm('Delete category?')">@csrf @method('DELETE')
  <button class="btn btn-sm btn-outline-danger">Delete</button></form>
</td></tr>
@endforeach
</tbody></table></div>
{{ $categories->links() }}
</div></div></div></div>
@endsection