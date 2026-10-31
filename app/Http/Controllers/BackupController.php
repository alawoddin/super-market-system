<?php

namespace App\Http\Controllers;

use App\Services\BackupService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\File;

class BackupController extends Controller
{
    public function __construct(private BackupService $backups)
    {
    }

    public function index()
    {
        $dir = $this->backups->backupDirectory();
        $files = collect(File::files($dir))
            ->filter(fn ($f) => str_ends_with(strtolower($f->getFilename()), '.sql'))
            ->sortByDesc(fn ($f) => $f->getMTime())
            ->values();

        return view('admin.backups.index', compact('files'));
    }

    public function create(Request $request)
    {
        try {
            $filename = $this->backups->create();
        } catch (\Throwable $e) {
            return back()->with('backup_status', [
                'type' => 'error',
                'text' => 'Backup failed: '.$e->getMessage(),
            ]);
        }

        // Hosted sites: also download immediately so admin keeps a local copy.
        if ($request->boolean('download', true)) {
            $path = $this->backups->path($filename);

            return response()->download($path, $filename, [
                'Content-Type' => 'application/sql',
            ]);
        }

        return back()->with('backup_status', [
            'type' => 'success',
            'text' => 'Backup created: '.$filename,
        ]);
    }

    public function download(string $file)
    {
        $path = $this->backups->path($file);
        abort_unless(File::exists($path), 404);

        return response()->download($path, basename($file), [
            'Content-Type' => 'application/sql',
        ]);
    }

    public function restore(Request $request)
    {
        $request->validate(['file' => ['required', 'string']]);

        try {
            $this->backups->restore($request->file);
            Artisan::call('optimize:clear');
        } catch (\Throwable $e) {
            return back()->with('backup_status', [
                'type' => 'error',
                'text' => 'Restore failed: '.$e->getMessage(),
            ]);
        }

        return back()->with('backup_status', [
            'type' => 'success',
            'text' => 'Database restored successfully from '.$request->file,
        ]);
    }

    public function destroy(Request $request)
    {
        $request->validate(['file' => ['required', 'string']]);

        try {
            $this->backups->delete($request->file);
        } catch (\Throwable $e) {
            return back()->with('backup_status', [
                'type' => 'error',
                'text' => 'Delete failed: '.$e->getMessage(),
            ]);
        }

        return back()->with('backup_status', [
            'type' => 'success',
            'text' => 'Backup deleted.',
        ]);
    }
}
