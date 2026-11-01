@extends('admin.admin_master')
@section('title','Register Details')
@section('admin')
<div class="content"><div class="container-fluid">
<div class="py-3"><h4>Register #{{ $register->id }}</h4></div>
<div class="card"><div class="card-body">
<p>User: {{ $register->user?->name }}</p>
<p>Opened: {{ $register->opened_at }}</p>
<p>Closed: {{ $register->closed_at ?? '-' }}</p>
<p>Opening: {{ money($register->opening_balance) }}</p>
<p>Cash sales: {{ money($register->cash_sales) }}</p>
<p>Expenses: {{ money($register->cash_expenses) }}</p>
<p>Refunds: {{ money($register->cash_refunds) }}</p>
<p>Expected: {{ money($register->expected_closing_balance) }}</p>
<p>Actual: {{ $register->actual_closing_balance !== null ? money($register->actual_closing_balance) : '-' }}</p>
<p>Difference: {{ $register->cash_difference !== null ? money($register->cash_difference) : '-' }}</p>
<p>Notes: {{ $register->notes }}</p>
</div></div></div></div>
@endsection