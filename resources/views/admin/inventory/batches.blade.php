@extends('admin.admin_master')
@section('title','Stock Batches')
@section('admin')
<div class="content"><div class="container-fluid">
<div class="py-3 d-flex justify-content-between"><h4 class="m-0">Batches / Expiration</h4>
<div class="btn-group">
<a href="?filter=" class="btn btn-sm btn-outline-secondary">All</a>
<a href="?filter=expiring" class="btn btn-sm btn-outline-warning">Expiring</a>
<a href="?filter=expired" class="btn btn-sm btn-outline-danger">Expired</a>
</div></div>
<div class="card"><div class="card-body">
<table class="table"><thead><tr><th>Product</th><th>Batch</th><th>Expiry</th><th>Received</th><th>Remaining</th><th>Cost</th></tr></thead><tbody>
@foreach($batches as $b)
<tr class="{{ $b->is_expired ? 'table-danger' : ($b->is_expiring_soon ? 'table-warning' : '') }}">
<td>{{ $b->product?->name }}</td><td>{{ $b->batch_number }}</td>
<td>{{ $b->expiration_date?->format('Y-m-d') ?? 'N/A' }}</td>
<td>{{ qty_format($b->quantity_received) }}</td><td>{{ qty_format($b->quantity_remaining) }}</td>
<td>{{ money($b->purchase_price) }}</td></tr>
@endforeach
</tbody></table>{{ $batches->links() }}
</div></div></div></div>
@endsection