@echo off
rem One-time setup on the shop computer: database, settings, and desktop shortcut.
powershell.exe -NoProfile -ExecutionPolicy Bypass -File "%~dp0launcher\install.ps1"
