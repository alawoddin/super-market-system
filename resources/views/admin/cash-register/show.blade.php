@extends('admin.admin_master')
@section('title','Day Report')
@section('admin')
<div class="content"><div class="container-fluid">
<div class="py-3 d-flex justify-content-between align-items-center">
    <h4 class="m-0">Day Report — {{ $register->opened_at->format('Y-m-d') }} <small class="text-muted">by {{ $register->user?->name }}</small></h4>
    <a href="{{ route('cash-register.index') }}" class="btn btn-light btn-sm">Back to Cash Register</a>
</div>

@include('admin.cash-register._summary')

@if($register->notes)
<div class="card"><div class="card-body"><h5>Notes</h5><p class="mb-0" style="white-space:pre-line">{{ $register->notes }}</p></div></div>
@endif
</div></div>
@endsection
