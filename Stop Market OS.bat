@echo off
rem Stops the Market OS server running in the background.
powershell.exe -NoProfile -ExecutionPolicy Bypass -File "%~dp0launcher\stop.ps1"
timeout /t 2 >nul
