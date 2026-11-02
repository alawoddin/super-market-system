# Market OS launcher: makes sure MySQL and the Laravel server are running, then opens the app window.
# Started by "Market OS.vbs" (the desktop shortcut) so no black console window is shown.
param([switch]$NoBrowser)

$ErrorActionPreference = 'Stop'
$root = Split-Path $PSScriptRoot -Parent
$port = 8000
$url = "http://127.0.0.1:$port"
$logFile = Join-Path $root 'storage\logs\launcher.log'

function Write-Log($text) {
    Add-Content -Path $logFile -Value ("[{0}] {1}" -f (Get-Date -Format 'yyyy-MM-dd HH:mm:ss'), $text)
}

function Show-Error($text) {
    Write-Log "ERROR: $text"
    Add-Type -AssemblyName PresentationFramework
    [System.Windows.MessageBox]::Show($text, 'Market OS', 'OK', 'Error') | Out-Null
    exit 1
}

function Test-Port([int]$p) {
    $client = New-Object System.Net.Sockets.TcpClient
    try {
        $wait = $client.BeginConnect('127.0.0.1', $p, $null, $null)
        return $wait.AsyncWaitHandle.WaitOne(300) -and $client.Connected
    } catch { return $false } finally { $client.Close() }
}

function Wait-Port([int]$p, [int]$seconds) {
    for ($i = 0; $i -lt $seconds * 2; $i++) {
        if (Test-Port $p) { return $true }
        Start-Sleep -Milliseconds 500
    }
    return $false
}

function Find-Php {
    $cmd = Get-Command php -ErrorAction SilentlyContinue
    if ($cmd) { return $cmd.Source }
    foreach ($path in @('C:\xampp\php\php.exe', 'C:\php\php.exe')) {
        if (Test-Path $path) { return $path }
    }
    $laragon = Get-ChildItem 'C:\laragon\bin\php\*\php.exe' -ErrorAction SilentlyContinue | Select-Object -Last 1
    if ($laragon) { return $laragon.FullName }
    return $null
}

function Find-Browser {
    foreach ($path in @(
        "$env:ProgramFiles\Google\Chrome\Application\chrome.exe",
        "${env:ProgramFiles(x86)}\Google\Chrome\Application\chrome.exe",
        "$env:LOCALAPPDATA\Google\Chrome\Application\chrome.exe",
        "${env:ProgramFiles(x86)}\Microsoft\Edge\Application\msedge.exe",
        "$env:ProgramFiles\Microsoft\Edge\Application\msedge.exe"
    )) {
        if ($path -and (Test-Path $path)) { return $path }
    }
    return $null
}

# 1. MySQL
if (-not (Test-Port 3306)) {
    Write-Log 'MySQL not running, trying to start it'
    $service = Get-Service | Where-Object { $_.Name -match '^(MySQL|MariaDB)' } | Select-Object -First 1
    if ($service) {
        try { Start-Service $service.Name } catch { Write-Log "Could not start service $($service.Name): $_" }
    } elseif (Test-Path 'C:\xampp\mysql\bin\mysqld.exe') {
        Start-Process 'C:\xampp\mysql\bin\mysqld.exe' -ArgumentList '--defaults-file=C:\xampp\mysql\bin\my.ini' -WindowStyle Hidden
    }
    if (-not (Wait-Port 3306 30)) {
        Show-Error "MySQL is not running.`n`nOpen Windows Services, find MySQL, and press Start. Then open Market OS again."
    }
}

# 2. Laravel server
if (-not (Test-Port $port)) {
    $php = Find-Php
    if (-not $php) { Show-Error 'PHP was not found on this computer. Install XAMPP (PHP) and try again.' }
    Write-Log "Starting server with $php"
    Start-Process -FilePath $php -ArgumentList 'artisan', 'serve', '--host=127.0.0.1', "--port=$port" `
        -WorkingDirectory $root -WindowStyle Hidden
    if (-not (Wait-Port $port 30)) {
        Show-Error "The Market OS server did not start.`n`nSee storage\logs\launcher.log and storage\logs\laravel.log."
    }
}

if ($NoBrowser) { Write-Log 'Server ready (no browser)'; exit 0 }

# 3. App window. A separate browser profile keeps it apart from normal browsing,
#    and lets --kiosk-printing print receipts straight to the default printer without a dialog.
$browser = Find-Browser
$profileDir = Join-Path $env:LOCALAPPDATA 'MarketOS\browser-profile'
if ($browser) {
    Start-Process -FilePath $browser -ArgumentList "--app=$url", '--start-maximized', '--kiosk-printing', `
        "--user-data-dir=`"$profileDir`"", '--no-first-run', '--no-default-browser-check'
} else {
    Start-Process $url
}
Write-Log 'App opened'
