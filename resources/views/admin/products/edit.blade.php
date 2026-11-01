@extends('admin.admin_master')
@section('title','Edit Product')
@section('admin')
<div class="content"><div class="container-fluid">
<div class="py-3"><h4>Edit Product</h4></div>
<div class="card"><div class="card-body">
<form method="POST" action="{{ route('products.update', $product) }}">@csrf @method('PUT')
<div class="row g-3">
<div class="col-md-6"><label class="form-label">Name</label><input name="name" class="form-control" value="{{ old('name', $product->name) }}" required></div>
<div class="col-md-6"><label class="form-label">Barcode</label><div class="input-group"><input name="barcode" id="barcode" class="form-control" value="{{ old('barcode', $product->barcode) }}"><button type="button" class="btn btn-outline-secondary" id="genBarcode">Generate</button></div></div>
<div class="col-md-4"><label class="form-label">Quantity in stock</label><input type="number" step="0.001" min="0" name="quantity" class="form-control" value="{{ old('quantity', (float) $product->stock_quantity) }}" required></div>
<div class="col-md-4"><label class="form-label">Purchase Price (AFN)</label><input type="number" step="0.01" min="0" name="purchase_price" class="form-control" value="{{ old('purchase_price', (float) $product->purchase_price) }}" required></div>
<div class="col-md-4"><label class="form-label">Selling Price (AFN)</label><input type="number" step="0.01" min="0" name="selling_price" class="form-control" value="{{ old('selling_price', (float) $product->selling_price) }}" required></div>
<div class="col-md-3 form-check ms-2"><input type="checkbox" class="form-check-input" name="is_active" value="1" id="is_active" @checked(old('is_active', $product->is_active))><label class="form-check-label" for="is_active">Active (shown in POS)</label></div>
</div>
<div class="mt-3"><button class="btn btn-primary">Update</button> <a href="{{ route('products.index') }}" class="btn btn-light">Cancel</a></div>
</form></div></div></div></div>
@endsection
@push('scripts')
<script>
document.getElementById('barcode')?.addEventListener('keydown', e => { if (e.key === 'Enter') e.preventDefault(); });
document.getElementById('genBarcode')?.addEventListener('click', async () => {
  const res = await fetch('{{ route('products.barcode.generate') }}');
  const data = await res.json();
  document.getElementById('barcode').value = data.barcode;
});
</script>
@endpush
