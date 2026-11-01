@extends('admin.admin_master')
@section('title','Cash Register Report')
@section('admin')
<div class="content"><div class="container-fluid">
<div class="py-3"><h4>Cash Register Report</h4></div>
<div class="card"><div class="card-body">
<table class="table table-sm"><thead><tr><th>User</th><th>Opened</th><th>Closed</th><th>Opening</th><th>Cash Sales</th><th>Expenses</th><th>Refunds</th><th>Closing</th><th></th></tr></thead><tbody>
@foreach($rows as $r)
<tr><td>{{ $r->user?->name }}</td><td>{{ $r->opened_at->format('Y-m-d H:i') }}</td>
<td>{{ $r->closed_at?->format('Y-m-d H:i') ?? '-' }}</td>
<td>{{ money($r->opening_balance) }}</td><td>{{ money($r->cash_sales) }}</td><td>{{ money($r->cash_expenses) }}</td><td>{{ money($r->cash_refunds) }}</td>
<td>{{ $r->status === 'closed' ? money($r->actual_closing_balance) : '-' }}</td>
<td>@if($r->status === 'closed')<a href="{{ route('cash-register.show',$r) }}" class="btn btn-sm btn-outline-secondary">Day report</a>@endif</td></tr>
@endforeach
</tbody></table>{{ $rows->links() }}
</div></div></div></div>
@endsection
