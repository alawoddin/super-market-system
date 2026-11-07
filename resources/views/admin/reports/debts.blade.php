@extends('admin.admin_master')
@section('title','Debt Report')
@section('admin')
<div class="content"><div class="container-fluid">
<div class="py-3 d-flex justify-content-between align-items-center"><h4 class="mb-0">Customer Debts</h4>
<div class="fs-5">Total: <strong class="text-danger">{{ money($customers->sum('balance')) }}</strong></div></div>
<div class="card"><div class="card-body">
<table class="table table-sm"><thead><tr><th>Name</th><th>Phone</th><th class="text-end">Balance</th><th></th></tr></thead><tbody>
@forelse($customers as $c)
<tr><td>{{ $c['name'] }}</td><td>{{ $c['phone'] }}</td><td class="text-end text-danger fw-semibold">{{ money($c['balance']) }}</td>
<td class="text-end"><a href="{{ route('customer-payments.create', ['customer_id' => $c['id']]) }}" class="btn btn-sm btn-outline-success">Take payment</a></td></tr>
@empty<tr><td colspan="4">No debts</td></tr>@endforelse
</tbody></table></div></div>
</div></div>
@endsection
