@extends('admin.admin_master')
@section('title','Stock Movements')
@section('admin')
<div class="content"><div class="container-fluid">
<div class="py-3"><h4>Stock Movement History</h4></div>
<div class="card"><div class="card-body">
<table class="table table-sm"><thead><tr><th>Date</th><th>Product</th><th>Type</th><th>Qty</th><th>Before</th><th>After</th><th>User</th><th>Notes</th></tr></thead><tbody>
@foreach($movements as $m)
<tr>
<td>{{ $m->created_at->format('Y-m-d H:i') }}</td><td>{{ $m->product?->name }}</td><td>{{ $m->type }}</td>
<td>{{ qty_format($m->quantity) }}</td><td>{{ qty_format($m->quantity_before) }}</td><td>{{ qty_format($m->quantity_after) }}</td>
<td>{{ $m->user?->name }}</td><td>{{ $m->notes }}</td></tr>
@endforeach
</tbody></table>{{ $movements->links() }}
</div></div></div></div>
@endsection