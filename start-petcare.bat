@echo off
title PetCare - Start All
cd /d "%~dp0"
powershell -NoProfile -ExecutionPolicy Bypass -File "%~dp0start-petcare.ps1" %*
if errorlevel 1 pause
