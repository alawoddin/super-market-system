@extends('admin.admin_master')
@section('title','Inventory')
@section('admin')
<div class="content"><div class="container-fluid">
<div class="py-3 d-flex justify-content-between"><h4 class="m-0">Current Stock</h4>
<div><a href="{{ route('inventory.batches') }}" class="btn btn-sm btn-outline-warning">Batches</a>
<a href="{{ route('inventory.movements') }}" class="btn btn-sm btn-outline-secondary">Movements</a></div></div>
<div class="card"><div class="card-body">
<form class="row g-2 mb-3"><div class="col-md-4"><input name="search" value="{{ request('search') }}" class="form-control" placeholder="Search"></div>
<div class="col-md-2"><select name="stock" class="form-select"><option value="">All</option><option value="low" @selected(request('stock')=='low')>Low stock</option></select></div>
<div class="col-md-2"><button class="btn btn-light">Filter</button></div></form>
<table class="table"><thead><tr><th>Product</th><th>Category</th><th>Stock</th><th>Min</th><th>Value</th><th>Adjust</th></tr></thead><tbody>
@foreach($products as $p)
<tr>
<td>{{ $p->name }}</td>
<td class="{{ $p->is_low_stock?'text-danger fw-bold':'' }}">{{ qty_format($p->stock_quantity) }}</td>
<td>{{ qty_format($p->min_stock) }}</td>
<td>{{ money($p->stock_quantity * $p->purchase_price) }}</td>
<td>
<form method="POST" action="{{ route('inventory.adjust',$p) }}" class="d-flex gap-1">@csrf
<input type="number" step="0.001" name="stock_quantity" class="form-control form-control-sm" style="width:90px" value="{{ $p->stock_quantity }}" required>
<button class="btn btn-sm btn-outline-primary">Set</button>
</form>
</td></tr>
@endforeach
</tbody></table>{{ $products->links() }}
</div></div></div></div>
@endsection