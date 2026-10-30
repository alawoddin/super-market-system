@extends('admin.admin_master')
@section('title','Inventory Report')
@section('admin')
<div class="content"><div class="container-fluid">
<div class="py-3"><h4>Inventory Report</h4></div>
<div class="row g-2 mb-3">
<div class="col-md-4"><div class="card card-body">Stock Value<br><strong>{{ money($value) }}</strong></div></div>
<div class="col-md-4"><div class="card card-body">Low Stock<br><strong>{{ $lowStock }}</strong></div></div>
<div class="col-md-4"><div class="card card-body">Expired Batches<br><strong>{{ $expired }}</strong></div></div>
</div>
<div class="card"><div class="card-body">
<table class="table table-sm"><thead><tr><th>Product</th><th>Stock</th><th>Cost</th><th>Value</th></tr></thead><tbody>
@foreach($products as $p)
<tr><td>{{ $p->name }}</td><td>{{ qty_format($p->stock_quantity) }}</td><td>{{ money($p->purchase_price) }}</td>
<td>{{ money($p->stock_quantity * $p->purchase_price) }}</td></tr>
@endforeach
</tbody></table>{{ $products->links() }}
</div></div></div></div>
@endsection