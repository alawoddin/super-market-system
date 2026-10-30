@extends('admin.admin_master')
@section('title','Customers')
@section('admin')
<div class="content"><div class="container-fluid">
<div class="py-3 d-flex justify-content-between"><h4 class="m-0">Customers</h4><a href="{{ route('customers.create') }}" class="btn btn-primary btn-sm">Add Customer</a></div>
<div class="card"><div class="card-body">
<form class="row g-2 mb-3"><div class="col-md-4"><input name="search" value="{{ request('search') }}" class="form-control" placeholder="Search"></div><div class="col-md-2"><button class="btn btn-light">Search</button></div></form>
<table class="table table-hover"><thead><tr><th>Name</th><th>Phone</th><th>Debt</th><th></th></tr></thead><tbody>
@foreach($customers as $c)
<tr><td>{{ $c->name }}</td><td>{{ $c->phone }}</td><td>{{ money($c->outstandingBalance()) }}</td>
<td class="text-end">
<a href="{{ route('customers.show',$c) }}" class="btn btn-sm btn-outline-secondary">View</a>
<a href="{{ route('customers.statement',$c) }}" class="btn btn-sm btn-outline-info">Statement</a>
<a href="{{ route('customers.edit',$c) }}" class="btn btn-sm btn-outline-primary">Edit</a>
</td></tr>
@endforeach
</tbody></table>{{ $customers->links() }}
</div></div></div></div>
@endsection