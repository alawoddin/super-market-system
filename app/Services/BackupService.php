<?php

namespace App\Services;

use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\File;
use RuntimeException;
use Symfony\Component\Process\Process;
use Throwable;

class BackupService
{
    public function backupDirectory(): string
    {
        $dir = storage_path('app/backups');
        File::ensureDirectoryExists($dir);

        if (! is_writable($dir)) {
            throw new RuntimeException('Backup folder is not writable: storage/app/backups');
        }

        return $dir;
    }

    public function path(string $filename): string
    {
        return $this->backupDirectory().DIRECTORY_SEPARATOR.basename($filename);
    }

    public function create(): string
    {
        $filename = 'backup-'.now()->format('Ymd-His').'.sql';
        $path = $this->path($filename);

        $errors = [];

        if ($this->canRunProcess()) {
            try {
                $this->createWithMysqldump($path);
            } catch (Throwable $e) {
                $errors[] = 'mysqldump: '.$e->getMessage();
                $this->safeUnlink($path);
            }
        }

        if (! $this->isValidBackup($path)) {
            try {
                $this->createWithPhp($path);
            } catch (Throwable $e) {
                $errors[] = 'php-dump: '.$e->getMessage();
                $this->safeUnlink($path);
                throw new RuntimeException('Backup failed. '.implode(' | ', $errors));
            }
        }

        if (! $this->isValidBackup($path)) {
            throw new RuntimeException('Backup file was not created or is empty.');
        }

        return $filename;
    }

    public function restore(string $filename): void
    {
        $path = $this->path($filename);
        if (! File::exists($path)) {
            throw new RuntimeException('Backup file not found.');
        }

        $sql = File::get($path);
        if (trim($sql) === '') {
            throw new RuntimeException('Backup file is empty.');
        }

        if ($this->canRunProcess()) {
            try {
                $this->restoreWithMysqlClient($path);

                return;
            } catch (Throwable) {
                // Fall back to PHP restore on shared hosting.
            }
        }

        $this->restoreWithPhp($sql);
    }

    public function delete(string $filename): void
    {
        $path = $this->path($filename);
        if (File::exists($path)) {
            File::delete($path);
        }
    }

    private function isValidBackup(string $path): bool
    {
        return File::exists($path) && File::size($path) > 20;
    }

    private function safeUnlink(string $path): void
    {
        if (File::exists($path)) {
            File::delete($path);
        }
    }

    private function canRunProcess(): bool
    {
        if (! function_exists('proc_open') || ! class_exists(Process::class)) {
            return false;
        }

        $disabled = array_map('trim', explode(',', (string) ini_get('disable_functions')));

        return ! in_array('proc_open', $disabled, true);
    }

    private function createWithMysqldump(string $path): void
    {
        $mysqldump = $this->findBinary(['mysqldump', 'mysqldump.exe']);
        if (! $mysqldump) {
            throw new RuntimeException('mysqldump not found');
        }

        $db = $this->mysqlConfig();
        $command = [
            $mysqldump,
            '--host='.$db['host'],
            '--port='.(string) $db['port'],
            '--user='.$db['username'],
            '--single-transaction',
            '--routines',
            '--triggers',
            '--default-character-set=utf8mb4',
            '--column-statistics=0',
            '--result-file='.$path,
            $db['database'],
        ];

        if (($db['password'] ?? '') !== '') {
            array_splice($command, 4, 0, '--password='.$db['password']);
        }

        $process = new Process($command);
        $process->setTimeout(300);
        $process->run();

        if (! $this->isValidBackup($path)) {
            throw new RuntimeException(trim($process->getErrorOutput() ?: $process->getOutput() ?: 'mysqldump failed'));
        }
    }

    private function createWithPhp(string $path): void
    {
        $connection = config('database.default');
        if ($connection !== 'mysql') {
            // Still try MySQL-style dump for this project.
        }

        $database = (string) config('database.connections.mysql.database');
        $tables = DB::select('SHOW FULL TABLES WHERE Table_type = \'BASE TABLE\'');
        $key = 'Tables_in_'.$database;

        $handle = fopen($path, 'wb');
        if ($handle === false) {
            throw new RuntimeException('Cannot write backup file.');
        }

        try {
            fwrite($handle, "-- Market OS SQL Backup\n");
            fwrite($handle, '-- Generated: '.now()->toDateTimeString()."\n");
            fwrite($handle, '-- Database: '.$database."\n");
            fwrite($handle, "-- Engine: PHP fallback (works on shared hosting)\n\n");
            fwrite($handle, "SET NAMES utf8mb4;\n");
            fwrite($handle, "SET FOREIGN_KEY_CHECKS=0;\n");
            fwrite($handle, "SET SQL_MODE='NO_AUTO_VALUE_ON_ZERO';\n\n");

            foreach ($tables as $tableRow) {
                $table = $tableRow->{$key} ?? array_values((array) $tableRow)[0];
                $table = (string) $table;

                $create = DB::select('SHOW CREATE TABLE `'.$table.'`');
                $createSql = $create[0]->{'Create Table'} ?? null;
                if (! $createSql) {
                    continue;
                }

                fwrite($handle, "DROP TABLE IF EXISTS `{$table}`;\n");
                fwrite($handle, $createSql.";\n\n");

                $columns = DB::getSchemaBuilder()->getColumnListing($table);
                if ($columns === []) {
                    continue;
                }

                $columnList = '`'.implode('`,`', $columns).'`';

                DB::table($table)->orderBy($columns[0])->chunk(200, function ($rows) use ($handle, $table, $columns, $columnList) {
                    if ($rows->isEmpty()) {
                        return;
                    }

                    $valueGroups = [];
                    foreach ($rows as $row) {
                        $arr = (array) $row;
                        $values = [];
                        foreach ($columns as $column) {
                            $values[] = $this->sqlValue($arr[$column] ?? null);
                        }
                        $valueGroups[] = '('.implode(',', $values).')';
                    }

                    fwrite(
                        $handle,
                        'INSERT INTO `'.$table.'` ('.$columnList.') VALUES '."\n".implode(",\n", $valueGroups).";\n"
                    );
                });

                fwrite($handle, "\n");
            }

            fwrite($handle, "SET FOREIGN_KEY_CHECKS=1;\n");
        } finally {
            fclose($handle);
        }
    }

    private function sqlValue(mixed $value): string
    {
        if ($value === null) {
            return 'NULL';
        }

        if (is_bool($value)) {
            return $value ? '1' : '0';
        }

        if (is_int($value) || is_float($value)) {
            return (string) $value;
        }

        // Binary-safe-ish escape for MySQL string literals.
        $string = (string) $value;
        $string = str_replace(["\\", "\0", "\n", "\r", "'", '"', "\x1a"], ['\\\\', '\\0', '\\n', '\\r', "\\'", '\\"', '\\Z'], $string);

        return "'".$string."'";
    }

    private function restoreWithMysqlClient(string $path): void
    {
        $mysql = $this->findBinary(['mysql', 'mysql.exe']);
        if (! $mysql) {
            throw new RuntimeException('mysql client not found');
        }

        $db = $this->mysqlConfig();
        $args = [
            $mysql,
            '--host='.$db['host'],
            '--port='.(string) $db['port'],
            '--user='.$db['username'],
            '--default-character-set=utf8mb4',
            $db['database'],
        ];

        if (($db['password'] ?? '') !== '') {
            array_splice($args, 4, 0, '--password='.$db['password']);
        }

        $process = new Process($args);
        $process->setTimeout(300);
        $process->setInput(File::get($path));
        $process->run();

        if (! $process->isSuccessful()) {
            throw new RuntimeException(trim($process->getErrorOutput() ?: 'mysql restore failed'));
        }
    }

    private function restoreWithPhp(string $sql): void
    {
        $statements = $this->splitSqlStatements($sql);

        DB::statement('SET FOREIGN_KEY_CHECKS=0');
        try {
            foreach ($statements as $statement) {
                $trimmed = trim($statement);
                if ($trimmed === '' || str_starts_with($trimmed, '--') || str_starts_with($trimmed, '/*')) {
                    continue;
                }
                DB::unprepared($trimmed);
            }
        } finally {
            DB::statement('SET FOREIGN_KEY_CHECKS=1');
        }
    }

    /**
     * Split SQL into statements while respecting quoted strings.
     *
     * @return array<int, string>
     */
    private function splitSqlStatements(string $sql): array
    {
        $sql = preg_replace('/^--.*$/m', '', $sql) ?? $sql;
        $statements = [];
        $buffer = '';
        $inSingle = false;
        $inDouble = false;
        $inBacktick = false;
        $len = strlen($sql);

        for ($i = 0; $i < $len; $i++) {
            $char = $sql[$i];
            $prev = $i > 0 ? $sql[$i - 1] : '';

            if ($char === "'" && ! $inDouble && ! $inBacktick && $prev !== '\\') {
                $inSingle = ! $inSingle;
            } elseif ($char === '"' && ! $inSingle && ! $inBacktick && $prev !== '\\') {
                $inDouble = ! $inDouble;
            } elseif ($char === '`' && ! $inSingle && ! $inDouble) {
                $inBacktick = ! $inBacktick;
            }

            if ($char === ';' && ! $inSingle && ! $inDouble && ! $inBacktick) {
                $statements[] = $buffer;
                $buffer = '';
                continue;
            }

            $buffer .= $char;
        }

        if (trim($buffer) !== '') {
            $statements[] = $buffer;
        }

        return $statements;
    }

    private function mysqlConfig(): array
    {
        return config('database.connections.mysql');
    }

    private function findBinary(array $names): ?string
    {
        $dirs = array_filter([
            env('MYSQL_BIN_PATH'),
            'C:\\xampp\\mysql\\bin',
            'C:\\laragon\\bin\\mysql\\mysql-8.0.30-winx64\\bin',
            'C:\\Program Files\\MySQL\\MySQL Server 8.4\\bin',
            'C:\\Program Files\\MySQL\\MySQL Server 8.0\\bin',
            'C:\\Program Files\\MySQL\\MySQL Server 5.7\\bin',
            'C:\\wamp64\\bin\\mysql\\mysql8.0.31\\bin',
            '/usr/bin',
            '/usr/local/bin',
            '/usr/local/mysql/bin',
        ]);

        foreach ($dirs as $dir) {
            foreach ($names as $name) {
                $candidate = rtrim((string) $dir, '\\/').DIRECTORY_SEPARATOR.$name;
                if (File::exists($candidate)) {
                    return $candidate;
                }
            }
        }

        foreach ($names as $name) {
            try {
                $process = Process::fromShellCommandline(
                    strtoupper(substr(PHP_OS, 0, 3)) === 'WIN' ? 'where '.$name : 'command -v '.$name
                );
                $process->setTimeout(5);
                $process->run();
                if ($process->isSuccessful()) {
                    $line = trim(explode("\n", trim($process->getOutput()))[0] ?? '');
                    if ($line !== '' && File::exists($line)) {
                        return $line;
                    }
                }
            } catch (Throwable) {
                // ignore
            }
        }

        return null;
    }
}
