@extends('admin.admin_master')
@section('title','Backups')
@section('admin')
<div class="content"><div class="container-fluid">
<div class="py-3 d-flex justify-content-between"><h4 class="m-0">Database Backups</h4>
<form method="POST" action="{{ route('backups.create') }}">@csrf<button class="btn btn-primary btn-sm">Create Backup Now</button></form></div>
<div class="card"><div class="card-body">
<table class="table"><thead><tr><th>File</th><th>Size</th><th>Date</th><th></th></tr></thead><tbody>
@forelse($files as $f)
<tr>
<td>{{ $f->getFilename() }}</td>
<td>{{ number_format($f->getSize()/1024,1) }} KB</td>
<td>{{ date('Y-m-d H:i', $f->getMTime()) }}</td>
<td class="text-end">
<a href="{{ route('backups.download',$f->getFilename()) }}" class="btn btn-sm btn-outline-secondary">Download</a>
<form method="POST" action="{{ route('backups.restore') }}" class="d-inline" onsubmit="return confirm('Restore this backup? Current data will be overwritten.')">@csrf
<input type="hidden" name="file" value="{{ $f->getFilename() }}">
<button class="btn btn-sm btn-outline-danger">Restore</button></form>
</td></tr>
@empty<tr><td colspan="4">No backups yet</td></tr>@endforelse
</tbody></table>
</div></div></div></div>
@endsection