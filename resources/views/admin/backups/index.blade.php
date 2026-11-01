@extends('admin.admin_master')
@section('title','Backups')
@section('admin')
<div class="content">
<div class="container-fluid">
<div class="py-3 d-flex justify-content-between align-items-center flex-wrap gap-2">
    <div>
        <h4 class="m-0">Database Backups</h4>
        <small class="text-muted">Works on local and hosted servers. Backup downloads to your computer and stays on the server for restore.</small>
    </div>
    <form method="POST" action="{{ route('backups.create') }}">
        @csrf
        <input type="hidden" name="download" value="1">
        <button class="btn btn-primary" type="submit">Create & Download Backup</button>
    </form>
</div>

<div class="card mb-3">
    <div class="card-body">
        <h6 class="mb-2">How backup works on hosting</h6>
        <ul class="mb-0 text-muted small">
            <li>Click <strong>Create & Download Backup</strong> — SQL file downloads to your PC and is saved on the server.</li>
            <li>If the host has no <code>mysqldump</code>, the system uses a built-in PHP dump (shared hosting safe).</li>
            <li>Keep downloaded files offline. Use <strong>Restore</strong> only when you need to recover.</li>
            <li>Make sure <code>storage/app/backups</code> is writable on the server (<code>chmod 775</code>).</li>
        </ul>
    </div>
</div>

<div class="card">
<div class="card-body">
<div class="table-responsive">
<table class="table align-middle mb-0">
    <thead>
        <tr>
            <th>File</th>
            <th>Size</th>
            <th>Date</th>
            <th class="text-end">Actions</th>
        </tr>
    </thead>
    <tbody>
    @forelse($files as $f)
        <tr>
            <td class="fw-semibold">{{ $f->getFilename() }}</td>
            <td>{{ number_format($f->getSize()/1024, 1) }} KB</td>
            <td>{{ date('Y-m-d H:i', $f->getMTime()) }}</td>
            <td class="text-end text-nowrap">
                <a href="{{ route('backups.download', $f->getFilename()) }}" class="btn btn-sm btn-outline-secondary">Download</a>
                <form method="POST" action="{{ route('backups.restore') }}" class="d-inline" data-confirm="Restore this backup? All current data will be replaced." data-confirm-button="Yes, restore">
                    @csrf
                    <input type="hidden" name="file" value="{{ $f->getFilename() }}">
                    <button class="btn btn-sm btn-outline-danger" type="submit">Restore</button>
                </form>
                <form method="POST" action="{{ route('backups.destroy') }}" class="d-inline" data-confirm="Delete this backup file?" data-confirm-button="Yes, delete">
                    @csrf
                    <input type="hidden" name="file" value="{{ $f->getFilename() }}">
                    <button class="btn btn-sm btn-outline-dark" type="submit">Delete</button>
                </form>
            </td>
        </tr>
    @empty
        <tr><td colspan="4" class="text-muted text-center py-4">No backups yet. Click “Create & Download Backup”.</td></tr>
    @endforelse
    </tbody>
</table>
</div>
</div>
</div>
</div>
</div>
@endsection
