@extends('admin.admin_master')
@section('title','Settings')
@section('admin')
<div class="content"><div class="container-fluid">
<div class="py-3"><h4>Shop Settings</h4></div>
<div class="card"><div class="card-body">
<form method="POST" action="{{ route('settings.update') }}" enctype="multipart/form-data">@csrf @method('PUT')
<div class="row g-3">
<div class="col-md-6"><label class="form-label">Shop Name</label><input name="shop_name" class="form-control" value="{{ $settings->shop_name }}" required></div>
<div class="col-md-6"><label class="form-label">Phone</label><input name="phone" class="form-control" value="{{ $settings->phone }}"></div>
<div class="col-md-12"><label class="form-label">Address</label><textarea name="address" class="form-control">{{ $settings->address }}</textarea></div>
<div class="col-md-4"><label class="form-label">Currency</label><input name="currency" class="form-control" value="{{ $settings->currency }}" required></div>
<div class="col-md-4"><label class="form-label">Low Stock Alert</label><input type="number" name="low_stock_alert" class="form-control" value="{{ $settings->low_stock_alert }}" required></div>
<div class="col-md-4"><label class="form-label">Expiration Alert (days)</label><input type="number" name="expiration_alert_days" class="form-control" value="{{ $settings->expiration_alert_days }}" required></div>
<div class="col-md-4"><label class="form-label">Receipt Width</label><select name="receipt_width" class="form-select"><option value="80mm" @selected($settings->receipt_width==='80mm')>80mm</option><option value="58mm" @selected($settings->receipt_width==='58mm')>58mm</option></select></div>
<div class="col-md-8"><label class="form-label">Logo</label><input type="file" name="logo" class="form-control" accept="image/*"></div>
<div class="col-md-6"><label class="form-label">Receipt Header</label><textarea name="receipt_header" class="form-control">{{ $settings->receipt_header }}</textarea></div>
<div class="col-md-6"><label class="form-label">Receipt Footer</label><textarea name="receipt_footer" class="form-control">{{ $settings->receipt_footer }}</textarea></div>
</div>
<button class="btn btn-primary mt-3">Save Settings</button>
</form></div></div></div></div>
@endsection