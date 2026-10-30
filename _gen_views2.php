<?php
function ensure($path){ $dir=dirname($path); if(!is_dir($dir)) mkdir($dir,0777,true); }
function w($rel,$content){ $path=__DIR__.'/resources/views/'.$rel; ensure($path); file_put_contents($path,$content); echo "OK $rel\n"; }

w('admin/categories/index.blade.php', <<<'BLADE'
@extends('admin.admin_master')
@section('title','Categories')
@section('admin')
<div class="content"><div class="container-fluid">
<div class="py-3 d-flex justify-content-between align-items-center">
  <h4 class="m-0">Categories</h4>
  <a href="{{ route('categories.create') }}" class="btn btn-primary btn-sm">Add Category</a>
</div>
<div class="card"><div class="card-body">
<form class="row g-2 mb-3"><div class="col-md-4"><input type="text" name="search" value="{{ request('search') }}" class="form-control" placeholder="Search..."></div>
<div class="col-md-2"><button class="btn btn-light">Search</button></div></form>
<div class="table-responsive"><table class="table table-hover"><thead><tr><th>Name</th><th>Products</th><th>Status</th><th></th></tr></thead><tbody>
@foreach($categories as $c)
<tr>
<td>{{ $c->name }}<div class="text-muted small">{{ $c->description }}</div></td>
<td>{{ $c->products_count }}</td>
<td><span class="badge bg-{{ $c->is_active?'success':'secondary' }}">{{ $c->is_active?'Active':'Inactive' }}</span></td>
<td class="text-end">
  <a href="{{ route('categories.edit',$c) }}" class="btn btn-sm btn-outline-primary">Edit</a>
  <form action="{{ route('categories.destroy',$c) }}" method="POST" class="d-inline" onsubmit="return confirm('Delete category?')">@csrf @method('DELETE')
  <button class="btn btn-sm btn-outline-danger">Delete</button></form>
</td></tr>
@endforeach
</tbody></table></div>
{{ $categories->links() }}
</div></div></div></div>
@endsection
BLADE);

w('admin/categories/create.blade.php', <<<'BLADE'
@extends('admin.admin_master')
@section('title','Add Category')
@section('admin')
<div class="content"><div class="container-fluid">
<div class="py-3"><h4>Add Category</h4></div>
<div class="card"><div class="card-body">
<form method="POST" action="{{ route('categories.store') }}">@csrf
<div class="mb-3"><label class="form-label">Name</label><input name="name" class="form-control" value="{{ old('name') }}" required>@error('name')<small class="text-danger">{{ $message }}</small>@enderror</div>
<div class="mb-3"><label class="form-label">Description</label><textarea name="description" class="form-control" rows="3">{{ old('description') }}</textarea></div>
<div class="form-check mb-3"><input type="checkbox" name="is_active" value="1" class="form-check-input" id="active" checked><label for="active" class="form-check-label">Active</label></div>
<button class="btn btn-primary">Save</button>
<a href="{{ route('categories.index') }}" class="btn btn-light">Cancel</a>
</form></div></div></div></div>
@endsection
BLADE);

w('admin/categories/edit.blade.php', <<<'BLADE'
@extends('admin.admin_master')
@section('title','Edit Category')
@section('admin')
<div class="content"><div class="container-fluid">
<div class="py-3"><h4>Edit Category</h4></div>
<div class="card"><div class="card-body">
<form method="POST" action="{{ route('categories.update',$category) }}">@csrf @method('PUT')
<div class="mb-3"><label class="form-label">Name</label><input name="name" class="form-control" value="{{ old('name',$category->name) }}" required></div>
<div class="mb-3"><label class="form-label">Description</label><textarea name="description" class="form-control" rows="3">{{ old('description',$category->description) }}</textarea></div>
<div class="form-check mb-3"><input type="checkbox" name="is_active" value="1" class="form-check-input" id="active" @checked(old('is_active',$category->is_active))><label for="active" class="form-check-label">Active</label></div>
<button class="btn btn-primary">Update</button>
<a href="{{ route('categories.index') }}" class="btn btn-light">Cancel</a>
</form></div></div></div></div>
@endsection
BLADE);

w('admin/products/index.blade.php', <<<'BLADE'
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
BLADE);

$productFormFields = <<<'BLADE'
<div class="row g-3">
<div class="col-md-6"><label class="form-label">Name</label><input name="name" class="form-control" value="{{ old('name', $product->name ?? '') }}" required></div>
<div class="col-md-6"><label class="form-label">Category</label><select name="category_id" class="form-select" required><option value="">Select</option>@foreach($categories as $c)<option value="{{ $c->id }}" @selected(old('category_id',$product->category_id ?? '')==$c->id)>{{ $c->name }}</option>@endforeach</select></div>
<div class="col-md-4"><label class="form-label">Barcode</label><div class="input-group"><input name="barcode" id="barcode" class="form-control" value="{{ old('barcode', $product->barcode ?? '') }}"><button type="button" class="btn btn-outline-secondary" id="genBarcode">Generate</button></div></div>
<div class="col-md-4"><label class="form-label">SKU</label><input name="sku" class="form-control" value="{{ old('sku', $product->sku ?? '') }}"></div>
<div class="col-md-4"><label class="form-label">Unit</label><select name="unit" class="form-select">@foreach($units as $k=>$v)<option value="{{ $k }}" @selected(old('unit',$product->unit ?? 'piece')==$k)>{{ $v }}</option>@endforeach</select></div>
<div class="col-md-4"><label class="form-label">Purchase Price (AFN)</label><input type="number" step="0.01" name="purchase_price" class="form-control" value="{{ old('purchase_price', $product->purchase_price ?? 0) }}" required></div>
<div class="col-md-4"><label class="form-label">Selling Price (AFN)</label><input type="number" step="0.01" name="selling_price" class="form-control" value="{{ old('selling_price', $product->selling_price ?? 0) }}" required></div>
<div class="col-md-4"><label class="form-label">Min Stock Alert</label><input type="number" step="0.001" name="min_stock" class="form-control" value="{{ old('min_stock', $product->min_stock ?? 5) }}" required></div>
<div class="col-md-12"><label class="form-label">Description</label><textarea name="description" class="form-control" rows="3">{{ old('description', $product->description ?? '') }}</textarea></div>
<div class="col-md-6"><label class="form-label">Image</label><input type="file" name="image" class="form-control" accept="image/*"></div>
<div class="col-md-3 form-check mt-4"><input type="checkbox" class="form-check-input" name="is_active" value="1" id="is_active" @checked(old('is_active', $product->is_active ?? true))><label class="form-check-label" for="is_active">Active</label></div>
<div class="col-md-3 form-check mt-4"><input type="checkbox" class="form-check-input" name="track_expiry" value="1" id="track_expiry" @checked(old('track_expiry', $product->track_expiry ?? true))><label class="form-check-label" for="track_expiry">Track expiry</label></div>
</div>
BLADE;

w('admin/products/create.blade.php', <<<BLADE
@extends('admin.admin_master')
@section('title','Add Product')
@section('admin')
<div class="content"><div class="container-fluid">
<div class="py-3"><h4>Add Product</h4></div>
<div class="card"><div class="card-body">
<form method="POST" action="{{ route('products.store') }}" enctype="multipart/form-data">@csrf
$productFormFields
<div class="mt-3"><button class="btn btn-primary">Save</button> <a href="{{ route('products.index') }}" class="btn btn-light">Cancel</a></div>
</form></div></div></div></div>
@endsection
@push('scripts')
<script>
document.getElementById('genBarcode')?.addEventListener('click', async () => {
  const res = await fetch('{{ route('products.barcode.generate') }}');
  const data = await res.json();
  document.getElementById('barcode').value = data.barcode;
});
</script>
@endpush
BLADE);

w('admin/products/edit.blade.php', <<<BLADE
@extends('admin.admin_master')
@section('title','Edit Product')
@section('admin')
<div class="content"><div class="container-fluid">
<div class="py-3"><h4>Edit Product</h4></div>
<div class="card"><div class="card-body">
<form method="POST" action="{{ route('products.update', \$product) }}" enctype="multipart/form-data">@csrf @method('PUT')
$productFormFields
<div class="mt-3"><button class="btn btn-primary">Update</button> <a href="{{ route('products.index') }}" class="btn btn-light">Cancel</a></div>
</form></div></div></div></div>
@endsection
@push('scripts')
<script>
document.getElementById('genBarcode')?.addEventListener('click', async () => {
  const res = await fetch('{{ route('products.barcode.generate') }}');
  const data = await res.json();
  document.getElementById('barcode').value = data.barcode;
});
</script>
@endpush
BLADE);

w('admin/products/show.blade.php', <<<'BLADE'
@extends('admin.admin_master')
@section('title','Product Details')
@section('admin')
<div class="content"><div class="container-fluid">
<div class="py-3 d-flex justify-content-between"><h4>{{ $product->name }}</h4><a href="{{ route('products.edit',$product) }}" class="btn btn-primary btn-sm">Edit</a></div>
<div class="row g-3">
<div class="col-md-6"><div class="card"><div class="card-body">
<p><strong>Barcode:</strong> {{ $product->barcode }}</p>
<p><strong>SKU:</strong> {{ $product->sku ?: '-' }}</p>
<p><strong>Category:</strong> {{ $product->category?->name }}</p>
<p><strong>Unit:</strong> {{ $product->unit }}</p>
<p><strong>Purchase:</strong> {{ money($product->purchase_price) }}</p>
<p><strong>Selling:</strong> {{ money($product->selling_price) }}</p>
<p><strong>Stock:</strong> {{ qty_format($product->stock_quantity) }}</p>
</div></div></div>
<div class="col-md-6"><div class="card"><div class="card-body">
<h5>Batches</h5>
<table class="table table-sm"><thead><tr><th>Batch</th><th>Expiry</th><th>Remaining</th><th>Cost</th></tr></thead><tbody>
@forelse($product->stockBatches as $b)
<tr><td>{{ $b->batch_number }}</td><td>{{ $b->expiration_date?->format('Y-m-d') ?? 'N/A' }}</td><td>{{ qty_format($b->quantity_remaining) }}</td><td>{{ money($b->purchase_price) }}</td></tr>
@empty<tr><td colspan="4">No batches</td></tr>@endforelse
</tbody></table>
</div></div></div>
</div></div></div>
@endsection
BLADE);

echo "catalog views done\n";
