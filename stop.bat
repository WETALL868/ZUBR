@echo off
setlocal enabledelayedexpansion
cd /d "%~dp0"
title Mail Analytics - stop

echo Stopping Mail Analytics / Ostanovka programmy...
echo.

set "PORT=8000"
if not exist ".env" goto :find_process
for /f "usebackq tokens=2 delims==" %%v in (`findstr /b /c:"APP_PORT" ".env"`) do set "PORT=%%v"
set "PORT=!PORT: =!"

:find_process
echo Checking port !PORT!
set "STOPPED=0"
for /f "tokens=5" %%p in ('netstat -ano ^| findstr /r /c:"TCP.*:!PORT! .*LISTENING"') do call :kill_pid %%p

if "!STOPPED!"=="0" goto :not_running
echo.
echo Stopped / Programma ostanovlena.
goto :done

:kill_pid
echo   Closing process PID %1
taskkill /PID %1 /F >nul 2>&1
set "STOPPED=1"
goto :eof

:not_running
echo.
echo Nothing is running on port !PORT!.
echo Programma na portu !PORT! ne naidena - vozmozhno, uzhe ostanovlena.

:done
echo.
pause
endlocal
