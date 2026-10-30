@extends('admin.admin_master')
@section('title','Suppliers')
@section('admin')
<div class="content"><div class="container-fluid">
<div class="py-3 d-flex justify-content-between"><h4 class="m-0">Suppliers</h4><a href="{{ route('suppliers.create') }}" class="btn btn-primary btn-sm">Add Supplier</a></div>
<div class="card"><div class="card-body">
<form class="row g-2 mb-3"><div class="col-md-4"><input name="search" value="{{ request('search') }}" class="form-control" placeholder="Search"></div><div class="col-md-2"><button class="btn btn-light">Search</button></div></form>
<table class="table table-hover"><thead><tr><th>Name</th><th>Phone</th><th>Opening</th><th>Outstanding</th><th></th></tr></thead><tbody>
@foreach($suppliers as $s)
<tr><td>{{ $s->name }}</td><td>{{ $s->phone }}</td><td>{{ money($s->opening_balance) }}</td><td>{{ money($s->outstandingBalance()) }}</td>
<td class="text-end"><a href="{{ route('suppliers.show',$s) }}" class="btn btn-sm btn-outline-secondary">View</a>
<a href="{{ route('suppliers.edit',$s) }}" class="btn btn-sm btn-outline-primary">Edit</a></td></tr>
@endforeach
</tbody></table>{{ $suppliers->links() }}
</div></div></div></div>
@endsection