@extends('admin.admin_master')
@section('title','Products')
@section('admin')
<div class="content">
<div class="container-fluid">
<div class="py-3 d-flex justify-content-between align-items-center flex-wrap gap-2">
    <div>
        <h4 class="m-0">Products</h4>
        <small class="text-muted">Search, filter, and manage all products</small>
    </div>
    <div class="d-flex gap-2">
        <a href="{{ route('products.export') }}" class="btn btn-outline-success btn-sm">Export Excel</a>
        <a href="{{ route('products.create') }}" class="btn btn-primary btn-sm">Add Product</a>
    </div>
</div>

<div class="card"><div class="card-body">
<div class="row g-2 mb-3">
    <div class="col-md-2">
        <select id="filter-stock" class="form-select">
            <option value="">All stock</option>
            <option value="low">Low stock</option>
            <option value="out">Out of stock</option>
        </select>
    </div>
    <div class="col-md-2">
        <select id="filter-status" class="form-select">
            <option value="">All status</option>
            <option value="1">Active</option>
            <option value="0">Off</option>
        </select>
    </div>
    <div class="col-md-2">
        <button type="button" class="btn btn-outline-secondary w-100" id="btn-reset">Reset</button>
    </div>
</div>

<form method="POST" action="{{ route('products.import') }}" enctype="multipart/form-data" class="row g-2 mb-3">@csrf
    <div class="col-md-4"><input type="file" name="file" class="form-control" required></div>
    <div class="col-md-2"><button class="btn btn-outline-primary">Import Excel</button></div>
</form>

<div class="table-responsive">
<table id="products-datatable" class="table table-hover table-striped align-middle w-100">
    <thead>
        <tr>
            <th>#</th>
            <th>Product</th>
            <th>Barcode</th>
            <th>Buy</th>
            <th>Sell</th>
            <th>Stock</th>
            <th>Status</th>
            <th class="text-end">Actions</th>
        </tr>
    </thead>
    <tbody></tbody>
</table>
</div>
</div></div>
</div></div>
@endsection

@include('admin.partials.datatable-assets')

@push('scripts')
<script>
$(function () {
    const table = $('#products-datatable').DataTable({
        processing: true,
        serverSide: true,
        responsive: true,
        pageLength: 25,
        order: [[1, 'asc']],
        ajax: {
            url: @json(route('products.datatable')),
            data: function (d) {
                d.stock = $('#filter-stock').val();
                d.status = $('#filter-status').val();
            }
        },
        columns: [
            { data: 'DT_RowIndex', orderable: false, searchable: false, width: '40px' },
            { data: 'name' },
            { data: 'barcode' },
            { data: 'buy', className: 'text-end' },
            { data: 'sell', className: 'text-end' },
            { data: 'stock', className: 'text-end' },
            { data: 'status' },
            { data: 'actions', orderable: false, searchable: false, className: 'text-end' }
        ],
        dom: "<'row align-items-center mb-2'<'col-md-4'l><'col-md-4 text-center'B><'col-md-4'f>>rtip",
        buttons: [
            { extend: 'copy', className: 'btn btn-sm btn-light', text: 'Copy' },
            { extend: 'print', className: 'btn btn-sm btn-light', text: 'Print' },
            { extend: 'csv', className: 'btn btn-sm btn-light', text: 'CSV' }
        ],
        language: {
            searchPlaceholder: 'Name / barcode...',
            processing: 'Loading products...'
        }
    });

    $('#filter-stock, #filter-status').on('change', () => table.ajax.reload());
    $('#btn-reset').on('click', function () {
        $('#filter-stock, #filter-status').val('');
        table.ajax.reload();
    });
});
</script>
@endpush
