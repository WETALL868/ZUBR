@echo off
setlocal
cd /d "%~dp0"
title Mail Analytics - diagnostics

echo ============================================================
echo   Diagnostics / Diagnostika. Send this text for support.
echo ============================================================
echo.
echo Current folder:
echo   %CD%
echo.

echo Windows version:
ver
echo.

echo Python launcher (py):
py -3 --version 2>&1
echo   errorlevel=%errorlevel%
echo.

echo Python in PATH:
python --version 2>&1
echo   errorlevel=%errorlevel%
echo.

echo Virtual environment:
if exist ".venv\Scripts\python.exe" goto :venv_yes
echo   NOT created yet (.venv is missing)
goto :files

:venv_yes
".venv\Scripts\python.exe" --version 2>&1
echo   Checking packages:
".venv\Scripts\python.exe" -c "import fastapi, sqlalchemy, openai, openpyxl, jinja2; print('   all packages OK')" 2>&1

:files
echo.
echo Key files:
if exist "run.py" (echo   run.py OK) else (echo   run.py MISSING)
if exist "requirements.txt" (echo   requirements.txt OK) else (echo   requirements.txt MISSING)
if exist ".env" (echo   .env OK) else (echo   .env not created yet)
if exist "app\main.py" (echo   app\main.py OK) else (echo   app\main.py MISSING)
if exist "data" (echo   data folder OK) else (echo   data folder MISSING)
echo.

echo Last lines of logs\app.log:
if exist "logs\app.log" goto :show_log
echo   log file not created yet
goto :done

:show_log
powershell -NoProfile -Command "Get-Content 'logs\app.log' -Tail 15" 2>nul

:done
echo.
echo ============================================================
pause
endlocal
