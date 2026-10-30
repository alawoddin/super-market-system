@extends('admin.admin_master')
@section('title','Cash Register Report')
@section('admin')
<div class="content"><div class="container-fluid">
<div class="py-3"><h4>Cash Register Report</h4></div>
<div class="card"><div class="card-body">
<table class="table table-sm"><thead><tr><th>ID</th><th>User</th><th>Opened</th><th>Closed</th><th>Expected</th><th>Actual</th><th>Diff</th></tr></thead><tbody>
@foreach($rows as $r)
<tr><td>#{{ $r->id }}</td><td>{{ $r->user?->name }}</td><td>{{ $r->opened_at->format('Y-m-d H:i') }}</td>
<td>{{ $r->closed_at?->format('Y-m-d H:i') ?? '-' }}</td><td>{{ money($r->expected_closing_balance) }}</td>
<td>{{ $r->actual_closing_balance !== null ? money($r->actual_closing_balance) : '-' }}</td>
<td>{{ $r->cash_difference !== null ? money($r->cash_difference) : '-' }}</td></tr>
@endforeach
</tbody></table>{{ $rows->links() }}
</div></div></div></div>
@endsection