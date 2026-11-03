# One-time setup of Market OS on a shop computer. Safe to run again (keeps data and the admin password).
$ErrorActionPreference = 'Stop'
$root = Split-Path $PSScriptRoot -Parent
Set-Location $root

function Step($text) { Write-Host "`n==> $text" -ForegroundColor Cyan }
function Fail($text) { Write-Host "`nERROR: $text" -ForegroundColor Red; Read-Host 'Press Enter to close'; exit 1 }

function Set-EnvValue([string]$key, [string]$value) {
    $lines = Get-Content .env
    if ($lines -match "^$key=") { $lines = $lines -replace "^$key=.*", "$key=$value" } else { $lines += "$key=$value" }
    [System.IO.File]::WriteAllLines((Join-Path $root '.env'), [string[]]$lines)   # UTF-8 without BOM
}
function Get-EnvValue([string]$key) {
    $line = Get-Content .env | Where-Object { $_ -match "^$key=" } | Select-Object -First 1
    if ($line) { return ($line -replace "^$key=", '').Trim('"') } else { return '' }
}

# PHP
Step 'Finding PHP'
$php = (Get-Command php -ErrorAction SilentlyContinue).Source
if (-not $php -and (Test-Path 'C:\xampp\php\php.exe')) { $php = 'C:\xampp\php\php.exe' }
if (-not $php) { Fail 'PHP not found. Install XAMPP first.' }
Write-Host $php
if (-not (Test-Path 'vendor\autoload.php')) { Fail 'The vendor folder is missing. Copy the whole project folder (including vendor), or run "composer install".' }

# .env
Step 'Settings file (.env)'
if (-not (Test-Path .env)) {
    Copy-Item .env.example .env
    $dbName = Read-Host 'MySQL database name [shop]'; if (-not $dbName) { $dbName = 'shop' }
    $dbUser = Read-Host 'MySQL user [root]'; if (-not $dbUser) { $dbUser = 'root' }
    $dbPass = Read-Host 'MySQL password (leave empty if none)'
    Set-EnvValue 'DB_CONNECTION' 'mysql'
    Set-EnvValue 'DB_HOST' '127.0.0.1'
    Set-EnvValue 'DB_PORT' '3306'
    Set-EnvValue 'DB_DATABASE' $dbName
    Set-EnvValue 'DB_USERNAME' $dbUser
    Set-EnvValue 'DB_PASSWORD' $dbPass
    & $php artisan key:generate --force
}
Set-EnvValue 'APP_ENV' 'production'
Set-EnvValue 'APP_DEBUG' 'false'
Set-EnvValue 'APP_URL' 'http://127.0.0.1:8000'
Write-Host 'OK'

# Database
Step 'Database'
$client = New-Object System.Net.Sockets.TcpClient
try { $client.Connect('127.0.0.1', [int](Get-EnvValue 'DB_PORT')) } catch { Fail 'MySQL is not running. Start the MySQL service and run this again.' } finally { $client.Close() }
& $php (Join-Path $PSScriptRoot 'create-database.php')
if ($LASTEXITCODE -ne 0) { Fail 'Could not connect to MySQL with the user and password in .env.' }
& $php artisan migrate --force
if ($LASTEXITCODE -ne 0) { Fail 'Database update (migrate) failed.' }
& $php artisan db:seed --force
if (-not (Test-Path 'public\storage')) { & $php artisan storage:link }

# Speed up
Step 'Optimizing'
& $php artisan optimize

# Shortcuts
Step 'Creating shortcuts'
$shell = New-Object -ComObject WScript.Shell
$target = Join-Path $env:WINDIR 'System32\wscript.exe'
$vbs = Join-Path $PSScriptRoot 'Market OS.vbs'
$icon = Join-Path $PSScriptRoot 'ghaznowyan.ico'
$places = @([Environment]::GetFolderPath('Desktop'), [Environment]::GetFolderPath('Programs'))
$autostart = Read-Host 'Open Ghaznowyan Hyper automatically when Windows starts? (y/n) [n]'
if ($autostart -match '^[yY]') { $places += [Environment]::GetFolderPath('Startup') }
foreach ($folder in $places) {
    # Older installs named the shortcut "Market OS"
    Remove-Item (Join-Path $folder 'Market OS.lnk') -ErrorAction SilentlyContinue
    $link = $shell.CreateShortcut((Join-Path $folder 'Ghaznowyan Hyper.lnk'))
    $link.TargetPath = $target
    $link.Arguments = "`"$vbs`""
    $link.WorkingDirectory = $root
    $link.IconLocation = $icon
    $link.Description = 'Ghaznowyan Hyper supermarket system'
    $link.Save()
    Write-Host "Shortcut: $folder"
}

Write-Host "`nDone! Double-click 'Ghaznowyan Hyper' on the desktop to start." -ForegroundColor Green
Read-Host 'Press Enter to close'
