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

    public function create()
    {
        try {
            $filename = $this->backups->create();
        } catch (\Throwable $e) {
            return back()->with([
                'message' => 'Backup failed: '.$e->getMessage(),
                'alert-type' => 'error',
            ]);
        }

        return back()->with([
            'message' => 'Backup created successfully: '.$filename,
            'alert-type' => 'success',
        ]);
    }

    public function download(string $file)
    {
        $path = storage_path('app/backups/'.basename($file));
        abort_unless(File::exists($path), 404);

        return response()->download($path);
    }

    public function restore(Request $request)
    {
        $request->validate(['file' => ['required', 'string']]);

        try {
            $this->backups->restore($request->file);
            Artisan::call('cache:clear');
        } catch (\Throwable $e) {
            return back()->with([
                'message' => 'Restore failed: '.$e->getMessage(),
                'alert-type' => 'error',
            ]);
        }

        return back()->with([
            'message' => 'Database restored successfully',
            'alert-type' => 'success',
        ]);
    }
}
