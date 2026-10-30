@extends('admin.admin_master')
@section('title','Add Product')
@section('admin')
<div class="content"><div class="container-fluid">
<div class="py-3"><h4>Add Product</h4></div>
<div class="card"><div class="card-body">
<form method="POST" action="{{ route('products.store') }}" enctype="multipart/form-data">@csrf
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