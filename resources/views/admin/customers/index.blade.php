@extends('admin.admin_master')
@section('title','Customers')
@section('admin')
<div class="content">
<div class="container-fluid">
<div class="py-3 d-flex justify-content-between align-items-center flex-wrap gap-2">
    <div>
        <h4 class="m-0">Customers</h4>
        <small class="text-muted">Search customers and view debt balances</small>
    </div>
    <a href="{{ route('customers.create') }}" class="btn btn-primary">Add Customer</a>
</div>

<div class="card"><div class="card-body">
<div class="row g-2 mb-3">
    <div class="col-md-3">
        <select id="filter-status" class="form-select">
            <option value="">All status</option>
            <option value="1">Active</option>
            <option value="0">Off</option>
        </select>
    </div>
</div>

<div class="table-responsive">
<table id="customers-datatable" class="table table-hover table-striped align-middle w-100">
    <thead>
        <tr>
            <th>#</th>
            <th>Name</th>
            <th>Phone</th>
            <th>Debt</th>
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
    const table = $('#customers-datatable').DataTable({
        processing: true,
        serverSide: true,
        responsive: true,
        pageLength: 25,
        order: [[1, 'asc']],
        ajax: {
            url: @json(route('customers.datatable')),
            data: function (d) { d.status = $('#filter-status').val(); }
        },
        columns: [
            { data: 'DT_RowIndex', orderable: false, searchable: false, width: '40px' },
            { data: 'name' },
            { data: 'phone' },
            { data: 'debt', className: 'text-end' },
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
            searchPlaceholder: 'Name / phone / address...',
            processing: 'Loading customers...'
        }
    });
    $('#filter-status').on('change', () => table.ajax.reload());
});
</script>
@endpush
