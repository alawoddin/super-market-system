<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\File;
use Symfony\Component\Process\Process;

class BackupController extends Controller
{
    public function index()
    {
        $dir = storage_path('app/backups');
        File::ensureDirectoryExists($dir);
        $files = collect(File::files($dir))
            ->sortByDesc(fn ($f) => $f->getMTime())
            ->values();

        return view('admin.backups.index', compact('files'));
    }

    public function create()
    {
        $dir = storage_path('app/backups');
        File::ensureDirectoryExists($dir);

        $filename = 'backup-'.now()->format('Ymd-His').'.sql';
        $path = $dir.DIRECTORY_SEPARATOR.$filename;

        $db = config('database.connections.mysql');
        $mysqldump = $this->findMysqldump();

        if (! $mysqldump) {
            return back()->with(['message' => 'mysqldump not found on this system.', 'alert-type' => 'error']);
        }

        $command = [
            $mysqldump,
            '-h', $db['host'],
            '-P', (string) $db['port'],
            '-u', $db['username'],
            $db['database'],
        ];

        if (! empty($db['password'])) {
            $command[] = '-p'.$db['password'];
        }

        $process = new Process($command);
        $process->run();

        if (! $process->isSuccessful()) {
            return back()->with(['message' => 'Backup failed: '.$process->getErrorOutput(), 'alert-type' => 'error']);
        }

        File::put($path, $process->getOutput());

        return back()->with(['message' => 'Backup created: '.$filename, 'alert-type' => 'success']);
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
        $path = storage_path('app/backups/'.basename($request->file));
        abort_unless(File::exists($path), 404);

        $db = config('database.connections.mysql');
        $mysql = $this->findMysqlClient();

        if (! $mysql) {
            return back()->with(['message' => 'mysql client not found.', 'alert-type' => 'error']);
        }

        $command = sprintf(
            '"%s" -h%s -P%s -u%s %s %s < "%s"',
            $mysql,
            $db['host'],
            $db['port'],
            $db['username'],
            $db['password'] !== '' ? '-p'.escapeshellarg($db['password']) : '',
            escapeshellarg($db['database']),
            $path
        );

        // Use Process with input instead of shell redirect for Windows safety
        $args = [$mysql, '-h', $db['host'], '-P', (string) $db['port'], '-u', $db['username'], $db['database']];
        if ($db['password'] !== '') {
            $args[] = '-p'.$db['password'];
        }

        $process = new Process($args);
        $process->setInput(File::get($path));
        $process->run();

        if (! $process->isSuccessful()) {
            return back()->with(['message' => 'Restore failed: '.$process->getErrorOutput(), 'alert-type' => 'error']);
        }

        Artisan::call('cache:clear');

        return back()->with(['message' => 'Database restored successfully', 'alert-type' => 'success']);
    }

    private function findMysqldump(): ?string
    {
        $candidates = [
            'C:\\Program Files\\MySQL\\MySQL Server 8.0\\bin\\mysqldump.exe',
            'C:\\xampp\\mysql\\bin\\mysqldump.exe',
            'mysqldump',
        ];

        foreach ($candidates as $bin) {
            if ($bin === 'mysqldump' || File::exists($bin)) {
                return $bin;
            }
        }

        return null;
    }

    private function findMysqlClient(): ?string
    {
        $candidates = [
            'C:\\Program Files\\MySQL\\MySQL Server 8.0\\bin\\mysql.exe',
            'C:\\xampp\\mysql\\bin\\mysql.exe',
            'mysql',
        ];

        foreach ($candidates as $bin) {
            if ($bin === 'mysql' || File::exists($bin)) {
                return $bin;
            }
        }

        return null;
    }
}
