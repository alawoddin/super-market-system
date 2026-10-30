@extends('admin.admin_master')
@section('title','Purchase List')
@section('admin')
<div class="content">
<div class="container-fluid">
<div class="py-3 d-flex justify-content-between align-items-center flex-wrap gap-2">
    <div>
        <h4 class="m-0">Purchase List</h4>
        <small class="text-muted">Search, sort, and filter all purchases (AFN)</small>
    </div>
    <a href="{{ route('purchases.create') }}" class="btn btn-primary">New Purchase</a>
</div>

<div class="card">
<div class="card-body">
<div class="row g-2 mb-3" id="purchase-filters">
    <div class="col-md-3">
        <select id="filter-supplier" class="form-select">
            <option value="">All suppliers</option>
            @foreach($suppliers as $supplier)
                <option value="{{ $supplier->id }}">{{ $supplier->name }}</option>
            @endforeach
        </select>
    </div>
    <div class="col-md-2">
        <select id="filter-status" class="form-select">
            <option value="">All payment status</option>
            <option value="paid">Paid</option>
            <option value="partial">Partial</option>
            <option value="unpaid">Unpaid</option>
        </select>
    </div>
    <div class="col-md-2">
        <input type="date" id="filter-from" class="form-control" title="From date">
    </div>
    <div class="col-md-2">
        <input type="date" id="filter-to" class="form-control" title="To date">
    </div>
    <div class="col-md-3 d-flex gap-2">
        <button type="button" class="btn btn-light" id="btn-filter">Apply filter</button>
        <button type="button" class="btn btn-outline-secondary" id="btn-reset">Reset</button>
    </div>
</div>

<div class="table-responsive">
<table id="purchases-datatable" class="table table-hover table-striped align-middle w-100">
    <thead>
        <tr>
            <th>#</th>
            <th>Invoice</th>
            <th>Supplier</th>
            <th>Date</th>
            <th>Items</th>
            <th>Total</th>
            <th>Paid</th>
            <th>Due</th>
            <th>Status</th>
            <th class="text-end">Actions</th>
        </tr>
    </thead>
    <tbody></tbody>
</table>
</div>
</div>
</div>
</div>
</div>
@endsection

@include('admin.partials.datatable-assets')

@push('scripts')
<script>
$(function () {
    const table = $('#purchases-datatable').DataTable({
        processing: true,
        serverSide: true,
        responsive: true,
        pageLength: 25,
        lengthMenu: [[10, 25, 50, 100], [10, 25, 50, 100]],
        order: [[3, 'desc']],
        ajax: {
            url: @json(route('purchases.datatable')),
            data: function (d) {
                d.supplier_id = $('#filter-supplier').val();
                d.payment_status = $('#filter-status').val();
                d.from = $('#filter-from').val();
                d.to = $('#filter-to').val();
            }
        },
        columns: [
            { data: 'DT_RowIndex', orderable: false, searchable: false, width: '40px' },
            { data: 'invoice' },
            { data: 'supplier' },
            { data: 'date' },
            { data: 'items', className: 'text-center' },
            { data: 'total', className: 'text-end' },
            { data: 'paid', className: 'text-end' },
            { data: 'due', className: 'text-end' },
            { data: 'status' },
            { data: 'actions', orderable: false, searchable: false, className: 'text-end' }
        ],
        dom: "<'row align-items-center mb-2'<'col-md-4'l><'col-md-4 text-center'B><'col-md-4'f>>" +
             "<'row'<'col-12'tr>>" +
             "<'row align-items-center mt-2'<'col-md-5'i><'col-md-7'p>>",
        buttons: [
            { extend: 'copy', className: 'btn btn-sm btn-light', text: 'Copy' },
            { extend: 'print', className: 'btn btn-sm btn-light', text: 'Print' },
            { extend: 'csv', className: 'btn btn-sm btn-light', text: 'CSV' }
        ],
        language: {
            processing: 'Loading purchases...',
            search: 'Search:',
            searchPlaceholder: 'Invoice, supplier, notes...',
            lengthMenu: 'Show _MENU_',
            info: 'Showing _START_ to _END_ of _TOTAL_ purchases',
            infoEmpty: 'No purchases found',
            zeroRecords: 'No matching purchases',
            paginate: { previous: 'Prev', next: 'Next' }
        }
    });

    $('#btn-filter').on('click', function () { table.ajax.reload(); });
    $('#btn-reset').on('click', function () {
        $('#filter-supplier, #filter-status, #filter-from, #filter-to').val('');
        table.ajax.reload();
    });
    $('#filter-supplier, #filter-status').on('change', function () { table.ajax.reload(); });
});
</script>
@endpush
