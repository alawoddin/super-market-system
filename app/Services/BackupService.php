<?php

namespace App\Services;

use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\File;
use RuntimeException;
use Symfony\Component\Process\Process;

class BackupService
{
    public function backupDirectory(): string
    {
        $dir = storage_path('app/backups');
        File::ensureDirectoryExists($dir);

        return $dir;
    }

    public function create(): string
    {
        $filename = 'backup-'.now()->format('Ymd-His').'.sql';
        $path = $this->backupDirectory().DIRECTORY_SEPARATOR.$filename;

        try {
            $this->createWithMysqldump($path);
        } catch (\Throwable $e) {
            $this->createWithPhp($path);
        }

        if (! File::exists($path) || File::size($path) < 10) {
            throw new RuntimeException('Backup file was not created or is empty.');
        }

        return $filename;
    }

    public function restore(string $filename): void
    {
        $path = $this->backupDirectory().DIRECTORY_SEPARATOR.basename($filename);
        if (! File::exists($path)) {
            throw new RuntimeException('Backup file not found.');
        }

        $sql = File::get($path);
        if (trim($sql) === '') {
            throw new RuntimeException('Backup file is empty.');
        }

        try {
            $this->restoreWithMysqlClient($path);
        } catch (\Throwable $e) {
            $this->restoreWithPhp($sql);
        }
    }

    private function createWithMysqldump(string $path): void
    {
        $mysqldump = $this->findBinary(['mysqldump.exe', 'mysqldump']);
        if (! $mysqldump) {
            throw new RuntimeException('mysqldump not found.');
        }

        $db = config('database.connections.mysql');
        $command = [
            $mysqldump,
            '--host='.$db['host'],
            '--port='.(string) $db['port'],
            '--user='.$db['username'],
            '--single-transaction',
            '--routines',
            '--triggers',
            '--column-statistics=0',
            '--result-file='.$path,
            $db['database'],
        ];

        if (($db['password'] ?? '') !== '') {
            array_splice($command, 4, 0, '--password='.$db['password']);
        }

        $process = new Process($command);
        $process->setTimeout(180);
        $process->run();

        $ok = File::exists($path) && File::size($path) > 10;
        if (! $ok) {
            throw new RuntimeException(trim($process->getErrorOutput() ?: $process->getOutput() ?: 'mysqldump failed'));
        }
    }

    private function createWithPhp(string $path): void
    {
        $database = config('database.connections.mysql.database');
        $tables = DB::select('SHOW TABLES');
        $key = 'Tables_in_'.$database;

        $sql = "-- Supermarket System Backup\n";
        $sql .= '-- Generated: '.now()->toDateTimeString()."\n";
        $sql .= '-- Database: '.$database."\n\n";
        $sql .= "SET FOREIGN_KEY_CHECKS=0;\n\n";

        foreach ($tables as $tableRow) {
            $table = $tableRow->{$key} ?? array_values((array) $tableRow)[0];

            $create = DB::select('SHOW CREATE TABLE `'.$table.'`');
            $createSql = $create[0]->{'Create Table'} ?? null;
            if (! $createSql) {
                continue;
            }

            $sql .= "DROP TABLE IF EXISTS `{$table}`;\n";
            $sql .= $createSql.";\n\n";

            $rows = DB::table($table)->get();
            foreach ($rows as $row) {
                $values = [];
                foreach ((array) $row as $value) {
                    if (is_null($value)) {
                        $values[] = 'NULL';
                    } elseif (is_numeric($value) && ! preg_match('/^0\d+/', (string) $value)) {
                        $values[] = $value;
                    } else {
                        $values[] = "'".str_replace(["\\", "'"], ["\\\\", "\\'"], (string) $value)."'";
                    }
                }
                $sql .= 'INSERT INTO `'.$table.'` VALUES ('.implode(',', $values).");\n";
            }
            $sql .= "\n";
        }

        $sql .= "SET FOREIGN_KEY_CHECKS=1;\n";
        File::put($path, $sql);
    }

    private function restoreWithMysqlClient(string $path): void
    {
        $mysql = $this->findBinary(['mysql.exe', 'mysql']);
        if (! $mysql) {
            throw new RuntimeException('mysql client not found.');
        }

        $db = config('database.connections.mysql');
        $args = [
            $mysql,
            '--host='.$db['host'],
            '--port='.(string) $db['port'],
            '--user='.$db['username'],
            $db['database'],
        ];

        if (($db['password'] ?? '') !== '') {
            array_splice($args, 4, 0, '--password='.$db['password']);
        }

        $process = new Process($args);
        $process->setTimeout(180);
        $process->setInput(File::get($path));
        $process->run();

        if (! $process->isSuccessful()) {
            throw new RuntimeException(trim($process->getErrorOutput() ?: 'mysql restore failed'));
        }
    }

    private function restoreWithPhp(string $sql): void
    {
        // Remove comments and split on semicolons carefully enough for our dumps.
        $sql = preg_replace('/^--.*$/m', '', $sql);
        $statements = array_filter(array_map('trim', explode(";\n", $sql)));

        DB::statement('SET FOREIGN_KEY_CHECKS=0');
        try {
            foreach ($statements as $statement) {
                if ($statement === '' || str_starts_with($statement, '/*')) {
                    continue;
                }
                DB::unprepared($statement);
            }
        } finally {
            DB::statement('SET FOREIGN_KEY_CHECKS=1');
        }
    }

    private function findBinary(array $names): ?string
    {
        $dirs = [
            'C:\\Program Files\\MySQL\\MySQL Server 8.0\\bin',
            'C:\\Program Files\\MySQL\\MySQL Server 8.4\\bin',
            'C:\\Program Files\\MySQL\\MySQL Server 5.7\\bin',
            'C:\\xampp\\mysql\\bin',
            'C:\\wamp64\\bin\\mysql\\mysql8.0.31\\bin',
            'C:\\laragon\\bin\\mysql\\mysql-8.0.30-winx64\\bin',
        ];

        foreach ($dirs as $dir) {
            foreach ($names as $name) {
                $path = $dir.DIRECTORY_SEPARATOR.$name;
                if (File::exists($path)) {
                    return $path;
                }
            }
        }

        foreach ($names as $name) {
            $process = Process::fromShellCommandline(
                strtoupper(substr(PHP_OS, 0, 3)) === 'WIN'
                    ? 'where '.$name
                    : 'which '.$name
            );
            $process->run();
            if ($process->isSuccessful()) {
                $line = trim(explode("\n", trim($process->getOutput()))[0] ?? '');
                if ($line !== '' && File::exists($line)) {
                    return $line;
                }
            }
        }

        return null;
    }
}
