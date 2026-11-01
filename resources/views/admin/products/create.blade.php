@extends('admin.admin_master')
@section('title','Add Product')
@section('admin')
<div class="content"><div class="container-fluid">
<div class="py-3"><h4>Add Product</h4></div>
<div class="card"><div class="card-body">
<form method="POST" action="{{ route('products.store') }}">@csrf
<div class="row g-3">
<div class="col-md-6"><label class="form-label">Name</label><input name="name" class="form-control" value="{{ old('name') }}" required autofocus></div>
<div class="col-md-6"><label class="form-label">Barcode</label><div class="input-group"><input name="barcode" id="barcode" class="form-control" value="{{ old('barcode') }}" placeholder="Scan or type, or leave empty to generate"><button type="button" class="btn btn-outline-secondary" id="genBarcode">Generate</button></div></div>
<div class="col-md-4"><label class="form-label">Quantity</label><input type="number" step="0.001" min="0" name="quantity" class="form-control" value="{{ old('quantity', 0) }}" required></div>
<div class="col-md-4"><label class="form-label">Purchase Price (AFN)</label><input type="number" step="0.01" min="0" name="purchase_price" class="form-control" value="{{ old('purchase_price', 0) }}" required></div>
<div class="col-md-4"><label class="form-label">Selling Price (AFN)</label><input type="number" step="0.01" min="0" name="selling_price" class="form-control" value="{{ old('selling_price', 0) }}" required></div>
</div>
<div class="mt-3"><button class="btn btn-primary">Save</button> <a href="{{ route('products.index') }}" class="btn btn-light">Cancel</a></div>
</form></div></div></div></div>
@endsection
@push('scripts')
<script>
// Stop the scanner's Enter key from submitting the form before the other fields are filled
document.getElementById('barcode')?.addEventListener('keydown', e => { if (e.key === 'Enter') e.preventDefault(); });
document.getElementById('genBarcode')?.addEventListener('click', async () => {
  const res = await fetch('{{ route('products.barcode.generate') }}');
  const data = await res.json();
  document.getElementById('barcode').value = data.barcode;
});
</script>
@endpush
