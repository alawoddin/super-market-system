@extends('admin.admin_master')
@section('title','Products')
@section('admin')
<div class="content"><div class="container-fluid">
<div class="py-3 d-flex justify-content-between align-items-center flex-wrap gap-2">
  <h4 class="m-0">Products</h4>
  <div class="d-flex gap-2">
    <a href="{{ route('products.export') }}" class="btn btn-outline-success btn-sm">Export Excel</a>
    <a href="{{ route('products.create') }}" class="btn btn-primary btn-sm">Add Product</a>
  </div>
</div>
<div class="card"><div class="card-body">
<form class="row g-2 mb-3" method="GET">
  <div class="col-md-3"><input name="search" value="{{ request('search') }}" class="form-control" placeholder="Name / barcode"></div>
  <div class="col-md-2"><select name="category_id" class="form-select"><option value="">All categories</option>@foreach($categories as $c)<option value="{{ $c->id }}" @selected(request('category_id')==$c->id)>{{ $c->name }}</option>@endforeach</select></div>
  <div class="col-md-2"><select name="stock" class="form-select"><option value="">Stock</option><option value="low" @selected(request('stock')=='low')>Low</option><option value="out" @selected(request('stock')=='out')>Out</option></select></div>
  <div class="col-md-2"><button class="btn btn-light w-100">Filter</button></div>
</form>
<form method="POST" action="{{ route('products.import') }}" enctype="multipart/form-data" class="row g-2 mb-3">@csrf
  <div class="col-md-4"><input type="file" name="file" class="form-control" required></div>
  <div class="col-md-2"><button class="btn btn-outline-primary">Import Excel</button></div>
</form>
<div class="table-responsive"><table class="table table-hover align-middle"><thead><tr><th>Product</th><th>Barcode</th><th>Category</th><th>Buy</th><th>Sell</th><th>Stock</th><th>Status</th><th></th></tr></thead><tbody>
@foreach($products as $p)
<tr>
<td>{{ $p->name }}<div class="small text-muted">{{ $p->unit }}</div></td>
<td>{{ $p->barcode }}</td>
<td>{{ $p->category?->name }}</td>
<td>{{ money($p->purchase_price) }}</td>
<td>{{ money($p->selling_price) }}</td>
<td class="{{ $p->is_low_stock?'text-danger fw-semibold':'' }}">{{ qty_format($p->stock_quantity) }}</td>
<td><span class="badge bg-{{ $p->is_active?'success':'secondary' }}">{{ $p->is_active?'Active':'Off' }}</span></td>
<td class="text-end">
<a href="{{ route('products.show',$p) }}" class="btn btn-sm btn-outline-secondary">View</a>
<a href="{{ route('products.edit',$p) }}" class="btn btn-sm btn-outline-primary">Edit</a>
</td></tr>
@endforeach
</tbody></table></div>
{{ $products->links() }}
</div></div></div></div>
@endsection