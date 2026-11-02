<?php
// Creates the MySQL database named in .env if it does not exist yet. Used by install.ps1.
$env = parse_ini_file(__DIR__.'/../.env', false, INI_SCANNER_RAW);
$name = str_replace('`', '', $env['DB_DATABASE'] ?? 'shop');
$pdo = new PDO('mysql:host='.($env['DB_HOST'] ?? '127.0.0.1').';port='.($env['DB_PORT'] ?? '3306'), $env['DB_USERNAME'] ?? 'root', trim($env['DB_PASSWORD'] ?? '', '"'));
$pdo->exec("CREATE DATABASE IF NOT EXISTS `{$name}` CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci");
echo "Database '{$name}' ready\n";
