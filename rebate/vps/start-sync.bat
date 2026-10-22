@echo off
rem Starts the XRover 5 lot sync and repeats it every interval_minutes (see config.json).
rem Put a shortcut to this file in the Startup folder (Win+R, shell:startup) so it runs after a VPS restart.
cd /d "%~dp0"
title XRover 5 lot sync - keep this window open
python xr5_lot_sync.py --loop
pause
